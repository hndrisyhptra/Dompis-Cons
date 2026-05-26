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
    ];
}