<?php

namespace App\Services;

use App\Models\ProjectActivityLog;

class ProjectActivityService
{
    public static function log(array $data): ProjectActivityLog
    {
        return ProjectActivityLog::create([
            'project_id' => $data['project_id'] ?? null,
            'lop_id' => $data['lop_id'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'target_user_id' => $data['target_user_id'] ?? null,
            'evidence_id' => $data['evidence_id'] ?? null,
            'activity_type' => $data['activity_type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'stage' => $data['stage'] ?? null,
            'status_before' => $data['status_before'] ?? null,
            'status_after' => $data['status_after'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);
    }
}