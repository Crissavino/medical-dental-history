<?php

use App\Http\Controllers\AnamnesisController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\EncounterController;
use App\Http\Controllers\GdprExportController;
use App\Http\Controllers\IntakeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TreatmentController;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Inertia\Inertia;

// Public intake wizard (no auth required)
Route::middleware('throttle:intake')->group(function () {
    Route::get('/intake/{patient?}', [IntakeController::class, 'show'])->name('intake.show');
    Route::post('/intake', [IntakeController::class, 'store'])->name('intake.store');
    Route::get('/intake-complete', [IntakeController::class, 'complete'])->name('intake.complete');
});

// Home — redirect to dashboard or login
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::get('/dashboard', function () {
    $recentPatients = Patient::orderByDesc('updated_at')->limit(5)->get();
    $todayEncounters = Encounter::with('patient', 'provider:id,name')
        ->whereDate('encounter_date', today())
        ->orderBy('encounter_date')
        ->get();

    return Inertia::render('Dashboard', [
        'recentPatients' => $recentPatients,
        'todayEncounters' => $todayEncounters,
        'stats' => [
            'total_patients' => Patient::count(),
            'today_encounters' => $todayEncounters->count(),
            'pending_encounters' => Encounter::where('status', 'scheduled')->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Patients
    Route::resource('patients', PatientController::class);

    // Anamnesis (nested under patient)
    Route::get('/patients/{patient}/anamnesis/create', [AnamnesisController::class, 'create'])
        ->name('anamnesis.create');
    Route::post('/patients/{patient}/anamnesis', [AnamnesisController::class, 'store'])
        ->name('anamnesis.store');
    Route::get('/anamnesis/{anamnesisVersion}', [AnamnesisController::class, 'show'])
        ->name('anamnesis.show');
    Route::get('/anamnesis/{anamnesisVersion}/pdf', [AnamnesisController::class, 'pdf'])
        ->name('anamnesis.pdf');

    // Encounters (nested under patient for create, standalone for show/edit)
    Route::get('/patients/{patient}/encounters', [EncounterController::class, 'index'])
        ->name('patient.encounters.index');
    Route::get('/patients/{patient}/encounters/create', [EncounterController::class, 'create'])
        ->name('encounters.create');
    Route::post('/patients/{patient}/encounters', [EncounterController::class, 'store'])
        ->name('patient.encounters.store');
    Route::get('/encounters/{encounter}', [EncounterController::class, 'show'])
        ->name('encounters.show');
    Route::get('/encounters/{encounter}/edit', [EncounterController::class, 'edit'])
        ->name('encounters.edit');
    Route::put('/encounters/{encounter}', [EncounterController::class, 'update'])
        ->name('encounters.update');
    Route::delete('/encounters/{encounter}', [EncounterController::class, 'destroy'])
        ->name('encounters.destroy');

    // Treatments (nested under encounter)
    Route::post('/encounters/{encounter}/treatments', [TreatmentController::class, 'store'])
        ->name('treatments.store');
    Route::put('/treatments/{treatment}', [TreatmentController::class, 'update'])
        ->name('treatments.update');
    Route::delete('/treatments/{treatment}', [TreatmentController::class, 'destroy'])
        ->name('treatments.destroy');

    // Attachments (polymorphic)
    Route::post('/attachments', [AttachmentController::class, 'store'])
        ->name('attachments.store');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])
        ->name('attachments.show');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');

    // GDPR Export
    Route::get('/patients/{patient}/gdpr-export', [GdprExportController::class, 'export'])
        ->name('patients.gdpr-export');

    // Audit Logs (admin only)
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('role:admin')
        ->name('audit-logs.index');
});

require __DIR__.'/auth.php';
