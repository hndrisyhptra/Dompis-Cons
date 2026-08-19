<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportProcess extends Model
{
    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    protected $table = 'import_processes';

    protected $primaryKey = 'id_import';

    public $incrementing = true;

    protected $keyType = 'int';


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';


    /*
    |--------------------------------------------------------------------------
    | IMPORT TYPE
    |--------------------------------------------------------------------------
    */

    public const TYPE_PID = 'pid';

    public const TYPE_BOQ = 'boq';


    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uuid',

        'import_type',
        'project_type',
        'customer_id',

        'original_file_name',
        'stored_file_path',
        'disk',

        'status',
        'current_stage',
        'progress',

        'total_rows',
        'processed_rows',
        'valid_rows',
        'invalid_rows',

        'created_count',
        'updated_count',
        'unchanged_count',
        'skipped_count',

        'summary',

        'error_message',

        'uploaded_by',

        'started_at',
        'finished_at',
    ];


    /*
    |--------------------------------------------------------------------------
    | CAST
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',

            'progress' => 'integer',

            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',

            'created_count' => 'integer',
            'updated_count' => 'integer',
            'unchanged_count' => 'integer',
            'skipped_count' => 'integer',

            'summary' => 'array',

            'uploaded_by' => 'integer',

            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function errors(): HasMany
    {
        return $this->hasMany(
            ImportProcessError::class,
            'import_process_id',
            'id_import'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }


    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }


    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }


    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }


    public function isFinished(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
            ],
            true
        );
    }
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'uploaded_by',
            'id_user'
        );
    }
}