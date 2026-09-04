<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramWebhookEvent extends Model
{
    protected $primaryKey = 'id_tele_webhook';

    protected $fillable = [
        'event_type',
        'recipient_type',
        'recipient_user_id',
        'recipient_role',
        'project_id',
        'lop_id',
        'title',
        'message',
        'payload',
        'status',
        'delivered_at',
        'pushed_at',
        'push_attempts',
        'push_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'pushed_at' => 'datetime',
    ];

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id', 'id_user');
    }
}
