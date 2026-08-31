# GIS to CAD Generator - Python Worker

Worker Python untuk fitur "GIS to CAD Generator" (KML/KMZ survey -> DXF
AutoCAD). Dipanggil otomatis dari Laravel lewat `PythonDxfWorkerService`,
tidak untuk diakses user langsung.

## PENTING: belum pernah dites end-to-end

Script `gis_to_dxf.py` ditulis mengikuti API resmi `ezdxf` dan `pyproj` yang
sudah stabil bertahun-tahun, tapi **belum bisa dieksekusi/dites** oleh Claude
di sesi ini karena dua alasan:

1. Sandbox cloud Claude tidak punya akses network ke PyPI (`pip install`
   gagal dengan `host_not_allowed`).
2. `device_bash` (shell ke komputer Anda) juga tidak punya akses network.

Jadi **wajib** dites manual sebelum dipakai user beneran. Caranya di bawah.
Kalau ada error, kirim output lengkapnya (stdout + stderr) supaya bisa
diperbaiki.

## 1. Instalasi

```bash
cd python-worker
python3 -m venv venv
source venv/bin/activate        # Windows: venv\Scripts\activate
pip install -r requirements.txt
```

Atau tanpa virtualenv (langsung ke Python sistem):

```bash
pip3 install -r python-worker/requirements.txt
```

## 2. Smoke test manual

Buat file `test-dataset.json` (contoh dataset kecil, 1 tiang + 1 ODP + 1
jalur kabel, lokasi contoh di sekitar Surabaya):

```json
{
  "points": [
    {"ref": "p1", "type": "tiang", "name": "Tiang-01", "lat": -7.257472, "lng": 112.752090},
    {"ref": "p2", "type": "odp", "name": "ODP-03", "lat": -7.257600, "lng": 112.752300},
    {"ref": "p3", "type": "otb", "name": "OTB-01", "lat": -7.257700, "lng": 112.752400},
    {"ref": "p4", "type": "jc", "name": "JC-01", "lat": -7.257800, "lng": 112.752500}
  ],
  "polylines": [
    {"ref": "r1", "type": "cable", "name": "Rute Kabel 1",
     "coordinates": [[-7.257472, 112.752090], [-7.257600, 112.752300], [-7.257700, 112.752400]]}
  ],
  "template": "standard_fttx"
}
```

Jalankan:

```bash
python3 gis_to_dxf.py --input test-dataset.json --output test-output.dxf --template standard_fttx
```

**Yang harus dicek:**

- Exit code harus `0` (`echo $?` setelah menjalankan).
- Output di terminal harus 1 baris JSON: `{"success": true, "utm_zone": "49S", "epsg": 32749, "points_written": 4, "polylines_written": 1, "unclassified_points": 0}`
  (utm_zone untuk lokasi Indonesia biasanya antara `46N`/`46S` sampai `54N`/`54S`
  tergantung wilayah - `49S` di atas contoh untuk area Jawa Timur).
- File `test-output.dxf` harus terbentuk dan bisa dibuka di AutoCAD (atau
  viewer DXF gratis seperti [ODA File Converter](https://www.opendesign.com/guestfiles)
  / [LibreCAD](https://librecad.org/) kalau tidak ada AutoCAD).
- Di dalam DXF, cek panel Layers: harus ada `FTTX_POLE`, `FTTX_ODP`,
  `FTTX_ODC`, `FTTX_OTB`, `FTTX_JC`, `FTTX_ENDING_SITE`, `FTTX_CABLE`,
  `ROAD`, `BUILDING` (2 terakhir boleh kosong, itu memang disengaja).
- Koordinat objek harus dalam rentang **meter kecil yang masuk akal**
  (misal ratusan-ribuan, BUKAN angka seperti -7.xx / 112.xx yang berarti
  transformasi UTM-nya gagal/tidak jalan).
- Jalur kabel harus muncul sebagai garis (LWPOLYLINE) yang menghubungkan
  titik-titik sesuai urutan `coordinates`.

## 3. Kalau gagal

Jalankan ulang dengan Python langsung (bukan lewat PHP) supaya traceback
lengkap kelihatan:

```bash
python3 gis_to_dxf.py --input test-dataset.json --output test-output.dxf --template standard_fttx
```

Kirim seluruh output (termasuk traceback kalau ada) untuk diperbaiki.

## 4. Setelah lolos smoke test

Set path python3 yang benar di `.env` server (lihat `GIS_CAD_PYTHON_BIN` di
`.env.example`) - kalau pakai virtualenv, arahkan ke binary python di dalam
venv tsb, misal:

```
GIS_CAD_PYTHON_BIN=/path/ke/project/python-worker/venv/bin/python3
```
