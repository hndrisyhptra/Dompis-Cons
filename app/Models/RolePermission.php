<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SCAFFOLD — lihat migration create_role_permissions_table untuk catatan
 * lengkap. Model ini belum dipakai logic otorisasi manapun di aplikasi.
 */
class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'permission',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id_roles');
    }
}
