<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Upload admin di step FI-OGP Golive: capture valins, PDF ABD & Valid4,
 * KML, Mancore (foto ATAU excel -- lihat migration
 * create_lop_golive_submissions_table untuk alasan kenapa bukan pakai
 * tabel relasional mancore).
 *
 * Revisi (permintaan user): tiap kategori sekarang boleh MULTIPLE file.
 * Path disimpan di kolom *_paths (JSON array) -- kolom *_path (string)
 * lama tetap ada & otomatis disinkron ke file TERAKHIR yg diupload, murni
 * utk kompatibilitas kode/tempat lain yg masih baca kolom lama. Jangan
 * baca *_path langsung utk cek "ada file atau tidak" -- pakai
 * captureValinsFiles()/dst atau isComplete().
 */
class LopGoliveSubmission extends Model
{
    protected $fillable = [
        'lop_id',
        'capture_valins_path',
        'capture_valins_paths',
        'abd_valid4_path',
        'abd_valid4_paths',
        'kml_path',
        'kml_paths',
        'mancore_path',
        'mancore_paths',
        'mancore_input_type',
        'fi_completed_at',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'fi_completed_at' => 'datetime',
        'capture_valins_paths' => 'array',
        'abd_valid4_paths' => 'array',
        'kml_paths' => 'array',
        'mancore_paths' => 'array',
    ];

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id', 'id_lop');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id_user');
    }

    /**
     * Daftar path file utk 1 kategori (key: capture_valins/abd_valid4/
     * kml/mancore). Sumber utama kolom *_paths (array); fallback ke kolom
     * *_path lama (dibungkus jadi array 1 elemen) kalau *_paths kosong --
     * jaga-jaga data lama sebelum migration backfill / kasus race.
     */
    public function filesFor(string $key): array
    {
        $multi = $this->{$key.'_paths'};

        if (is_array($multi) && count($multi) > 0) {
            return array_values(array_filter($multi, fn ($p) => filled($p)));
        }

        $single = $this->{$key.'_path'};

        return $single ? [$single] : [];
    }

    public function captureValinsFiles(): array
    {
        return $this->filesFor('capture_valins');
    }

    public function abdValid4Files(): array
    {
        return $this->filesFor('abd_valid4');
    }

    public function kmlFiles(): array
    {
        return $this->filesFor('kml');
    }

    public function mancoreFiles(): array
    {
        return $this->filesFor('mancore');
    }

    /**
     * Syarat FI-OGP Golive lengkap = ke-4 kategori ini masing-masing sudah
     * punya MINIMAL 1 file (dulu: masing-masing WAJIB persis 1 path).
     */
    public function isComplete(): bool
    {
        return count($this->captureValinsFiles()) > 0
            && count($this->abdValid4Files()) > 0
            && count($this->kmlFiles()) > 0
            && count($this->mancoreFiles()) > 0;
    }
}
