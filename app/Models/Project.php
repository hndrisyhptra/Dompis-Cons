<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    protected $primaryKey = 'id_project';

    protected $fillable = [
        'pid',
        'customer_id',
        'pid_sap',
        'project_name',
        'program',
        'branch',
        'sto',
        'mitra_name',
        'kml_file',
        'jenis_eksekusi',
        'execution_type',
        'status',
        'status_project',
        'latitude',
        'longitude',
        'location_address',
        'map_note'
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if ($project->customer_id) {
                return;
            }

            $project->customer_id = Customer::where('customer_code', 'TIF')
                ->value('id_customer');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function boqItems()
    {
        return $this->hasMany(BoqItem::class, 'project_id', 'id_project');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id_customer');
    }

    public function assignment()
    {
        return $this->hasOne(
            ProjectAssignment::class,
            'project_id',
            'id_project'
        );
    }

    public function assignments()
    {
        return $this->hasMany(
            ProjectAssignment::class,
            'project_id',
            'id_project'
        );
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'project_id', 'id_project');
    }

    public function lop()
    {
        return $this->hasOne(Lop::class, 'project_id', 'id_project');
    }

    public function progressSummary(): array
    {
        $evidences = $this->evidences ?? collect();
        $boqItems = $this->boqItems ?? collect();

        $materialBoqItems = $boqItems->filter(fn ($boq) =>
            str_starts_with($boq->designator, 'M-')
        );

        $materialTotal = $materialBoqItems->count();

        $persiapanDone =
            $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'barang_tiba')
                ->where('status', 'approved')
                ->count() > 0
            &&
            $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'perizinan')
                ->where('status', 'approved')
                ->count() > 0;

        $instalasiApproved = $materialBoqItems->filter(function ($boq) use ($evidences) {
            $items = $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq);

            return $items->count() > 0
                && $items->where('status', 'pending')->count() == 0
                && $items->where('status', 'rejected')->count() == 0
                && $items->where('status', 'approved')->count() == $items->count();
        })->count();

        $instalasiDone = $materialTotal > 0 && $instalasiApproved >= $materialTotal;

        // Step 3 tidak wajib upload
        $pengukuranDone = $instalasiDone;

        $finishingRequiredItems = $materialBoqItems->filter(function ($boq) {
            return optional($boq->designatorData)->requires_finishing_evidence == 1
                || optional($boq->designatorDataByCode)->requires_finishing_evidence == 1;
        });

        $finishingTotal = $finishingRequiredItems->count();

        $finishingApproved = $finishingRequiredItems->filter(function ($boq) use ($evidences) {
            $items = $evidences
                ->where('stage', 'finishing')
                ->where('boq_item_id', $boq->id_boq);

            return $items->count() > 0
                && $items->where('status', 'pending')->count() == 0
                && $items->where('status', 'rejected')->count() == 0
                && $items->where('status', 'approved')->count() == $items->count();
        })->count();

       $finishingDone =
        $persiapanDone &&
        $instalasiDone &&
        $pengukuranDone &&
        (
            $finishingTotal == 0 ||
            $finishingApproved >= $finishingTotal
        );

        $doneStep = collect([
            $persiapanDone,
            $instalasiDone,
            $pengukuranDone,
            $finishingDone,
        ])->filter()->count();

        $progress = round(($doneStep / 4) * 100);
        
        $stageLabel = match (true) {
            $finishingDone => 'Ready UT',
            $instalasiDone => 'Pengukuran',
            $persiapanDone => 'Instalasi',
            default => 'Persiapan',
        };
        return compact(
            'persiapanDone',
            'instalasiDone',
            'pengukuranDone',
            'finishingDone',
            'materialTotal',
            'instalasiApproved',
            'finishingApproved',
            'finishingTotal',
            'progress',
            'stageLabel'
        );
    }

    public function activityLogs()
    {
        return $this->hasMany(ProjectActivityLog::class, 'project_id', 'id_project')
            ->latest();
    }

    public function issues()
    {
        return $this->hasMany(\App\Models\ProjectIssue::class, 'project_id', 'id_project');
    }
}
