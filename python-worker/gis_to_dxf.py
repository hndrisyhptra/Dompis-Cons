#!/usr/bin/env python3
"""
gis_to_dxf.py - Worker untuk fitur "GIS to CAD Generator" Dompis Cons.

Dipanggil oleh app/Services/Gis/PythonDxfWorkerService.php lewat subprocess.
Tugasnya CUMA dua:
  1. Transformasi koordinat WGS84 (EPSG:4326) -> UTM meter (pakai pyproj),
     zona UTM dideteksi otomatis dari titik tengah dataset.
  2. Tulis file DXF AutoCAD (pakai ezdxf) dengan layer, block simbol per
     tipe object, label TEXT, dan LWPOLYLINE untuk jalur kabel.

Kontrak I/O (dijaga sederhana & stabil supaya gampang di-debug manual):
  Input   : --input <path JSON>   -> lihat contoh struktur di bawah
  Output  : --output <path DXF>   -> file DXF ditulis di sini
  Template: --template <nama>     -> 'standard_fttx' (default) atau 'custom'

  Selalu print TEPAT SATU baris JSON terakhir ke stdout:
    sukses : {"success": true, "utm_zone": "49S", "epsg": 32749,
              "points_written": N, "polylines_written": M,
              "unclassified_points": K}
    gagal  : {"success": false, "error": "pesan error yang jelas"}
  exit code 0 = sukses, exit code != 0 = gagal (PHP-side juga mengecek dua-duanya).

  Struktur input JSON (dataset ternormalisasi dari GisToCadExportService):
  {
    "points": [
      {"ref": "p1", "type": "tiang", "name": "Tiang-01", "lat": -7.1, "lng": 112.7},
      {"ref": "p2", "type": "odp", "name": "ODP-03", "lat": ..., "lng": ...},
      ...
    ],
    "polylines": [
      {"ref": "r1", "type": "cable", "name": "Rute Kabel 1",
       "coordinates": [[lat, lng], [lat, lng], ...]}
    ],
    "template": "standard_fttx",
    "custom_layers": { "tiang": "MY_POLE_LAYER", ... }   # opsional, untuk template custom
  }

PENTING - BELUM PERNAH DIJALANKAN:
  Script ini ditulis mengikuti API resmi ezdxf & pyproj yang sudah stabil
  bertahun-tahun, TAPI belum bisa dieksekusi langsung oleh Claude karena
  sandbox yang tersedia tidak punya akses network ke PyPI untuk instalasi
  ezdxf/pyproj. WAJIB di-smoke-test manual sebelum dipakai user beneran -
  lihat python-worker/README.md untuk contoh perintah testnya.
"""

import argparse
import json
import math
import sys

try:
    import ezdxf
    from pyproj import Transformer
except ImportError as exc:  # pragma: no cover - pesan bantuan, bukan logic inti
    print(json.dumps({
        "success": False,
        "error": (
            "Library Python yang dibutuhkan belum terpasang ("
            + str(exc)
            + "). Jalankan: pip3 install -r python-worker/requirements.txt"
        ),
    }))
    sys.exit(1)


# --------------------------------------------------------------------------
# LAYER & BLOCK MAPPING (template 'standard_fttx')
# --------------------------------------------------------------------------

# type dataset -> nama layer DXF. Sesuai daftar layer standar di requirement:
# FTTX_POLE, FTTX_ODP, FTTX_ODC, FTTX_OTB, FTTX_JC, FTTX_CABLE, ROAD, BUILDING.
# FTTX_ENDING_SITE ditambahkan karena flow eksplisit minta baca titik Ending
# Site, walau tidak disebut di daftar 8 layer aslinya.
STANDARD_LAYER_MAP = {
    "tiang": "FTTX_POLE",
    "odp": "FTTX_ODP",
    "odc": "FTTX_ODC",
    "otb": "FTTX_OTB",
    "jc": "FTTX_JC",
    "ending_site": "FTTX_ENDING_SITE",
}

BLOCK_MAP = {
    "tiang": "BLOCK_POLE",
    "odp": "BLOCK_ODP",
    "odc": "BLOCK_ODC",
    "otb": "BLOCK_OTB",
    "jc": "BLOCK_JC",
    "ending_site": "BLOCK_ENDING",
}

# Titik yang tipenya tidak dikenali (mestinya sudah dibersihkan di layar
# review manual sebelum sampai ke worker ini) TETAP digambar, bukan
# dibuang diam-diam - supaya kelihatan di CAD & di ringkasan hasil.
FALLBACK_LAYER = "FTTX_UNCLASSIFIED"
FALLBACK_BLOCK = "BLOCK_UNCLASSIFIED"

# ACI color index (AutoCAD Color Index, 1-255) per layer.
LAYER_COLORS = {
    "FTTX_POLE": 5,             # biru
    "FTTX_ODP": 2,              # kuning
    "FTTX_ODC": 1,              # merah
    "FTTX_OTB": 3,              # hijau
    "FTTX_JC": 6,                # magenta
    "FTTX_ENDING_SITE": 4,       # cyan
    "FTTX_CABLE": 30,            # oranye
    "FTTX_UNCLASSIFIED": 9,      # abu-abu terang, sengaja mencolok
    "ROAD": 8,                   # abu-abu gelap
    "BUILDING": 252,             # abu-abu terang
}

# Ukuran simbol & label dalam METER (koordinat sudah dalam UTM meter).
# Sengaja dijadikan konstanta di satu tempat supaya gampang diubah
# ("mudah dikembangkan") tanpa bongkar logic transformasi/DXF writer-nya.
BLOCK_SYMBOL_SIZE = 0.6
TEXT_HEIGHT = 1.0
TEXT_OFFSET_X = 0.8
TEXT_OFFSET_Y = 0.4

# --------------------------------------------------------------------------
# ANTI-TUMPUK LABEL: di lapangan sangat umum beberapa titik (mis. Tiang +
# ODP yang dipasang di tiang yang sama) dicatat surveyor pada koordinat GPS
# yang nyaris identik (akurasi GPS HP ~3-5 meter). Kalau label semuanya
# cuma digeser dengan offset tetap yang sama (TEXT_OFFSET_X/Y di atas),
# label-label itu akan ikut menumpuk jadi satu juga. Untuk titik yang
# terdeteksi "satu lokasi" (jaraknya <= CLUSTER_RADIUS_M), label disebar
# melingkar di sekitar titik pusat cluster + digambar garis leader tipis
# supaya tetap jelas label itu punya siapa.
# --------------------------------------------------------------------------
CLUSTER_RADIUS_M = 2.5
CLUSTER_LABEL_RADIUS_M = 2.2
LEADER_LINE_COLOR = 8  # abu-abu gelap, netral - tidak mendominasi warna layer


def cluster_points_by_proximity(coords, radius):
    """Kelompokkan index titik yang jaraknya <= radius (transitif, semacam
    DBSCAN dengan min_samples=1). coords: list[(x, y)]. Return list of
    list[int] (index ke `coords`).
    """
    n = len(coords)
    visited = [False] * n
    clusters = []

    for i in range(n):
        if visited[i]:
            continue

        stack = [i]
        visited[i] = True
        cluster = []

        while stack:
            cur = stack.pop()
            cluster.append(cur)
            cx, cy = coords[cur]

            for j in range(n):
                if visited[j]:
                    continue
                jx, jy = coords[j]
                if (jx - cx) ** 2 + (jy - cy) ** 2 <= radius ** 2:
                    visited[j] = True
                    stack.append(j)

        clusters.append(cluster)

    return clusters


def compute_label_anchors(coords):
    """Hitung posisi label (x, y) untuk tiap titik di `coords`, sekaligus
    tandai titik mana yang perlu digambar garis leader (karena posisi
    labelnya digeser jauh dari simbol akibat cluster).

    Return: list sepanjang coords berisi tuple (anchor_x, anchor_y, needs_leader).
    """
    n = len(coords)
    anchors = [None] * n

    for cluster in cluster_points_by_proximity(coords, CLUSTER_RADIUS_M):
        if len(cluster) == 1:
            idx = cluster[0]
            x, y = coords[idx]
            anchors[idx] = (x + TEXT_OFFSET_X, y + TEXT_OFFSET_Y, False)
            continue

        # Cluster berisi >1 titik yang lokasinya nyaris sama -> sebar
        # labelnya melingkar di sekitar centroid cluster supaya tidak
        # saling menumpuk, urutan mengikuti urutan asli titik di dataset
        # supaya hasilnya deterministik/gampang di-review ulang.
        cluster = sorted(cluster)
        centroid_x = sum(coords[i][0] for i in cluster) / len(cluster)
        centroid_y = sum(coords[i][1] for i in cluster) / len(cluster)

        for order, idx in enumerate(cluster):
            angle = (2 * math.pi * order) / len(cluster)
            anchor_x = centroid_x + CLUSTER_LABEL_RADIUS_M * math.cos(angle)
            anchor_y = centroid_y + CLUSTER_LABEL_RADIUS_M * math.sin(angle)
            anchors[idx] = (anchor_x, anchor_y, True)

    return anchors


def detect_utm_epsg(lon: float, lat: float):
    """Deteksi zona UTM otomatis dari 1 titik (biasanya titik tengah dataset)."""
    zone = int((lon + 180) // 6) + 1
    zone = max(1, min(60, zone))

    if lat >= 0:
        return 32600 + zone, f"{zone}N"

    return 32700 + zone, f"{zone}S"


def build_blocks(doc):
    """Definisikan block simbol sederhana per tipe object.

    Semua entity di dalam block digambar di layer '0' (bukan layer target)
    supaya saat di-INSERT, block otomatis "mewarisi" layer dari INSERT-nya -
    ini konvensi standar DXF/AutoCAD untuk block symbol.
    """
    s = BLOCK_SYMBOL_SIZE

    # Tiang: lingkaran kecil + garis vertikal pendek (menyerupai simbol tiang)
    b = doc.blocks.new(name="BLOCK_POLE")
    b.add_circle(center=(0, 0), radius=s / 2)
    b.add_line((0, -s / 2), (0, -s))

    # ODP: lingkaran penuh
    b = doc.blocks.new(name="BLOCK_ODP")
    b.add_circle(center=(0, 0), radius=s / 2)

    # ODC: kotak
    half = s / 2
    b = doc.blocks.new(name="BLOCK_ODC")
    b.add_lwpolyline(
        [(-half, -half), (half, -half), (half, half), (-half, half)],
        close=True,
    )

    # OTB: segitiga
    b = doc.blocks.new(name="BLOCK_OTB")
    b.add_lwpolyline(
        [(0, half), (-half, -half), (half, -half)],
        close=True,
    )

    # JC: belah ketupat (diamond)
    b = doc.blocks.new(name="BLOCK_JC")
    b.add_lwpolyline(
        [(0, half), (half, 0), (0, -half), (-half, 0)],
        close=True,
    )

    # Ending Site: lingkaran ganda (konsentris)
    b = doc.blocks.new(name="BLOCK_ENDING")
    b.add_circle(center=(0, 0), radius=s / 2)
    b.add_circle(center=(0, 0), radius=s / 3)

    # Fallback untuk tipe yang tidak dikenal - tanda X di dalam kotak,
    # sengaja dibuat "mencolok" supaya gampang ditemukan drafter di CAD.
    b = doc.blocks.new(name="BLOCK_UNCLASSIFIED")
    b.add_lwpolyline(
        [(-half, -half), (half, -half), (half, half), (-half, half)],
        close=True,
    )
    b.add_line((-half, -half), (half, half))
    b.add_line((-half, half), (half, -half))


def resolve_layer_and_block(point_type: str, custom_layers: dict):
    layer = custom_layers.get(point_type) or STANDARD_LAYER_MAP.get(point_type, FALLBACK_LAYER)
    block = BLOCK_MAP.get(point_type, FALLBACK_BLOCK)

    return layer, block


def main():
    parser = argparse.ArgumentParser(description="Generate DXF AutoCAD dari dataset GIS survey Dompis Cons.")
    parser.add_argument("--input", required=True, help="Path file JSON dataset (points/polylines).")
    parser.add_argument("--output", required=True, help="Path tujuan file DXF.")
    parser.add_argument("--template", default="standard_fttx", help="standard_fttx (default) atau custom.")
    args = parser.parse_args()

    with open(args.input, "r", encoding="utf-8") as fh:
        dataset = json.load(fh)

    points = dataset.get("points") or []
    polylines = dataset.get("polylines") or []
    custom_layers = dataset.get("custom_layers") or {}

    if not points and not polylines:
        raise ValueError("Dataset kosong - tidak ada titik atau jalur kabel untuk digenerate.")

    # --------------------------------------------------------------------
    # 1. Tentukan zona UTM dari titik tengah (centroid) seluruh dataset.
    #    Untuk area survey lokal FTTx (biasanya beberapa ratus meter - a few
    #    km), 1 zona UTM sudah cukup akurat. Kalau suatu saat ada dataset
    #    yang melebar lintas 2 zona UTM sekaligus, ini adalah batas desain
    #    yang perlu ditingkatkan (dicatat di README, bukan silent error).
    # --------------------------------------------------------------------
    all_lonlat = [(p["lng"], p["lat"]) for p in points]
    for line in polylines:
        for lat, lng in line.get("coordinates", []):
            all_lonlat.append((lng, lat))

    if not all_lonlat:
        raise ValueError("Tidak ada koordinat valid di dalam dataset.")

    centroid_lon = sum(p[0] for p in all_lonlat) / len(all_lonlat)
    centroid_lat = sum(p[1] for p in all_lonlat) / len(all_lonlat)

    epsg, zone_label = detect_utm_epsg(centroid_lon, centroid_lat)
    transformer = Transformer.from_crs("EPSG:4326", f"EPSG:{epsg}", always_xy=True)

    def to_utm(lat, lng):
        x, y = transformer.transform(lng, lat)
        return x, y

    # --------------------------------------------------------------------
    # 2. Bangun dokumen DXF: layer table + block symbol + isi modelspace.
    # --------------------------------------------------------------------
    doc = ezdxf.new(dxfversion="R2010")
    doc.header["$INSUNITS"] = 6  # 6 = meter

    for layer_name, color in LAYER_COLORS.items():
        layer = doc.layers.new(name=layer_name)
        layer.dxf.color = color

    # ROAD & BUILDING sengaja dibuat KOSONG (tidak ada sumber data untuk ini
    # di flow survey saat ini) - hanya disiapkan sebagai layer supaya
    # drafter CAD tinggal gambar manual di atasnya kalau perlu.

    build_blocks(doc)

    msp = doc.modelspace()

    points_written = 0
    unclassified_points = 0

    # Hitung dulu semua koordinat UTM titik sebelum digambar, supaya
    # compute_label_anchors() bisa mendeteksi titik-titik yang lokasinya
    # nyaris sama (lihat komentar CLUSTER_RADIUS_M di atas) dan menyebar
    # posisi labelnya supaya tidak menumpuk.
    point_utm_coords = [to_utm(point["lat"], point["lng"]) for point in points]
    label_anchors = compute_label_anchors(point_utm_coords)

    for point, (x, y), (anchor_x, anchor_y, needs_leader) in zip(points, point_utm_coords, label_anchors):
        point_type = point.get("type", "unknown")
        layer_name, block_name = resolve_layer_and_block(point_type, custom_layers)

        if layer_name == FALLBACK_LAYER:
            unclassified_points += 1

        msp.add_blockref(block_name, insert=(x, y), dxfattribs={"layer": layer_name})

        label = point.get("name") or point.get("ref") or ""
        if label:
            if needs_leader:
                # Garis tipis penghubung simbol -> posisi label yang sudah
                # disebar, supaya tetap jelas label itu punya titik yang mana.
                msp.add_line(
                    (x, y),
                    (anchor_x, anchor_y),
                    dxfattribs={"layer": layer_name, "color": LEADER_LINE_COLOR},
                )

            msp.add_text(
                str(label),
                dxfattribs={
                    "layer": layer_name,
                    "height": TEXT_HEIGHT,
                    "insert": (anchor_x, anchor_y),
                },
            )

        points_written += 1

    polylines_written = 0

    for line in polylines:
        coords = line.get("coordinates") or []
        if len(coords) < 2:
            continue

        utm_points = [to_utm(lat, lng) for lat, lng in coords]

        msp.add_lwpolyline(utm_points, dxfattribs={"layer": "FTTX_CABLE"})

        label = line.get("name")
        if label:
            lx, ly = utm_points[0]
            msp.add_text(
                str(label),
                dxfattribs={
                    "layer": "FTTX_CABLE",
                    "height": TEXT_HEIGHT,
                    "insert": (lx + TEXT_OFFSET_X, ly + TEXT_OFFSET_Y),
                },
            )

        polylines_written += 1

    doc.saveas(args.output)

    print(json.dumps({
        "success": True,
        "utm_zone": zone_label,
        "epsg": epsg,
        "points_written": points_written,
        "polylines_written": polylines_written,
        "unclassified_points": unclassified_points,
    }))


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:  # noqa: BLE001 - sengaja tangkap semua, ini boundary proses subprocess
        print(json.dumps({"success": False, "error": str(exc)}))
        sys.exit(1)
