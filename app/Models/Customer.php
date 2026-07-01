<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $primaryKey = 'id_customer';

    protected $fillable = [
        'customer_code',
        'customer_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'customer_id', 'id_customer');
    }

    public function designators()
    {
        return $this->hasMany(Designator::class, 'customer_id', 'id_customer');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    /**
     * Customer default (TIF)
     */
    public static function default()
    {
        return static::where('customer_code', 'TIF')->first();
    }

    /**
     * Ambil ID customer default (TIF)
     */
    public static function defaultId()
    {
        return optional(static::default())->id_customer;
    }

    /**
     * Cari customer berdasarkan code
     */
    public static function byCode(string $code)
    {
        return static::where('customer_code', strtoupper($code))->first();
    }

    /**
     * Shortcut Customer TIF
     */
    public static function tif()
    {
        return static::byCode('TIF');
    }
}
