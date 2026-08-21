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
        'execution_type',
        'status_project',
        'latitude',
        'longitude',
        'location_address',
        'map_note',
        'status',
        'sdi_approval_status',
        'is_golive',
        'golive_evidence_path',
        'golive_at'
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
    
    protected ?array $progressSummaryCache = null;
    public function progressSummary(): array
    {
        // Jangan hitung ulang project yang sama dalam request yang sama
        if ($this->progressSummaryCache !== null) {
            return $this->progressSummaryCache;
        }
    
        // Jika controller sudah eager-load, ini tidak menjalankan query tambahan.
        // Jika belum, Laravel akan mengambil relasi yang dibutuhkan.
        $this->loadMissing([
            'evidences',
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
        ]);
    
        $evidences = $this->evidences;
        $boqItems  = $this->boqItems;
    
        /*
        |--------------------------------------------------------------------------
        | 1. Scan Evidence SATU KALI
        |--------------------------------------------------------------------------
        */
    
        $barangTibaApproved = false;
        $perizinanApproved  = false;
    
        $instalasiStats = [];
        $finishingStats = [];
    
        foreach ($evidences as $evidence) {
    
            // Persiapan
            if (
                $evidence->stage === 'persiapan' &&
                $evidence->status === 'approved'
            ) {
                if ($evidence->evidence_type === 'barang_tiba') {
                    $barangTibaApproved = true;
                }
    
                if ($evidence->evidence_type === 'perizinan') {
                    $perizinanApproved = true;
                }
            }
    
            $boqItemId = $evidence->boq_item_id;
    
            if (!$boqItemId) {
                continue;
            }
    
            $boqKey = (string) $boqItemId;
    
            // Evidence instalasi
            if (
                $evidence->stage === 'instalasi' &&
                $evidence->evidence_type === 'progress_boq'
            ) {
                if (!isset($instalasiStats[$boqKey])) {
                    $instalasiStats[$boqKey] = [
                        'total' => 0,
                        'approved' => 0,
                    ];
                }
    
                $instalasiStats[$boqKey]['total']++;
    
                if ($evidence->status === 'approved') {
                    $instalasiStats[$boqKey]['approved']++;
                }
            }
    
            // Evidence finishing
            if ($evidence->stage === 'finishing') {
                if (!isset($finishingStats[$boqKey])) {
                    $finishingStats[$boqKey] = [
                        'total' => 0,
                        'approved' => 0,
                    ];
                }
    
                $finishingStats[$boqKey]['total']++;
    
                if ($evidence->status === 'approved') {
                    $finishingStats[$boqKey]['approved']++;
                }
            }
        }
    
        /*
        |--------------------------------------------------------------------------
        | 2. Scan BOQ SATU KALI
        |--------------------------------------------------------------------------
        */
    
        $materialIds = [];
        $finishingRequiredIds = [];
    
        foreach ($boqItems as $boq) {
    
            // Hanya material M-
            if (!str_starts_with((string) ($boq->designator ?? ''), 'M-')) {
                continue;
            }
    
            $boqKey = (string) $boq->id_boq;
    
            $materialIds[] = $boqKey;
    
            $requiresFinishing =
                (int) optional($boq->designatorData)->requires_finishing_evidence === 1
                ||
                (int) optional($boq->designatorDataByCode)->requires_finishing_evidence === 1;
    
            if ($requiresFinishing) {
                $finishingRequiredIds[] = $boqKey;
            }
        }
    
        /*
        |--------------------------------------------------------------------------
        | 3. Persiapan
        |--------------------------------------------------------------------------
        */
    
        $persiapanDone =
            $barangTibaApproved &&
            $perizinanApproved;
    
        /*
        |--------------------------------------------------------------------------
        | 4. Instalasi
        |--------------------------------------------------------------------------
        */
    
        $materialTotal = count($materialIds);
        $instalasiApproved = 0;
    
        foreach ($materialIds as $boqKey) {
    
            $stats = $instalasiStats[$boqKey] ?? null;
    
            if (
                $stats &&
                $stats['total'] > 0 &&
                $stats['approved'] === $stats['total']
            ) {
                $instalasiApproved++;
            }
        }
    
        $instalasiDone =
            $materialTotal > 0 &&
            $instalasiApproved >= $materialTotal;
    
        /*
        |--------------------------------------------------------------------------
        | 5. Pengukuran
        |--------------------------------------------------------------------------
        */
    
        // Step 3 tidak wajib upload
        $pengukuranDone = $instalasiDone;
    
        /*
        |--------------------------------------------------------------------------
        | 6. Finishing
        |--------------------------------------------------------------------------
        */
    
        $finishingTotal = count($finishingRequiredIds);
        $finishingApproved = 0;
    
        foreach ($finishingRequiredIds as $boqKey) {
    
            $stats = $finishingStats[$boqKey] ?? null;
    
            if (
                $stats &&
                $stats['total'] > 0 &&
                $stats['approved'] === $stats['total']
            ) {
                $finishingApproved++;
            }
        }
    
        $finishingDone =
            $persiapanDone &&
            $instalasiDone &&
            $pengukuranDone &&
            (
                $finishingTotal === 0 ||
                $finishingApproved >= $finishingTotal
            );
    
        /*
        |--------------------------------------------------------------------------
        | 7. Progress
        |--------------------------------------------------------------------------
        */
    
        $doneStep =
            (int) $persiapanDone +
            (int) $instalasiDone +
            (int) $pengukuranDone +
            (int) $finishingDone;
    
        $progress = (int) round(($doneStep / 4) * 100);
    
        $stageLabel = match (true) {
            $finishingDone => 'Ready UT',
            $instalasiDone => 'Pengukuran',
            $persiapanDone => 'Instalasi',
            default => 'Persiapan',
        };
    
        return $this->progressSummaryCache = [
            'persiapanDone' => $persiapanDone,
            'instalasiDone' => $instalasiDone,
            'pengukuranDone' => $pengukuranDone,
            'finishingDone' => $finishingDone,
            'materialTotal' => $materialTotal,
            'instalasiApproved' => $instalasiApproved,
            'finishingApproved' => $finishingApproved,
            'finishingTotal' => $finishingTotal,
            'progress' => $progress,
            'stageLabel' => $stageLabel,
        ];
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

    public function pt2Survey()
    {
        return $this->hasOne(Pt2Survey::class, 'project_id', 'id_project');
    }

    public function pt2Mancore()
    {
        return $this->hasOne(Pt2Mancore::class, 'project_id', 'id_project');
    }

    public function siteSurveys()
    {
        return $this->hasMany(SiteSurvey::class, 'project_id', 'id_project');
    }
}
