<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pt2Survey extends Model
{
    use HasFactory;

    // 1. TAMBAHKAN BARIS INI UNTUK MEMBERITAHU LARAVEL NAMA PRIMARY KEY-NYA
    protected $primaryKey = 'id_pt2_survey';

    // 2. PASTIKAN SEMUA KOLOM YANG KITA GUNAKAN ADA DI DALAM $fillable
    protected $fillable = [
        'project_id',
        'has_kendala',
        'kendala_note',
        'pm_approval_status',
        'mode',
        'sub_mode_a',
        'odp_name',
        'distribusi',
        'core_ex',
        'power_out',
        'power_in_feeder',
        'tipe_kabel',
        'kesimpulan',
        'detail_data',
    ];

    // (Relasi lain jika ada biarkan saja di bawah sini)
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }
}