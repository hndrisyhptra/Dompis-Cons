<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lop extends Model
{
    protected $table = 'lops';
    protected $primaryKey = 'id_lop';

    protected $fillable = [
        'project_id',
        'id_ihld',
        'lop_name',
        'pid_sap',
        'program_sap',
        'tematik',
        'sto',
        'branch',
        'batch',
        'no_sp',
        'tgl_sp',
        'tgl_toc',
        'tahun_order',
        'start_tgl',
        'wo_smile',
        'nilai_material',
        'nilai_jasa',
        'nilai_total',
        'odp_8',
        'odp_16',
        'total_port',
        'plan_tiang',
        'realisasi_tiang',
        'plan_kabel',
        'realisasi_kabel',
        'plan_galian',
        'real_galian',
        'status_progress',
        'nama_waspang',
        'nik_waspang',
        'nama_admin',
        'nik_admin',
        'mitra_name',
        'est_prep',
        'est_izin',
        'est_delivery',
        'est_instalasi',
        'est_golive',
        'mapping_status',
        'package_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }
}