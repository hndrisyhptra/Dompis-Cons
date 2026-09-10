<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status per-item pengukuran (OTDR/FILE SOR/OPM/Kedalaman Galian/Eviden
 * Lainnya) untuk 1 LOP -- 1 baris per item, upsert lewat unique key
 * (lop_id, item_key). "Beres" berarti ada evidence_id ATAU
 * is_not_applicable=true.
 */
class LopMeasurementCheck extends Model
{
    public const ITEMS = ['otdr', 'file_sor', 'opm', 'kedalaman', 'eviden_lainnya'];

    public const LABELS = [
        'otdr' => 'OTDR',
        'file_sor' => 'File SOR',
        'opm' => 'OPM',
        'kedalaman' => 'Kedalaman Galian',
        'eviden_lainnya' => 'Eviden Lainnya',
    ];

    protected $fillable = [
        'lop_id',
        'item_key',
        'is_not_applicable',
        'note',
        'evidence_id',
        'checked_by',
    ];

    protected $casts = [
        'is_not_applicable' => 'boolean',
    ];

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id', 'id_lop');
    }

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidence_id', 'id_evidence');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by', 'id_user');
    }

    public function isDone(): bool
    {
        return $this->is_not_applicable || $this->evidence_id !== null;
    }
}
