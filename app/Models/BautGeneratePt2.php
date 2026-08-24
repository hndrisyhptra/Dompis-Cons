<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BautGeneratePt2 extends Model
{
    protected $table = 'baut_generates';
    protected $primaryKey = 'id_baut_generate';
    protected $guarded = [];

    protected $casts = [
        'field_values' => 'array',
        'boq_snapshot' => 'array',
        'photo_slots' => 'array',
        'generated_at' => 'datetime',
    ];

    public function lop()
    {
        return $this->belongsTo(Pt2Lop::class, 'pt2_lop_id', 'id_pt2_lop');
    }

    public function project()
    {
        return $this->belongsTo(Pt2Project::class, 'pt2_project_id', 'id_pt2_project');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by', 'id_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id_user');
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }
}
