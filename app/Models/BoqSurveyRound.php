<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 1 ronde BOQ Survey per LOP (round 1 = Survey pertama, round 2+ = hasil
 * tombol "Re Survey"). Lihat catatan lengkap di migration
 * 2026_09_10_140000_create_boq_survey_rounds_tables.
 */
class BoqSurveyRound extends Model
{
    protected $fillable = [
        'lop_id',
        'round_number',
        'status',
        'plan_total',
        'survey_total',
        'deviation_percent',
        'redesign_required',
        'started_by',
        'started_at',
        'finished_by',
        'finished_at',
        'note',
    ];

    protected $casts = [
        'redesign_required' => 'boolean',
        'plan_total' => 'float',
        'survey_total' => 'float',
        'deviation_percent' => 'float',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id', 'id_lop');
    }

    public function items()
    {
        return $this->hasMany(BoqSurveyRoundItem::class, 'boq_survey_round_id');
    }

    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by', 'id_user');
    }

    public function finisher()
    {
        return $this->belongsTo(User::class, 'finished_by', 'id_user');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}
