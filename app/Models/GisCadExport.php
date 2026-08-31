<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GisCadExport extends Model
{
    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    protected $table = 'gis_cad_exports';

    protected $primaryKey = 'id_gis_cad_export';

    public $incrementing = true;

    protected $keyType = 'int';


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    /**
     * Dataset sudah di-parse (dari upload KML/KMZ atau dari data survey
     * existing) & disimpan, tapi belum di-generate ke DXF - user masih
     * bisa review/koreksi klasifikasi titik & pilih template export.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';


    /*
    |--------------------------------------------------------------------------
    | SOURCE TYPE
    |--------------------------------------------------------------------------
    */

    public const SOURCE_KML_UPLOAD = 'kml_upload';

    public const SOURCE_SITE_SURVEY = 'site_survey';


    /*
    |--------------------------------------------------------------------------
    | TEMPLATE
    |--------------------------------------------------------------------------
    */

    public const TEMPLATE_STANDARD_FTTX = 'standard_fttx';

    public const TEMPLATE_CUSTOM = 'custom';


    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uuid',

        'source_type',
        'site_survey_id',
        'project_id',
        'template',

        'original_file_name',
        'uploaded_file_path',

        'dataset_path',

        'dxf_path',
        'bom_path',
        'disk',

        'status',
        'current_stage',

        'points_count',
        'polylines_count',
        'utm_zone',

        'error_message',

        'requested_by',

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
            'site_survey_id' => 'integer',
            'project_id' => 'integer',

            'points_count' => 'integer',
            'polylines_count' => 'integer',

            'requested_by' => 'integer',

            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SiteSurvey::class, 'site_survey_id', 'id_site_surveys');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id_user');
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

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
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
