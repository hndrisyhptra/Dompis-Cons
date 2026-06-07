<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
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


}


