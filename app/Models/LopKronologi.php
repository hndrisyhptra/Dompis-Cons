<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log kronologi generik -- 1 baris per entri catatan tanggal+deskripsi,
 * diinput manual oleh Waspang lewat tombol "Update Kronologi" (ada di semua
 * step/sub-step, lihat WaspangController::storeKronologi()).
 */
class LopKronologi extends Model
{
    protected $fillable = [
        'lop_id',
        'project_id',
        'stage_code',
        'permit_category_id',
        'event_date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id', 'id_lop');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    /** Kategori perizinan yang dipilih SAAT entri ini dibuat (histori -- lihat Stage 4f). */
    public function permitCategory()
    {
        return $this->belongsTo(PermitCategory::class, 'permit_category_id', 'id');
    }

    /** Eviden foto/PDF opsional yang diupload bersamaan lewat "Add Perizinan". */
    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'lop_kronologi_id');
    }
}
