<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::with('user:id,name')
            ->orderByDesc('created_at');

        if ($entityType = $request->input('entity_type')) {
            $query->where('entity_type', $entityType);
        }

        if ($entityId = $request->input('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        return Inertia::render('AuditLogs/Index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only('entity_type', 'entity_id', 'action'),
        ]);
    }
}
