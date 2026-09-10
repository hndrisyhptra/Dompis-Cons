<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master daftar status_progress LOP reguler (PT3). Dikelola admin lewat UI
 * (Stage 3 refactor) -- tambah status baru cukup insert baris, tidak perlu
 * migration/ubah kode.
 */
class ProjectStage extends Model
{
    protected $fillable = [
        'code',
        'label',
        'phase_group',
        'sequence',
        'color',
        'is_pause_type',
        'is_terminal',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_pause_type' => 'boolean',
        'is_terminal' => 'boolean',
        'is_active' => 'boolean',
        'sequence' => 'integer',
    ];

    /**
     * Urutan alur normal (bukan hold/drop), dipakai untuk render stepper.
     */
    public function scopeSequential($query)
    {
        return $query->where('code', '!=', 'drm')
            ->where('is_pause_type', false)
            ->where('is_terminal', false)
            ->whereNotNull('sequence')
            ->orderBy('sequence');
    }

    public function scopeActive($query)
    {
        return $query->where('code', '!=', 'drm')
            ->where('is_active', true);
    }
}
