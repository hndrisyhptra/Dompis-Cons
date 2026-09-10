<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Upload admin di step FI-OGP Golive: capture valins, PDF ABD & Valid4,
 * KML, Mancore (foto capture ATAU excel -- lihat migration
 * create_lop_golive_submissions_table untuk alasan kenapa bukan pakai
 * tabel relasional mancore).
 */
class LopGoliveSubmission extends Model
{
    protected $fillable = [
        'lop_id',
        'capture_valins_path',
        'abd_valid4_path',
        'kml_path',
        'mancore_path',
        'mancore_input_type',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
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
     * Syarat FI-OGP Golive lengkap = 4 file ini semua terisi.
     */
    public function isComplete(): bool
    {
        return $this->capture_valins_path
            && $this->abd_valid4_path
            && $this->kml_path
            && $this->mancore_path;
    }
}
