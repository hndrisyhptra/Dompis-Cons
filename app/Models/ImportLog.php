<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'type',
        'file_name',
        'uploaded_by',
        'total_rows',
        'imported',
        'updated',
        'skipped',
        'status',
        'message',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_user');
    }
}