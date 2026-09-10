<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat perpindahan status_progress per LOP -- lihat catatan lengkap di
 * migration 2026_09_08_090600_create_lop_stage_histories_table.
 */
class LopStageHistory extends Model
{
    protected $fillable = [
        'lop_id',
        'stage_code',
        'entered_at',
        'completed_at',
        'completed_by',
        'note',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id', 'id_lop');
    }

    public function stage()
    {
        return $this->belongsTo(ProjectStage::class, 'stage_code', 'code');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by', 'id_user');
    }
}
