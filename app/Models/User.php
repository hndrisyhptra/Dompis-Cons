<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    protected $table = 'users';

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'nik',
        'name',
        'username',
        'password',
        'role',
        'role_id',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function assignments()
        {
            return $this->hasMany(
                ProjectAssignment::class,
                'waspang_id',
                'id_user'
            );
        }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function siteSurveys()
    {
        return $this->hasMany(SiteSurvey::class, 'surveyor_id', 'id_user');
    }

    /**
     * Relasi ke tabel roles (pengganti kolom role ENUM lama).
     */
    public function roleRef(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id_roles');
    }

    /**
     * Accessor/mutator kompatibilitas: SELURUH kode lama (controller, Blade,
     * command) yang membaca $user->role sebagai string kode role (mis.
     * 'waspang', 'super_tif') TETAP BERFUNGSI tanpa perubahan, karena secara
     * transparan dialihkan ke relasi roleRef.
     *
     * - GET: ambil kode dari roleRef; fallback ke kolom `role` ENUM lama
     *   kalau role_id belum ke-set (mis. untuk baris lama yang belum sempat
     *   di-backfill, atau environment yang migration-nya belum jalan).
     * - SET: assignment seperti `$user->role = 'waspang'` atau mass-assign
     *   lewat $fillable (`User::create(['role' => 'waspang', ...])`) akan
     *   dicari role_id yang cocok lalu HANYA role_id yang ditulis -- kolom
     *   `role` ENUM fisik SENGAJA tidak disentuh lagi mulai sekarang (lihat
     *   catatan di migration add_role_id_to_users_table).
     *
     * Query builder (User::where('role', 'x'), whereIn('role', [...])) TIDAK
     * ikut lewat accessor ini karena beroperasi di level SQL -- titik-titik
     * itu sudah diganti eksplisit memakai scopeRole()/whereHas('roleRef', ...)
     * di seluruh controller yang bersangkutan.
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->roleRef?->code ?? $value,
            set: function ($value) {
                if ($value === null || $value === '') {
                    return ['role_id' => null];
                }

                return ['role_id' => Role::where('code', $value)->value('id_roles')];
            },
        );
    }

    /**
     * Query scope: User::roleCode('waspang') atau User::roleCode(['admin','pm']).
     * Pengganti User::where('role', ...)/whereIn('role', [...]) yang query
     * langsung ke kolom ENUM lama.
     *
     * CATATAN PENTING: scope ini SENGAJA TIDAK dinamai "role" (yang akan
     * dipanggil sebagai User::role(...)) -- itu bentrok dengan method
     * accessor role() di atas. Static call User::xxx(...) yang tidak
     * dikenali diteruskan Eloquent langsung ke method instance bernama sama
     * (lihat Model::__callStatic), BUKAN ke sistem local scope (yang baru
     * jalan lewat Model::__call/Builder::__call). Karena User sudah punya
     * method asli bernama role() (accessor di atas), User::role(...) akan
     * memanggil method itu langsung -- bukan scopeRole() -- dan mengembalikan
     * objek Attribute, bukan query builder (ini yang menyebabkan error
     * "Call to undefined method Attribute::where()" / "Attribute::get()"
     * saat scope masih bernama scopeRole).
     */
    public function scopeRoleCode($query, $codes)
    {
        return $query->whereHas('roleRef', function ($q) use ($codes) {
            $q->whereIn('code', (array) $codes);
        });
    }
}
