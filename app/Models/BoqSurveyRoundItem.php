<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot 1 baris boq_items pada saat 1 ronde BOQ Survey (BoqSurveyRound)
 * selesai. Lihat catatan lengkap di migration
 * 2026_09_10_140000_create_boq_survey_rounds_tables.
 */
class BoqSurveyRoundItem extends Model
{
    protected $fillable = [
        'boq_survey_round_id',
        'boq_item_id',
        'designator_id',
        'designator',
        'item_name',
        'unit',
        'quantity_plan',
        'quantity_survey',
    ];

    protected $casts = [
        'quantity_plan' => 'float',
        'quantity_survey' => 'float',
    ];

    public function round()
    {
        return $this->belongsTo(BoqSurveyRound::class, 'boq_survey_round_id');
    }

    public function boqItem()
    {
        return $this->belongsTo(BoqItem::class, 'boq_item_id', 'id_boq');
    }
}
