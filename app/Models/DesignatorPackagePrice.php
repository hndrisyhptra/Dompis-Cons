<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignatorPackagePrice extends Model
{
    protected $table = 'designator_package_prices';
    protected $primaryKey = 'id_price';

    protected $fillable = [
        'designator_id',
        'package_id',
        'price',
    ];

    public function designator()
    {
        return $this->belongsTo(Designator::class, 'designator_id', 'id_designator');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id_package');
    }
}