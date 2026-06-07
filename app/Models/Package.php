<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'packages';
    protected $primaryKey = 'id_package';

    protected $fillable = [
        'package_code',
        'package_name',
        'description',
    ];

    public function prices()
    {
        return $this->hasMany(DesignatorPackagePrice::class, 'package_id', 'id_package');
    }
}