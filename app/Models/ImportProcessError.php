<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportProcessError extends Model
{
    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    |
    | Mengikuti nama tabel yang tadi Anda buat:
    |
    | import_processes_errors
    |
    */

    protected $table = 'import_processes_errors';

    protected $primaryKey = 'id_error';

    public $incrementing = true;

    protected $keyType = 'int';


    /*
    |--------------------------------------------------------------------------
    | TIMESTAMP
    |--------------------------------------------------------------------------
    |
    | Tabel hanya memiliki created_at.
    | Tidak memiliki updated_at.
    |
    */

    const UPDATED_AT = null;


    /*
    |--------------------------------------------------------------------------
    | ERROR CODE
    |--------------------------------------------------------------------------
    */

    public const MISSING_REQUIRED_FIELD = 'missing_required_field';

    public const DUPLICATE_DATA = 'duplicate_data';

    public const DUPLICATE_LOP = 'duplicate_lop';

    public const INVALID_DATE = 'invalid_date';

    public const PROJECT_NOT_FOUND = 'project_not_found';

    public const LOP_NOT_FOUND = 'lop_not_found';

    public const INVALID_DATA = 'invalid_data';

    public const SYSTEM_ERROR = 'system_error';


    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'import_process_id',

        'row_number',

        'pid_sap',
        'id_ihld',
        'nama_lop',

        'error_code',

        'message',

        'row_data',
    ];


    /*
    |--------------------------------------------------------------------------
    | CAST
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'import_process_id' => 'integer',

            'row_number' => 'integer',

            'row_data' => 'array',

            'created_at' => 'datetime',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function importProcess(): BelongsTo
    {
        return $this->belongsTo(
            ImportProcess::class,
            'import_process_id',
            'id_import'
        );
    }
}