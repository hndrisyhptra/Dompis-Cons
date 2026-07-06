<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Designator extends Model
{
    protected $table = 'designators';

    protected $primaryKey = 'id_designator';

    protected $fillable = [
        'customer_id',
        'designator',
        'item_name',
        'unit',
        'type',
        'pair_code',
        'progress_category',
        'requires_finishing_evidence',
    ];

    protected static function booted(): void
    {
        static::creating(function (Designator $designator) {
            if ($designator->customer_id) {
                return;
            }

            $designator->customer_id = Customer::where('customer_code', 'TIF')
                ->value('id_customer');
        });
    }

    public function scopeForCustomer(Builder $query, ?int $customerId): Builder
    {
        if (! $customerId) {
            return $query;
        }

        return $query->where('customer_id', $customerId);
    }
    

    public function prices()
    {
        return $this->hasMany(DesignatorPackagePrice::class, 'designator_id', 'id_designator');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id_customer');
    }
}
