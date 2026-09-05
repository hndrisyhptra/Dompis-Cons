<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabel master role — pengganti kolom users.role yang sebelumnya ENUM.
 * Lihat migration create_roles_table & seed_roles_table untuk daftar awal
 * 9 role, dan App\Models\User::role() untuk cara pemakaian di kode lama
 * yang masih membaca $user->role sebagai string.
 */
class Role extends Model
{
    protected $table = 'roles';

    protected $primaryKey = 'id_roles';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'id_roles');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'id_roles');
    }
}
