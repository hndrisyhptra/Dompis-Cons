<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master daftar flagging kendala, dipakai di semua step (bukan cuma
 * Persiapan). Dikelola admin lewat UI Stage 3.
 */
class KendalaCategory extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
