<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', [
            'new' => $model->getAttributes(),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $this->log($model, 'updated', [
            'old' => array_intersect_key($model->getOriginal(), $changes),
            'new' => $changes,
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', [
            'old' => $model->getOriginal(),
        ]);
    }

    private function log(Model $model, string $action, array $metadata): void
    {
        AuditLog::create([
            'entity_type' => $model->getMorphClass(),
            'entity_id' => $model->getKey(),
            'user_id' => Auth::id(),
            'action' => $action,
            'metadata_json' => $metadata,
            'ip_address' => Request::ip(),
        ]);
    }
}
