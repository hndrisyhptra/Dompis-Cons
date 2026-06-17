<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designator extends Model
{
    protected $table = 'designators';

    protected $primaryKey = 'id_designator';

    protected $fillable = [
        'designator',
        'item_name',
        'unit',
        'type',
        'pair_code',
        'requires_finishing_evidence',
    ];
    

    public function prices()
    {
        return $this->hasMany(DesignatorPackagePrice::class, 'designator_id', 'id_designator');
    }
}

