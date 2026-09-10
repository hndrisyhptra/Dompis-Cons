<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Verifikasi SDI di step Golive: upload capture UIM + siapa & kapan
 * memverifikasi. Begitu baris ini ada (capture_uim_path terisi), LOP boleh
 * di-toggle jadi status_progress 'golive'.
 */
class LopGoliveVerification extends Model
{
    protected $fillable = [
        'lop_id',
        'capture_uim_path',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id', 'id_lop');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id_user');
    }
}
