<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $primaryKey = 'id_notification';

    protected $fillable = [
        'user_id',
        'project_id',
        'type',
        'title',
        'message',
        'redirect_url',
        'is_read',
        'read_at',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}