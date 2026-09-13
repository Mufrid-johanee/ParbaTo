<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(
        string $action,
        ?User $user = null,
        ?Model $subject = null,
        ?array $meta = null
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 512) ?: null,
        ]);
    }
}
