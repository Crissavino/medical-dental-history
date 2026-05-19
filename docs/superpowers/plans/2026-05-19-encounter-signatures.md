# Encounter Signatures Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add per-encounter dual signature (patient + dentist) at completion, lock the encounter once signed, expose per-encounter and consolidated clinical history PDFs, and write explicit audit log entries for signed/rectified events.

**Architecture:** Laravel 11 backend with new fields on `encounters`, lockdown enforced through model helpers + policies + form requests, signing endpoint that captures both signatures + IP + content hash, dompdf for the two PDF variants, Inertia/Vue 3 wizard for the front-end signing flow, AuditObserver augmented with explicit `signed`/`rectified` log rows from controllers.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3 + TypeScript, Tailwind 3, MySQL 8, `barryvdh/laravel-dompdf` (PDFs), `signature_pad` npm lib (canvas capture), `vue-i18n` (EN/RO).

**Spec:** `docs/superpowers/specs/2026-05-19-encounter-signatures-design.md`

**PHP CLI binary on this machine:** `/usr/local/opt/php@8.3/bin/php` (system `php` is 7.4 from MAMP).

---

## File Structure

### New files

```
database/migrations/2026_05_19_120000_add_signature_fields_to_encounters_table.php
database/factories/PatientFactory.php
database/factories/EncounterFactory.php
database/factories/TreatmentFactory.php
app/Services/EncounterPdfService.php
app/Services/ClinicalHistoryPdfService.php
app/Http/Requests/SignEncounterRequest.php
resources/views/pdf/encounter.blade.php
resources/views/pdf/clinical-history.blade.php
resources/js/Components/SignaturePad.vue
resources/js/Pages/Encounters/SignWizard.vue
tests/Feature/EncounterSigningTest.php
tests/Feature/EncounterLockdownTest.php
tests/Feature/EncounterPdfTest.php
tests/Feature/ClinicalHistoryPdfTest.php
tests/Feature/EncounterRectificationTest.php
```

### Modified files

```
app/Models/Encounter.php                              (fillable + casts + isLocked + relations)
app/Policies/EncounterPolicy.php                      (lock checks for update/delete; add sign/rectify/downloadPdf)
app/Policies/TreatmentPolicy.php                      (lock check via parent encounter)
app/Policies/AttachmentPolicy.php                     (lock check when polymorphic parent is locked encounter)
app/Policies/PatientPolicy.php                        (add clinicalHistory ability)
app/Http/Controllers/EncounterController.php          (sign, rectify, pdf methods + locked guards in update/destroy)
app/Http/Controllers/PatientController.php            (clinicalHistory action)
app/Http/Controllers/GdprExportController.php         (include clinical-history PDF in zip)
app/Http/Requests/UpdateEncounterRequest.php          (lock guard via authorize)
app/Http/Requests/StoreEncounterRequest.php           (allow rectifies_encounter_id + status)
app/Http/Requests/StoreTreatmentRequest.php           (block create if parent encounter is locked)
app/Http/Requests/UpdateTreatmentRequest.php          (block update if parent encounter is locked)
app/Http/Requests/StoreAttachmentRequest.php          (block create when attachable is locked encounter)
app/Http/Controllers/AuditLogController.php           (no code change; the new action values are filtered client-side)
database/factories/UserFactory.php                    (add ->role(string) state helper)
routes/web.php                                        (sign, rectify, encounter pdf, clinical history pdf)
resources/js/Pages/Encounters/Editor.vue              ("Close and sign visit" button + mount SignWizard)
resources/js/Pages/Encounters/Show.vue                (signed banner, signatures block, rectify, download PDF)
resources/js/Pages/Patients/Show.vue                  (lock icon per encounter, full-history download button)
resources/js/Pages/AuditLogs/Index.vue                (signed + rectified in action filter)
resources/js/Pages/Intake/Wizard.vue                  (replace inline signature_pad usage with <SignaturePad />)
resources/js/i18n/en.json                             (new keys)
resources/js/i18n/ro.json                             (new keys)
```

---

## Conventions used throughout this plan

- **Run tests with:** `/usr/local/opt/php@8.3/bin/php artisan test --filter=<TestName>` (system `php` is too old).
- **All feature tests use `RefreshDatabase`** (existing pattern from `tests/Feature/Auth/*`).
- **Commits** are small (one per task). Trailer line at the bottom of every commit:
  ```
  Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
  ```
- **Vue files** use `<script setup lang="ts">`, Composition API, `vue-i18n`'s `useI18n()` for strings.
- **Inertia** redirects via `router` on the client and `redirect()->route(...)` on the server.

---

## Task 1: Migration + factories

**Files:**
- Create: `database/migrations/2026_05_19_120000_add_signature_fields_to_encounters_table.php`
- Create: `database/factories/PatientFactory.php`
- Create: `database/factories/EncounterFactory.php`
- Create: `database/factories/TreatmentFactory.php`
- Modify: `database/factories/UserFactory.php` (add role state)
- Modify: `app/Models/Patient.php` (add `HasFactory<PatientFactory>` annotation)
- Modify: `app/Models/Treatment.php` (add `HasFactory<TreatmentFactory>` annotation)
- Modify: `app/Models/Encounter.php` (add `HasFactory<EncounterFactory>` annotation)

- [ ] **Step 1: Create the migration**

Write `database/migrations/2026_05_19_120000_add_signature_fields_to_encounters_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->longText('patient_signature_data')->nullable()->after('status');
            $table->longText('dentist_signature_data')->nullable()->after('patient_signature_data');
            $table->timestamp('patient_signed_at')->nullable()->after('dentist_signature_data');
            $table->foreignId('dentist_signed_by')->nullable()
                  ->constrained('users')->nullOnDelete()->after('patient_signed_at');
            $table->timestamp('dentist_signed_at')->nullable()->after('dentist_signed_by');
            $table->string('signed_ip', 45)->nullable()->after('dentist_signed_at');
            $table->string('signed_hash', 64)->nullable()->after('signed_ip');
            $table->foreignId('rectifies_encounter_id')->nullable()
                  ->constrained('encounters')->nullOnDelete()->after('signed_hash');
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeign(['rectifies_encounter_id']);
            $table->dropForeign(['dentist_signed_by']);
            $table->dropColumn([
                'patient_signature_data',
                'dentist_signature_data',
                'patient_signed_at',
                'dentist_signed_by',
                'dentist_signed_at',
                'signed_ip',
                'signed_hash',
                'rectifies_encounter_id',
            ]);
        });
    }
};
```

- [ ] **Step 2: Create `PatientFactory`**

Write `database/factories/PatientFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
```

- [ ] **Step 3: Create `EncounterFactory`**

Write `database/factories/EncounterFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'provider_id' => User::factory()->role('dentist'),
            'encounter_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'chief_complaint' => fake()->sentence(),
            'clinical_notes' => fake()->paragraph(),
            'diagnosis' => fake()->sentence(),
            'status' => 'in_progress',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'patient_signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            'dentist_signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            'patient_signed_at' => now(),
            'dentist_signed_at' => now(),
            'signed_ip' => '127.0.0.1',
            'signed_hash' => str_repeat('a', 64),
        ]);
    }
}
```

- [ ] **Step 4: Create `TreatmentFactory`**

Write `database/factories/TreatmentFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Encounter;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreatmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'tooth_number' => fake()->numberBetween(11, 48),
            'treatment_code' => 'D' . fake()->numerify('####'),
            'description' => fake()->sentence(),
            'surface' => fake()->randomElement(['mesial', 'distal', 'occlusal', 'buccal', 'lingual']),
            'cost' => fake()->randomFloat(2, 50, 500),
            'status' => 'completed',
        ];
    }
}
```

- [ ] **Step 5: Add role state to `UserFactory`**

In `database/factories/UserFactory.php`, append before the closing brace of the class:

```php
    public function role(string $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }
```

- [ ] **Step 6: Add `HasFactory` annotations to models**

In `app/Models/Patient.php`, change the `use HasFactory, SoftDeletes;` line to:
```php
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory, SoftDeletes;
```

Same pattern in `app/Models/Encounter.php`:
```php
    /** @use HasFactory<\Database\Factories\EncounterFactory> */
    use HasFactory, SoftDeletes;
```

And in `app/Models/Treatment.php` (read it first to confirm structure; add the annotation above the existing `use HasFactory;`):
```php
    /** @use HasFactory<\Database\Factories\TreatmentFactory> */
```

- [ ] **Step 7: Run migration and verify factories**

Run:
```bash
/usr/local/opt/php@8.3/bin/php artisan migrate
/usr/local/opt/php@8.3/bin/php artisan tinker --execute='dump(\App\Models\Encounter::factory()->completed()->make()->toArray());'
```
Expected: migration runs; the tinker dump shows an Encounter array with all new columns populated and `status === "completed"`.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_05_19_120000_add_signature_fields_to_encounters_table.php \
        database/factories/PatientFactory.php \
        database/factories/EncounterFactory.php \
        database/factories/TreatmentFactory.php \
        database/factories/UserFactory.php \
        app/Models/Patient.php \
        app/Models/Encounter.php \
        app/Models/Treatment.php
git commit -m "$(cat <<'EOF'
Add encounter signature columns + model factories

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 2: Encounter model lockdown + relations

**Files:**
- Modify: `app/Models/Encounter.php`
- Create: `tests/Feature/EncounterLockdownTest.php`

- [ ] **Step 1: Write the failing test**

Write `tests/Feature/EncounterLockdownTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterLockdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_encounter_is_locked(): void
    {
        $encounter = Encounter::factory()->completed()->create();
        $this->assertTrue($encounter->isLocked());
    }

    public function test_in_progress_encounter_is_not_locked(): void
    {
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        $this->assertFalse($encounter->isLocked());
    }

    public function test_cancelled_encounter_is_locked_for_edits(): void
    {
        $encounter = Encounter::factory()->create(['status' => 'cancelled']);
        $this->assertTrue($encounter->isLocked());
    }

    public function test_rectifier_relation_returns_rectifying_encounter(): void
    {
        $original = Encounter::factory()->completed()->create();
        $rectifier = Encounter::factory()->create([
            'patient_id' => $original->patient_id,
            'provider_id' => $original->provider_id,
            'rectifies_encounter_id' => $original->id,
        ]);

        $this->assertTrue($original->rectifier->is($rectifier));
        $this->assertTrue($rectifier->rectifies->is($original));
    }
}
```

- [ ] **Step 2: Run the test, confirm it fails**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterLockdownTest
```
Expected: 4 failures with messages about missing `isLocked`, `rectifier`, `rectifies`.

- [ ] **Step 3: Update the Encounter model**

Replace `app/Models/Encounter.php` body so it ends up as:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    /** @use HasFactory<\Database\Factories\EncounterFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'provider_id',
        'encounter_date',
        'chief_complaint',
        'clinical_notes',
        'diagnosis',
        'status',
        'patient_signature_data',
        'dentist_signature_data',
        'patient_signed_at',
        'dentist_signed_by',
        'dentist_signed_at',
        'signed_ip',
        'signed_hash',
        'rectifies_encounter_id',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
            'patient_signed_at' => 'datetime',
            'dentist_signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function dentistSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_signed_by');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function rectifies(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rectifies_encounter_id');
    }

    public function rectifier(): HasOne
    {
        return $this->hasOne(self::class, 'rectifies_encounter_id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['completed', 'cancelled'], true);
    }

    public function isSigned(): bool
    {
        return $this->status === 'completed'
            && $this->patient_signature_data !== null
            && $this->dentist_signature_data !== null;
    }
}
```

Add `use Illuminate\Database\Eloquent\Relations\HasOne;` to the use statements at the top.

- [ ] **Step 4: Run the test, confirm it passes**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterLockdownTest
```
Expected: 4 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Encounter.php tests/Feature/EncounterLockdownTest.php
git commit -m "$(cat <<'EOF'
Add Encounter lockdown helper and rectification relations

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Policy lockdown (Encounter, Treatment, Attachment)

**Files:**
- Modify: `app/Policies/EncounterPolicy.php`
- Modify: `app/Policies/TreatmentPolicy.php`
- Modify: `app/Policies/AttachmentPolicy.php`
- Modify: `tests/Feature/EncounterLockdownTest.php` (add policy tests)

- [ ] **Step 1: Extend the test with policy assertions**

Append to `tests/Feature/EncounterLockdownTest.php` before the closing brace:

```php
    public function test_completed_encounter_cannot_be_updated(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($admin)
            ->put(route('encounters.update', $encounter), [
                'encounter_date' => '2026-01-01',
                'chief_complaint' => 'changed',
            ])
            ->assertForbidden();
    }

    public function test_completed_encounter_cannot_be_deleted(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($admin)
            ->delete(route('encounters.destroy', $encounter))
            ->assertForbidden();
    }

    public function test_treatment_cannot_be_created_on_completed_encounter(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($admin)
            ->post(route('treatments.store', $encounter), [
                'treatment_code' => 'D1234',
                'description' => 'late add',
            ])
            ->assertForbidden();
    }

    public function test_attachment_cannot_be_created_on_completed_encounter(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $file = \Illuminate\Http\UploadedFile::fake()->create('xray.png', 100, 'image/png');

        $this->actingAs($admin)
            ->post(route('attachments.store'), [
                'attachable_type' => \App\Models\Encounter::class,
                'attachable_id' => $encounter->id,
                'file' => $file,
            ])
            ->assertForbidden();
    }
```

- [ ] **Step 2: Run the test, confirm failures**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterLockdownTest
```
Expected: 4 new failures (the 4 above) because policies don't yet check `isLocked`.

- [ ] **Step 3: Update `EncounterPolicy`**

Replace `app/Policies/EncounterPolicy.php` with:

```php
<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        if ($encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        if ($encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist');
    }

    public function sign(User $user, Encounter $encounter): bool
    {
        return !$encounter->isLocked()
            && $user->hasRole('admin', 'dentist');
    }

    public function rectify(User $user, Encounter $encounter): bool
    {
        return $encounter->status === 'completed'
            && $user->hasRole('admin', 'dentist');
    }

    public function downloadPdf(User $user, Encounter $encounter): bool
    {
        return $encounter->status === 'completed'
            && $user->hasRole('admin', 'dentist');
    }
}
```

- [ ] **Step 4: Update `TreatmentPolicy`**

Replace `app/Policies/TreatmentPolicy.php` with:

```php
<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function view(User $user, Treatment $treatment): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function create(User $user, ?Encounter $encounter = null): bool
    {
        if ($encounter && $encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function update(User $user, Treatment $treatment): bool
    {
        if ($treatment->encounter && $treatment->encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Treatment $treatment): bool
    {
        if ($treatment->encounter && $treatment->encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist');
    }
}
```

- [ ] **Step 5: Update `AttachmentPolicy`**

Replace `app/Policies/AttachmentPolicy.php` with:

```php
<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Encounter;
use App\Models\User;

class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function create(User $user, ?Model $attachable = null): bool
    {
        if ($attachable instanceof Encounter && $attachable->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $owner = $attachment->attachable;
        if ($owner instanceof Encounter && $owner->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist');
    }
}
```

Add `use Illuminate\Database\Eloquent\Model;` to the top.

- [ ] **Step 6: Apply lock authorization in form requests**

In `app/Http/Requests/UpdateEncounterRequest.php`, locate the `authorize()` method and replace its body with:

```php
        $user = $this->user();
        $encounter = $this->route('encounter');

        if (!$user || !$user->hasRole('admin', 'dentist', 'assistant')) {
            return false;
        }
        if ($encounter instanceof \App\Models\Encounter && $encounter->isLocked()) {
            return false;
        }
        return true;
```

In `app/Http/Requests/StoreTreatmentRequest.php`:

```php
        $encounter = $this->route('encounter');
        if ($encounter instanceof \App\Models\Encounter && $encounter->isLocked()) {
            return false;
        }
        return $this->user()?->hasRole('admin', 'dentist', 'assistant') ?? false;
```

In `app/Http/Requests/UpdateTreatmentRequest.php`:

```php
        $treatment = $this->route('treatment');
        if ($treatment && $treatment->encounter && $treatment->encounter->isLocked()) {
            return false;
        }
        return $this->user()?->hasRole('admin', 'dentist', 'assistant') ?? false;
```

In `app/Http/Requests/StoreAttachmentRequest.php`, locate `authorize()` and after the role check add:

```php
        $type = $this->input('attachable_type');
        $id = $this->input('attachable_id');
        if ($type === \App\Models\Encounter::class && $id) {
            $parent = \App\Models\Encounter::find($id);
            if ($parent && $parent->isLocked()) {
                return false;
            }
        }
```

(Read each request file first; the exact authorize body may differ but the goal is to add the lock guard before returning `true`.)

- [ ] **Step 7: Run the lockdown test, confirm pass**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterLockdownTest
```
Expected: all 8 tests pass.

- [ ] **Step 8: Commit**

```bash
git add app/Policies/EncounterPolicy.php \
        app/Policies/TreatmentPolicy.php \
        app/Policies/AttachmentPolicy.php \
        app/Http/Requests/UpdateEncounterRequest.php \
        app/Http/Requests/StoreTreatmentRequest.php \
        app/Http/Requests/UpdateTreatmentRequest.php \
        app/Http/Requests/StoreAttachmentRequest.php \
        tests/Feature/EncounterLockdownTest.php
git commit -m "$(cat <<'EOF'
Enforce encounter lockdown across policies and form requests

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Sign endpoint (backend)

**Files:**
- Create: `app/Http/Requests/SignEncounterRequest.php`
- Modify: `app/Http/Controllers/EncounterController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/EncounterSigningTest.php`

- [ ] **Step 1: Write the failing test**

Write `tests/Feature/EncounterSigningTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterSigningTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_dentist_can_sign_in_progress_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $response = $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ]);

        $response->assertRedirect(route('encounters.show', $encounter));

        $encounter->refresh();
        $this->assertSame('completed', $encounter->status);
        $this->assertSame($this->pngBase64, $encounter->patient_signature_data);
        $this->assertSame($this->pngBase64, $encounter->dentist_signature_data);
        $this->assertSame($dentist->id, $encounter->dentist_signed_by);
        $this->assertNotNull($encounter->patient_signed_at);
        $this->assertNotNull($encounter->dentist_signed_at);
        $this->assertNotNull($encounter->signed_ip);
        $this->assertSame(64, strlen($encounter->signed_hash));
    }

    public function test_signing_creates_explicit_audit_log_entry(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ]);

        $log = AuditLog::where('entity_type', Encounter::class)
            ->where('entity_id', $encounter->id)
            ->where('action', 'signed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($dentist->id, $log->user_id);
    }

    public function test_signing_fails_when_patient_signature_missing(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertSessionHasErrors(['patient_signature_data']);
    }

    public function test_signing_fails_when_encounter_not_in_progress(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'scheduled']);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertForbidden();
    }

    public function test_signing_fails_for_receptionist(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($receptionist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertForbidden();
    }

    public function test_signing_fails_when_no_treatments(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertSessionHasErrors();
    }
}
```

- [ ] **Step 2: Run, expect 6 failures**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterSigningTest
```
Expected: 6 failures (route does not exist yet).

- [ ] **Step 3: Create `SignEncounterRequest`**

Write `app/Http/Requests/SignEncounterRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Foundation\Http\FormRequest;

class SignEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $encounter = $this->route('encounter');
        if (!$encounter instanceof Encounter) {
            return false;
        }
        if ($encounter->status !== 'in_progress') {
            return false;
        }
        return $this->user()?->hasRole('admin', 'dentist') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_signature_data' => ['required', 'string', 'starts_with:data:image/'],
            'dentist_signature_data' => ['required', 'string', 'starts_with:data:image/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $encounter = $this->route('encounter');
            if ($encounter && $encounter->treatments()->count() === 0) {
                $validator->errors()->add(
                    'treatments',
                    'Encounter must have at least one treatment before signing.'
                );
            }
        });
    }
}
```

- [ ] **Step 4: Add the `sign` controller action**

In `app/Http/Controllers/EncounterController.php`, add at the top:

```php
use App\Http\Requests\SignEncounterRequest;
use App\Models\AuditLog;
```

Then append before the closing brace of the class:

```php
    public function sign(SignEncounterRequest $request, Encounter $encounter): RedirectResponse
    {
        $patientSignedAt = now();
        $dentistSignedAt = now();

        $treatmentsJson = $encounter->treatments()->orderBy('id')->get([
            'id', 'tooth_number', 'treatment_code', 'description', 'surface', 'cost', 'status',
        ])->toJson();

        $hash = hash('sha256', implode('|', [
            $encounter->id,
            $encounter->encounter_date?->toDateString(),
            (string) $encounter->chief_complaint,
            (string) $encounter->diagnosis,
            (string) $encounter->clinical_notes,
            $treatmentsJson,
            $patientSignedAt->toIso8601String(),
            $dentistSignedAt->toIso8601String(),
        ]));

        $encounter->update([
            'patient_signature_data' => $request->input('patient_signature_data'),
            'dentist_signature_data' => $request->input('dentist_signature_data'),
            'patient_signed_at' => $patientSignedAt,
            'dentist_signed_by' => auth()->id(),
            'dentist_signed_at' => $dentistSignedAt,
            'signed_ip' => $request->ip(),
            'signed_hash' => $hash,
            'status' => 'completed',
        ]);

        AuditLog::create([
            'entity_type' => Encounter::class,
            'entity_id' => $encounter->id,
            'user_id' => auth()->id(),
            'action' => 'signed',
            'ip_address' => $request->ip(),
            'metadata_json' => [
                'patient_signed_at' => $patientSignedAt->toIso8601String(),
                'dentist_signed_at' => $dentistSignedAt->toIso8601String(),
                'dentist_signed_by_user_id' => auth()->id(),
                'signed_ip' => $request->ip(),
                'treatments_count' => $encounter->treatments()->count(),
                'encounter_hash' => $hash,
            ],
        ]);

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Encounter signed and locked.');
    }
```

(If `AuditLog` fillable doesn't include `metadata_json` or `ip_address`, read `app/Models/AuditLog.php` and add them — verify before assuming the keys exist.)

- [ ] **Step 5: Register the route**

In `routes/web.php`, inside the `Route::middleware('auth')` group, after the existing encounter routes, add:

```php
    Route::post('/encounters/{encounter}/sign', [EncounterController::class, 'sign'])
        ->name('encounters.sign');
```

- [ ] **Step 6: Run tests, expect all 6 to pass**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterSigningTest
```
Expected: 6 passed.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/SignEncounterRequest.php \
        app/Http/Controllers/EncounterController.php \
        routes/web.php \
        tests/Feature/EncounterSigningTest.php
git commit -m "$(cat <<'EOF'
Add encounter signing endpoint with audit log and content hash

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Rectification flow (backend)

**Files:**
- Modify: `app/Http/Controllers/EncounterController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/EncounterRectificationTest.php`

- [ ] **Step 1: Write the failing test**

Write `tests/Feature/EncounterRectificationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterRectificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dentist_can_create_rectifier_for_completed_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $original = Encounter::factory()->completed()->create();
        Treatment::factory()->count(2)->create(['encounter_id' => $original->id]);

        $response = $this->actingAs($dentist)
            ->post(route('encounters.rectify', $original));

        $response->assertRedirect();
        $rectifier = Encounter::where('rectifies_encounter_id', $original->id)->first();

        $this->assertNotNull($rectifier);
        $this->assertSame($original->patient_id, $rectifier->patient_id);
        $this->assertSame('in_progress', $rectifier->status);
        $this->assertSame($original->chief_complaint, $rectifier->chief_complaint);
        $this->assertCount(2, $rectifier->treatments);
    }

    public function test_rectification_writes_audit_log_on_original(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $original = Encounter::factory()->completed()->create();

        $this->actingAs($dentist)->post(route('encounters.rectify', $original));

        $log = AuditLog::where('entity_type', Encounter::class)
            ->where('entity_id', $original->id)
            ->where('action', 'rectified')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('rectified_by_encounter_id', $log->metadata_json);
    }

    public function test_cannot_rectify_in_progress_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);

        $this->actingAs($dentist)
            ->post(route('encounters.rectify', $encounter))
            ->assertForbidden();
    }

    public function test_receptionist_cannot_rectify(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($receptionist)
            ->post(route('encounters.rectify', $encounter))
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run, expect 4 failures**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterRectificationTest
```
Expected: 4 failures (no route).

- [ ] **Step 3: Implement the `rectify` controller action**

In `app/Http/Controllers/EncounterController.php`, append before the closing brace:

```php
    public function rectify(Encounter $encounter): RedirectResponse
    {
        $this->authorize('rectify', $encounter);

        $rectifier = $encounter->patient->encounters()->create([
            'provider_id' => auth()->id(),
            'encounter_date' => now()->toDateString(),
            'chief_complaint' => $encounter->chief_complaint,
            'clinical_notes' => $encounter->clinical_notes,
            'diagnosis' => $encounter->diagnosis,
            'status' => 'in_progress',
            'rectifies_encounter_id' => $encounter->id,
        ]);

        foreach ($encounter->treatments as $treatment) {
            $rectifier->treatments()->create([
                'tooth_number' => $treatment->tooth_number,
                'treatment_code' => $treatment->treatment_code,
                'description' => $treatment->description,
                'surface' => $treatment->surface,
                'cost' => $treatment->cost,
                'status' => $treatment->status,
                'notes' => $treatment->notes ?? null,
            ]);
        }

        AuditLog::create([
            'entity_type' => Encounter::class,
            'entity_id' => $encounter->id,
            'user_id' => auth()->id(),
            'action' => 'rectified',
            'ip_address' => request()->ip(),
            'metadata_json' => [
                'rectified_by_encounter_id' => $rectifier->id,
                'rectified_by_user_id' => auth()->id(),
            ],
        ]);

        return redirect()->route('encounters.edit', $rectifier)
            ->with('success', 'Rectification encounter created. Edit and re-sign.');
    }
```

- [ ] **Step 4: Register the route**

In `routes/web.php`, inside `Route::middleware('auth')`, add:

```php
    Route::post('/encounters/{encounter}/rectify', [EncounterController::class, 'rectify'])
        ->name('encounters.rectify');
```

- [ ] **Step 5: Run, expect all 4 to pass**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterRectificationTest
```
Expected: 4 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/EncounterController.php routes/web.php tests/Feature/EncounterRectificationTest.php
git commit -m "$(cat <<'EOF'
Add rectification flow with audit log linkage

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Per-encounter PDF (backend)

**Files:**
- Create: `app/Services/EncounterPdfService.php`
- Create: `resources/views/pdf/encounter.blade.php`
- Modify: `app/Http/Controllers/EncounterController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/EncounterPdfTest.php`

- [ ] **Step 1: Write the failing test**

Write `tests/Feature/EncounterPdfTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_pdf_of_completed_encounter(): void
    {
        $admin = User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $response = $this->actingAs($admin)
            ->get(route('encounters.pdf', $encounter));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_pdf_blocked_for_in_progress_encounter(): void
    {
        $admin = User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);

        $this->actingAs($admin)
            ->get(route('encounters.pdf', $encounter))
            ->assertForbidden();
    }

    public function test_pdf_blocked_for_receptionist(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($receptionist)
            ->get(route('encounters.pdf', $encounter))
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run, expect 3 failures**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterPdfTest
```
Expected: 3 failures.

- [ ] **Step 3: Create the blade template**

Write `resources/views/pdf/encounter.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #222; }
        h1 { font-size: 14pt; margin: 0 0 6px 0; }
        h2 { font-size: 11pt; margin: 12px 0 4px 0; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; font-size: 9pt; }
        th { background: #f3f4f6; }
        .header { display: table; width: 100%; margin-bottom: 12px; }
        .header-left, .header-right { display: table-cell; vertical-align: top; }
        .header-right { text-align: right; }
        .signatures { margin-top: 18px; width: 100%; }
        .sig-cell { display: inline-block; width: 47%; border: 1px solid #ccc; padding: 8px; margin-right: 1%; vertical-align: top; }
        .sig-cell img { max-width: 100%; max-height: 80px; }
        .footer { margin-top: 14px; font-size: 8pt; color: #666; border-top: 1px solid #ccc; padding-top: 4px; }
        .rectify-banner { background: #fef3c7; border: 1px solid #f59e0b; padding: 4px 6px; margin-bottom: 8px; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}" style="height: 48px;" alt="logo">
            @endif
            <h1>Encounter Record</h1>
        </div>
        <div class="header-right">
            <div><strong>Patient:</strong> {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->identifier }})</div>
            <div><strong>Date:</strong> {{ $encounter->encounter_date?->format('d M Y') }}</div>
            <div><strong>Provider:</strong> {{ $encounter->provider?->name }}</div>
            <div><strong>Encounter ID:</strong> #{{ $encounter->id }}</div>
        </div>
    </div>

    @if($encounter->rectifies_encounter_id)
        <div class="rectify-banner">
            Rectifies encounter #{{ $encounter->rectifies_encounter_id }}
        </div>
    @endif

    @if($encounter->chief_complaint)
        <h2>Chief Complaint</h2>
        <p>{{ $encounter->chief_complaint }}</p>
    @endif

    @if($encounter->diagnosis)
        <h2>Diagnosis</h2>
        <p>{{ $encounter->diagnosis }}</p>
    @endif

    @if($encounter->clinical_notes)
        <h2>Clinical Notes</h2>
        <p style="white-space: pre-wrap;">{{ $encounter->clinical_notes }}</p>
    @endif

    <h2>Treatments</h2>
    <table>
        <thead>
            <tr>
                <th>Code</th><th>Tooth</th><th>Surface</th><th>Description</th><th style="text-align: right;">Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($encounter->treatments as $t)
                <tr>
                    <td>{{ $t->treatment_code }}</td>
                    <td>{{ $t->tooth_number }}</td>
                    <td>{{ $t->surface }}</td>
                    <td>{{ $t->description }}</td>
                    <td style="text-align: right;">{{ number_format($t->cost, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align: right;">Total</th>
                <th style="text-align: right;">{{ number_format($encounter->treatments->sum('cost'), 2) }} RON</th>
            </tr>
        </tfoot>
    </table>

    <h2>Signatures</h2>
    <div class="signatures">
        <div class="sig-cell">
            @if($encounter->patient_signature_data)
                <img src="{{ $encounter->patient_signature_data }}" alt="patient signature">
            @endif
            <div>{{ $patient->first_name }} {{ $patient->last_name }}</div>
            <div style="font-size: 8pt; color: #666;">
                Patient · {{ $encounter->patient_signed_at?->format('d/m/Y H:i') }}
            </div>
        </div>
        <div class="sig-cell">
            @if($encounter->dentist_signature_data)
                <img src="{{ $encounter->dentist_signature_data }}" alt="dentist signature">
            @endif
            <div>{{ $encounter->dentistSigner?->name ?? $encounter->provider?->name }}</div>
            <div style="font-size: 8pt; color: #666;">
                Dentist · {{ $encounter->dentist_signed_at?->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <div class="footer">
        Generated {{ now()->format('d/m/Y H:i') }} ·
        Signed IP {{ $encounter->signed_ip }} ·
        Document hash sha256:{{ $encounter->signed_hash }}
    </div>
</body>
</html>
```

- [ ] **Step 4: Create the PDF service**

Write `app/Services/EncounterPdfService.php`:

```php
<?php

namespace App\Services;

use App\Models\Encounter;
use Barryvdh\DomPDF\Facade\Pdf;

class EncounterPdfService
{
    public function generate(Encounter $encounter): \Barryvdh\DomPDF\PDF
    {
        $encounter->load(['patient', 'provider:id,name', 'dentistSigner:id,name', 'treatments']);

        $pdf = Pdf::loadView('pdf.encounter', [
            'encounter' => $encounter,
            'patient' => $encounter->patient,
            'logoBase64' => $this->loadLogoBase64(),
        ]);
        $pdf->setPaper('A4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', false);
        return $pdf;
    }

    public function filename(Encounter $encounter): string
    {
        $encounter->loadMissing('patient');
        return "encounter-{$encounter->patient->identifier}-{$encounter->id}.pdf";
    }

    private function loadLogoBase64(): string
    {
        $pngPath = public_path('images/clinic-logo.png');
        return file_exists($pngPath) ? base64_encode(file_get_contents($pngPath)) : '';
    }
}
```

- [ ] **Step 5: Add controller action and route**

In `app/Http/Controllers/EncounterController.php`, add at the top:

```php
use App\Services\EncounterPdfService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
```

Append before the closing brace:

```php
    public function pdf(Encounter $encounter, EncounterPdfService $pdfService): HttpResponse
    {
        $this->authorize('downloadPdf', $encounter);

        $pdf = $pdfService->generate($encounter);
        return $pdf->download($pdfService->filename($encounter));
    }
```

In `routes/web.php`, inside the auth group, add:

```php
    Route::get('/encounters/{encounter}/pdf', [EncounterController::class, 'pdf'])
        ->name('encounters.pdf');
```

- [ ] **Step 6: Run, expect all 3 to pass**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterPdfTest
```
Expected: 3 passed.

- [ ] **Step 7: Commit**

```bash
git add app/Services/EncounterPdfService.php \
        resources/views/pdf/encounter.blade.php \
        app/Http/Controllers/EncounterController.php \
        routes/web.php \
        tests/Feature/EncounterPdfTest.php
git commit -m "$(cat <<'EOF'
Add per-encounter signed PDF download

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Consolidated clinical history PDF

**Files:**
- Create: `app/Services/ClinicalHistoryPdfService.php`
- Create: `resources/views/pdf/clinical-history.blade.php`
- Modify: `app/Http/Controllers/PatientController.php`
- Modify: `app/Policies/PatientPolicy.php` (add `clinicalHistory` ability)
- Modify: `routes/web.php`
- Create: `tests/Feature/ClinicalHistoryPdfTest.php`

- [ ] **Step 1: Write the failing test**

Write `tests/Feature/ClinicalHistoryPdfTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalHistoryPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_full_clinical_history_pdf(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->completed()->create(['patient_id' => $patient->id]);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $response = $this->actingAs($admin)
            ->get(route('patients.clinical-history', $patient));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_receptionist_cannot_download_clinical_history(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $patient = Patient::factory()->create();

        $this->actingAs($receptionist)
            ->get(route('patients.clinical-history', $patient))
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run, expect 2 failures**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=ClinicalHistoryPdfTest
```

- [ ] **Step 3: Add `clinicalHistory` ability to `PatientPolicy`**

In `app/Policies/PatientPolicy.php`, append before the closing brace:

```php
    public function clinicalHistory(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin', 'dentist');
    }
```

- [ ] **Step 4: Write the blade template**

Write `resources/views/pdf/clinical-history.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #222; }
        h1 { font-size: 16pt; }
        h2 { font-size: 12pt; border-bottom: 1px solid #ccc; padding-bottom: 2px; margin-top: 16px; }
        h3 { font-size: 11pt; margin-top: 12px; }
        .cover { padding-top: 80px; text-align: center; page-break-after: always; }
        .toc { margin-top: 24px; }
        .encounter { page-break-before: always; }
        .sig-cell { display: inline-block; width: 47%; border: 1px solid #ccc; padding: 6px; vertical-align: top; }
        .sig-cell img { max-width: 100%; max-height: 70px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; font-size: 9pt; text-align: left; }
        th { background: #f3f4f6; }
        .footer-fixed { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 8pt; color: #666; }
    </style>
</head>
<body>
    <div class="footer-fixed">
        Patient {{ $patient->identifier }} · Generated {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="cover">
        @if($logoBase64)
            <img src="data:image/png;base64,{{ $logoBase64 }}" style="height: 72px;" alt="logo">
        @endif
        <h1>Clinical History</h1>
        <p>
            <strong>{{ $patient->first_name }} {{ $patient->last_name }}</strong><br>
            {{ $patient->identifier }}<br>
            DOB: {{ $patient->date_of_birth?->format('d/m/Y') }}
        </p>
        <div class="toc">
            <h2>Contents</h2>
            <ol>
                <li>Current anamnesis (v{{ $anamnesis?->version ?? 'n/a' }})</li>
                <li>Signed encounters ({{ $signedEncounters->count() }})</li>
                <li>Cancelled encounters ({{ $cancelledEncounters->count() }})</li>
            </ol>
        </div>
    </div>

    <h2>Current Anamnesis</h2>
    @if($anamnesis)
        <p>Version {{ $anamnesis->version }} ·
           Recorded {{ $anamnesis->created_at->format('d/m/Y H:i') }} ·
           Language: {{ strtoupper($anamnesis->language ?? 'en') }}</p>
        <p><em>See full anamnesis PDF in GDPR export bundle for the complete form.</em></p>
        @if($anamnesis->signature_data)
            <div class="sig-cell">
                <img src="{{ $anamnesis->signature_data }}" alt="patient signature">
                <div style="font-size: 8pt;">Patient consent signature</div>
            </div>
        @endif
        @if($anamnesis->dentist_signature_data)
            <div class="sig-cell">
                <img src="{{ $anamnesis->dentist_signature_data }}" alt="dentist signature">
                <div style="font-size: 8pt;">Dentist · {{ $anamnesis->signed_at?->format('d/m/Y H:i') }}</div>
            </div>
        @endif
    @else
        <p><em>No anamnesis on file.</em></p>
    @endif

    @foreach($signedEncounters as $encounter)
        <div class="encounter">
            <h2>Encounter #{{ $encounter->id }} — {{ $encounter->encounter_date?->format('d M Y') }}</h2>
            @if($encounter->rectifies_encounter_id)
                <p><em>Rectifies encounter #{{ $encounter->rectifies_encounter_id }}</em></p>
            @endif
            <p><strong>Provider:</strong> {{ $encounter->provider?->name }}</p>

            @if($encounter->chief_complaint)
                <h3>Chief Complaint</h3>
                <p>{{ $encounter->chief_complaint }}</p>
            @endif
            @if($encounter->diagnosis)
                <h3>Diagnosis</h3>
                <p>{{ $encounter->diagnosis }}</p>
            @endif
            @if($encounter->clinical_notes)
                <h3>Clinical Notes</h3>
                <p style="white-space: pre-wrap;">{{ $encounter->clinical_notes }}</p>
            @endif

            <h3>Treatments</h3>
            <table>
                <thead>
                    <tr><th>Code</th><th>Tooth</th><th>Surface</th><th>Description</th><th style="text-align: right;">Cost</th></tr>
                </thead>
                <tbody>
                    @foreach($encounter->treatments as $t)
                        <tr>
                            <td>{{ $t->treatment_code }}</td>
                            <td>{{ $t->tooth_number }}</td>
                            <td>{{ $t->surface }}</td>
                            <td>{{ $t->description }}</td>
                            <td style="text-align: right;">{{ number_format($t->cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h3>Signatures</h3>
            <div class="sig-cell">
                @if($encounter->patient_signature_data)
                    <img src="{{ $encounter->patient_signature_data }}" alt="patient signature">
                @endif
                <div style="font-size: 8pt;">Patient · {{ $encounter->patient_signed_at?->format('d/m/Y H:i') }}</div>
            </div>
            <div class="sig-cell">
                @if($encounter->dentist_signature_data)
                    <img src="{{ $encounter->dentist_signature_data }}" alt="dentist signature">
                @endif
                <div style="font-size: 8pt;">
                    {{ $encounter->dentistSigner?->name ?? $encounter->provider?->name }} ·
                    {{ $encounter->dentist_signed_at?->format('d/m/Y H:i') }}
                </div>
            </div>
            <p style="font-size: 8pt; color: #666;">Hash sha256:{{ $encounter->signed_hash }}</p>
        </div>
    @endforeach

    @if($cancelledEncounters->count() > 0)
        <h2 style="page-break-before: always;">Cancelled Encounters</h2>
        <table>
            <thead>
                <tr><th>Date</th><th>Provider</th><th>Reason / Chief Complaint</th></tr>
            </thead>
            <tbody>
                @foreach($cancelledEncounters as $c)
                    <tr>
                        <td>{{ $c->encounter_date?->format('d/m/Y') }}</td>
                        <td>{{ $c->provider?->name }}</td>
                        <td>{{ $c->chief_complaint }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
```

- [ ] **Step 5: Write the service**

Write `app/Services/ClinicalHistoryPdfService.php`:

```php
<?php

namespace App\Services;

use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;

class ClinicalHistoryPdfService
{
    public function generate(Patient $patient): \Barryvdh\DomPDF\PDF
    {
        $patient->load([
            'latestAnamnesis',
            'encounters' => fn ($q) => $q->orderBy('encounter_date'),
            'encounters.treatments',
            'encounters.provider:id,name',
            'encounters.dentistSigner:id,name',
        ]);

        $signedEncounters = $patient->encounters->where('status', 'completed')->values();
        $cancelledEncounters = $patient->encounters->where('status', 'cancelled')->values();

        $pdf = Pdf::loadView('pdf.clinical-history', [
            'patient' => $patient,
            'anamnesis' => $patient->latestAnamnesis,
            'signedEncounters' => $signedEncounters,
            'cancelledEncounters' => $cancelledEncounters,
            'logoBase64' => $this->loadLogoBase64(),
        ]);
        $pdf->setPaper('A4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', false);
        return $pdf;
    }

    public function filename(Patient $patient): string
    {
        return "clinical-history-{$patient->identifier}.pdf";
    }

    private function loadLogoBase64(): string
    {
        $pngPath = public_path('images/clinic-logo.png');
        return file_exists($pngPath) ? base64_encode(file_get_contents($pngPath)) : '';
    }
}
```

- [ ] **Step 6: Add controller action and route**

In `app/Http/Controllers/PatientController.php`, add at the top:

```php
use App\Services\ClinicalHistoryPdfService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
```

Append before the closing brace of the class:

```php
    public function clinicalHistory(Patient $patient, ClinicalHistoryPdfService $service): HttpResponse
    {
        $this->authorize('clinicalHistory', $patient);
        $pdf = $service->generate($patient);
        return $pdf->download($service->filename($patient));
    }
```

In `routes/web.php`, inside the auth group, add:

```php
    Route::get('/patients/{patient}/clinical-history/pdf', [PatientController::class, 'clinicalHistory'])
        ->name('patients.clinical-history');
```

- [ ] **Step 7: Run, expect 2 passes**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=ClinicalHistoryPdfTest
```

- [ ] **Step 8: Commit**

```bash
git add app/Services/ClinicalHistoryPdfService.php \
        resources/views/pdf/clinical-history.blade.php \
        app/Http/Controllers/PatientController.php \
        app/Policies/PatientPolicy.php \
        routes/web.php \
        tests/Feature/ClinicalHistoryPdfTest.php
git commit -m "$(cat <<'EOF'
Add consolidated clinical history PDF

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Include clinical history PDF in GDPR export ZIP

**Files:**
- Modify: `app/Http/Controllers/GdprExportController.php`
- Modify: `tests/Feature/ClinicalHistoryPdfTest.php` (add one test)

- [ ] **Step 1: Add failing test**

Append to `tests/Feature/ClinicalHistoryPdfTest.php`:

```php
    public function test_gdpr_export_zip_includes_clinical_history_pdf(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        Encounter::factory()->completed()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($admin)
            ->get(route('patients.gdpr-export', $patient));

        $response->assertOk();

        // Capture the streamed body and inspect ZIP contents
        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'gdpr');
        file_put_contents($tmp, $body);

        $zip = new \ZipArchive();
        $zip->open($tmp);
        $found = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if ($zip->getNameIndex($i) === 'clinical-history.pdf') {
                $found = true;
                break;
            }
        }
        $zip->close();
        @unlink($tmp);

        $this->assertTrue($found, 'clinical-history.pdf missing from GDPR export zip');
    }
```

- [ ] **Step 2: Run, expect 1 failure**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=ClinicalHistoryPdfTest
```

- [ ] **Step 3: Update `GdprExportController`**

In `app/Http/Controllers/GdprExportController.php`, add at the top:

```php
use App\Services\ClinicalHistoryPdfService;
```

Change the `export` method signature to inject the service:
```php
    public function export(Patient $patient, ClinicalHistoryPdfService $clinicalHistory): StreamedResponse
```

Insert this line right before `$zip->close();`:
```php
        $zip->addFromString('clinical-history.pdf', $clinicalHistory->generate($patient)->output());
```

- [ ] **Step 4: Run, expect pass**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=ClinicalHistoryPdfTest
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/GdprExportController.php tests/Feature/ClinicalHistoryPdfTest.php
git commit -m "$(cat <<'EOF'
Bundle clinical history PDF inside GDPR export zip

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: Extract `SignaturePad.vue` component

**Files:**
- Create: `resources/js/Components/SignaturePad.vue`
- Modify: `resources/js/Pages/Intake/Wizard.vue` (replace inline usage)

This is a refactor with no new behaviour — the intake flow must still work exactly the same way.

- [ ] **Step 1: Read current inline usage**

Run:
```bash
grep -n 'signaturePad\|SignaturePadLib\|signature_data\|signatureCanvas\|signatureContainer' resources/js/Pages/Intake/Wizard.vue
```
Understand the contract: canvas ref, init function, resize, clear, save (returns base64 PNG via `toDataURL()`).

- [ ] **Step 2: Write the component**

Write `resources/js/Components/SignaturePad.vue`:

```vue
<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import SignaturePadLib from 'signature_pad';

const props = defineProps<{
    modelValue: string | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const container = ref<HTMLDivElement | null>(null);
const canvas = ref<HTMLCanvasElement | null>(null);
let pad: SignaturePadLib | null = null;

function resize() {
    if (!canvas.value || !container.value) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.value.width = container.value.clientWidth * ratio;
    canvas.value.height = 180 * ratio;
    canvas.value.getContext('2d')?.scale(ratio, ratio);
    pad?.clear();
}

function handleEnd() {
    if (!pad || pad.isEmpty()) {
        emit('update:modelValue', null);
        return;
    }
    emit('update:modelValue', canvas.value!.toDataURL('image/png'));
}

function clear() {
    pad?.clear();
    emit('update:modelValue', null);
}

defineExpose({ clear });

onMounted(() => {
    if (!canvas.value) return;
    pad = new SignaturePadLib(canvas.value, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: '#1f2937',
    });
    pad.addEventListener('endStroke', handleEnd);
    resize();
    window.addEventListener('resize', resize);
});

onUnmounted(() => {
    window.removeEventListener('resize', resize);
    pad?.off();
});

watch(() => props.disabled, (d) => {
    if (d) pad?.off();
    else pad?.on();
});
</script>

<template>
    <div>
        <div ref="container" class="rounded-lg border border-gray-300 bg-white">
            <canvas ref="canvas" class="block w-full" style="height: 180px;" />
        </div>
        <div class="mt-2 flex justify-end">
            <button
                type="button"
                @click="clear"
                class="text-sm text-gray-500 underline hover:text-gray-700"
            >
                Clear
            </button>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Replace inline usage in `Intake/Wizard.vue`**

Locate the inline canvas+signature_pad block (around the canvas ref and `initSignaturePad` function). Replace the canvas markup with:

```vue
<SignaturePad v-model="form.signature_data" />
```

Remove: `signatureCanvas`, `signatureContainer`, `signaturePad`, `initSignaturePad`, `resizeSignatureCanvas`, `clearSignature`, `saveSignatureFromTyping`, the `signatureMode` toggle (if present, keep behaviour parity — if a typed-mode toggle existed, also extract that or leave a TODO marker that the typed mode is not yet ported and skip rendering the toggle).

Keep `form.signature_data` as the single source of truth. Import the component:

```ts
import SignaturePad from '@/Components/SignaturePad.vue';
```

(Read the existing file fully and preserve all other behaviour. If the intake originally supported a "type your name" mode in addition to drawing, keep that toggle but route the resulting base64 PNG through the same `form.signature_data` ref.)

- [ ] **Step 4: Manually verify in browser**

Run dev servers (already documented in CLAUDE.md):
```bash
/usr/local/opt/php@8.3/bin/php artisan serve
npm run dev
```
Open `http://127.0.0.1:8000/intake`, walk through the wizard to the consent step, draw a signature, submit. Verify the resulting `anamnesis_versions.signature_data` row contains a `data:image/png;base64,...` value (use tinker or check the DB).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/SignaturePad.vue resources/js/Pages/Intake/Wizard.vue
git commit -m "$(cat <<'EOF'
Extract SignaturePad component from intake wizard

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: SignWizard modal (3 steps)

**Files:**
- Create: `resources/js/Pages/Encounters/SignWizard.vue`
- Modify: `resources/js/i18n/en.json`
- Modify: `resources/js/i18n/ro.json`

- [ ] **Step 1: Add i18n keys**

In `resources/js/i18n/en.json`, find the `encounter` object and add:

```json
"close_and_sign": "Close and sign visit",
"sign_step_review": "Review",
"sign_step_patient": "Patient signature",
"sign_step_dentist": "Dentist signature",
"sign_warning": "Once signed, this encounter is locked. Fix any errors now.",
"sign_back_to_edit": "Back to edit",
"sign_continue": "Continue",
"sign_patient_text": "I, {name}, confirm that the treatments listed above were performed on me correctly.",
"sign_dentist_text": "I, Dr. {name}, certify that the listed treatments were performed as recorded.",
"sign_use_stored": "Use my stored professional signature",
"sign_submit": "Sign and close visit",
"sign_locked_banner": "Encounter signed on {at}. Locked.",
"signatures": "Signatures",
"download_signed_pdf": "Download signed PDF",
"rectify": "Rectify",
"rectifies_banner": "Rectifies encounter #{id}",
"rectified_by_banner": "Rectified by encounter #{id} on {at}",
"download_full_history": "Download full clinical history"
```

(Don't break existing JSON keys — read the file first, place these inside the existing `encounter` object.)

Mirror the same keys in `resources/js/i18n/ro.json` with Romanian translations:

```json
"close_and_sign": "Închide și semnează vizita",
"sign_step_review": "Verificare",
"sign_step_patient": "Semnătura pacientului",
"sign_step_dentist": "Semnătura medicului",
"sign_warning": "După semnare, această vizită va fi blocată. Corectați erorile acum.",
"sign_back_to_edit": "Înapoi la editare",
"sign_continue": "Continuă",
"sign_patient_text": "Subsemnatul, {name}, confirm că tratamentele de mai sus au fost efectuate corect.",
"sign_dentist_text": "Subsemnatul, Dr. {name}, certific că tratamentele au fost efectuate conform înregistrării.",
"sign_use_stored": "Folosește semnătura profesională stocată",
"sign_submit": "Semnează și închide vizita",
"sign_locked_banner": "Vizită semnată la {at}. Blocată.",
"signatures": "Semnături",
"download_signed_pdf": "Descarcă PDF semnat",
"rectify": "Rectifică",
"rectifies_banner": "Rectifică vizita #{id}",
"rectified_by_banner": "Rectificată de vizita #{id} la {at}",
"download_full_history": "Descarcă istoricul clinic complet"
```

- [ ] **Step 2: Write `SignWizard.vue`**

Write `resources/js/Pages/Encounters/SignWizard.vue`:

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SignaturePad from '@/Components/SignaturePad.vue';
import type { Encounter } from '@/types';

const props = defineProps<{
    encounter: Encounter;
    open: boolean;
    currentUser: { id: number; name: string; has_signature: boolean };
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { t } = useI18n();
const step = ref<1 | 2 | 3>(1);
const patientSig = ref<string | null>(null);
const dentistSig = ref<string | null>(null);
const useStored = ref(false);
const submitting = ref(false);

function close() {
    step.value = 1;
    patientSig.value = null;
    dentistSig.value = null;
    useStored.value = false;
    emit('close');
}

function submit() {
    if (!patientSig.value) return;
    submitting.value = true;
    const finalDentist = useStored.value ? 'use_stored' : dentistSig.value;
    if (!finalDentist) {
        submitting.value = false;
        return;
    }
    router.post(
        route('encounters.sign', props.encounter.id),
        {
            patient_signature_data: patientSig.value,
            dentist_signature_data: useStored.value ? null : dentistSig.value,
            use_stored_dentist_signature: useStored.value,
        } as any,
        {
            onFinish: () => { submitting.value = false; },
        }
    );
}

function patientName(): string {
    const p = props.encounter.patient;
    return `${p?.first_name ?? ''} ${p?.last_name ?? ''}`.trim();
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl">
                    <div class="flex items-center gap-2 border-b border-gray-200 px-6 py-4">
                        <span :class="['inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold', step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200']">1</span>
                        <span class="text-sm">{{ t('encounter.sign_step_review') }}</span>
                        <span class="text-gray-300">·</span>
                        <span :class="['inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold', step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200']">2</span>
                        <span class="text-sm">{{ t('encounter.sign_step_patient') }}</span>
                        <span class="text-gray-300">·</span>
                        <span :class="['inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold', step >= 3 ? 'bg-primary-600 text-white' : 'bg-gray-200']">3</span>
                        <span class="text-sm">{{ t('encounter.sign_step_dentist') }}</span>
                    </div>

                    <!-- Step 1: Review -->
                    <div v-if="step === 1" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="rounded-md bg-yellow-50 border border-yellow-200 p-3 text-sm text-yellow-800">
                            ⚠️ {{ t('encounter.sign_warning') }}
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.encounter_date') }}</p>
                            <p>{{ encounter.encounter_date }}</p>
                        </div>
                        <div v-if="encounter.chief_complaint">
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.chief_complaint') }}</p>
                            <p>{{ encounter.chief_complaint }}</p>
                        </div>
                        <div v-if="encounter.diagnosis">
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.diagnosis') }}</p>
                            <p>{{ encounter.diagnosis }}</p>
                        </div>
                        <div v-if="encounter.clinical_notes">
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.clinical_notes') }}</p>
                            <p class="whitespace-pre-wrap">{{ encounter.clinical_notes }}</p>
                        </div>
                        <div v-if="encounter.treatments?.length">
                            <p class="text-xs uppercase text-gray-500">{{ t('treatment.title') }}</p>
                            <table class="w-full text-sm">
                                <thead><tr class="text-left text-xs text-gray-500"><th>Code</th><th>Tooth</th><th>Description</th><th class="text-right">Cost</th></tr></thead>
                                <tbody>
                                    <tr v-for="tr in encounter.treatments" :key="tr.id" class="border-t">
                                        <td>{{ tr.treatment_code }}</td>
                                        <td>{{ tr.tooth_number }}</td>
                                        <td>{{ tr.description }}</td>
                                        <td class="text-right">{{ tr.cost }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Step 2: Patient signature -->
                    <div v-else-if="step === 2" class="p-6 space-y-3">
                        <p class="text-sm">{{ t('encounter.sign_patient_text', { name: patientName() }) }}</p>
                        <SignaturePad v-model="patientSig" />
                    </div>

                    <!-- Step 3: Dentist signature -->
                    <div v-else class="p-6 space-y-3">
                        <p class="text-sm">{{ t('encounter.sign_dentist_text', { name: currentUser.name }) }}</p>
                        <label v-if="currentUser.has_signature" class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="useStored">
                            {{ t('encounter.sign_use_stored') }}
                        </label>
                        <SignaturePad v-if="!useStored" v-model="dentistSig" />
                    </div>

                    <div class="flex justify-between gap-2 border-t border-gray-200 px-6 py-4">
                        <button type="button" @click="close" class="rounded-lg border px-3 py-2 text-sm">
                            {{ t('encounter.sign_back_to_edit') }}
                        </button>
                        <div class="flex gap-2">
                            <button
                                v-if="step < 3"
                                type="button"
                                :disabled="step === 2 && !patientSig"
                                @click="step = (step + 1) as 1 | 2 | 3"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                {{ t('encounter.sign_continue') }}
                            </button>
                            <button
                                v-else
                                type="button"
                                :disabled="submitting || (!useStored && !dentistSig)"
                                @click="submit"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                {{ t('encounter.sign_submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
```

- [ ] **Step 3: Adjust backend to support `use_stored_dentist_signature` flag**

In `app/Http/Requests/SignEncounterRequest.php`, change the rules method to:

```php
    public function rules(): array
    {
        return [
            'patient_signature_data' => ['required', 'string', 'starts_with:data:image/'],
            'dentist_signature_data' => ['nullable', 'string', 'starts_with:data:image/'],
            'use_stored_dentist_signature' => ['nullable', 'boolean'],
        ];
    }
```

Add a `withValidator` rule (extend the existing one):

```php
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $encounter = $this->route('encounter');
            if ($encounter && $encounter->treatments()->count() === 0) {
                $validator->errors()->add('treatments', 'Encounter must have at least one treatment before signing.');
            }
            $useStored = (bool) $this->input('use_stored_dentist_signature');
            $hasSig = (bool) $this->input('dentist_signature_data');
            if (!$useStored && !$hasSig) {
                $validator->errors()->add('dentist_signature_data', 'Dentist signature is required (or toggle "use stored").');
            }
            if ($useStored && !$this->user()?->signature_data) {
                $validator->errors()->add('use_stored_dentist_signature', 'You have no stored professional signature.');
            }
        });
    }
```

In `EncounterController::sign`, replace the `dentist_signature_data` source:

```php
        $useStored = (bool) $request->input('use_stored_dentist_signature');
        $dentistSig = $useStored
            ? auth()->user()->signature_data
            : $request->input('dentist_signature_data');
```

…and use `$dentistSig` in the `update()` call.

- [ ] **Step 4: Add a test for stored-dentist path**

Append to `tests/Feature/EncounterSigningTest.php`:

```php
    public function test_dentist_can_sign_with_stored_signature(): void
    {
        $dentist = User::factory()->role('dentist')->create([
            'signature_data' => $this->pngBase64,
        ]);
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'use_stored_dentist_signature' => true,
            ])
            ->assertRedirect();

        $encounter->refresh();
        $this->assertSame($this->pngBase64, $encounter->dentist_signature_data);
    }
```

- [ ] **Step 5: Run all signing tests, expect green**

```bash
/usr/local/opt/php@8.3/bin/php artisan test --filter=EncounterSigningTest
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Encounters/SignWizard.vue \
        resources/js/i18n/en.json \
        resources/js/i18n/ro.json \
        app/Http/Requests/SignEncounterRequest.php \
        app/Http/Controllers/EncounterController.php \
        tests/Feature/EncounterSigningTest.php
git commit -m "$(cat <<'EOF'
Add SignWizard modal and stored-dentist-signature support

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: Editor button + Show banner + Patients list lock icons

**Files:**
- Modify: `resources/js/Pages/Encounters/Editor.vue`
- Modify: `resources/js/Pages/Encounters/Show.vue`
- Modify: `resources/js/Pages/Patients/Show.vue`
- Modify: `app/Http/Controllers/EncounterController.php` (pass currentUser to Editor)

- [ ] **Step 1: Pass currentUser to Editor view**

In `EncounterController::edit`, change the Inertia render to include the current user with `has_signature`:

```php
        return Inertia::render('Encounters/Editor', [
            'encounter' => $encounter,
            'currentUser' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'has_signature' => (bool) auth()->user()->signature_data,
            ],
        ]);
```

In `EncounterController::show`, eager-load the dentist signer and pass the same `currentUser` shape:

```php
        $encounter->load([
            'patient',
            'provider:id,name',
            'dentistSigner:id,name',
            'rectifies:id,encounter_date',
            'rectifier:id,encounter_date,rectifies_encounter_id',
            'treatments',
            'attachments.uploader:id,name',
        ]);

        return Inertia::render('Encounters/Show', [
            'encounter' => $encounter,
        ]);
```

- [ ] **Step 2: Wire the SignWizard into `Editor.vue`**

Read `resources/js/Pages/Encounters/Editor.vue` and:
- Import the wizard: `import SignWizard from '@/Pages/Encounters/SignWizard.vue';`
- Add a ref: `const signWizardOpen = ref(false);`
- Add a button (visible only when `encounter && encounter.status === 'in_progress' && (encounter.treatments?.length ?? 0) > 0`):
  ```vue
  <button
      type="button"
      @click="signWizardOpen = true"
      class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
  >
      {{ t('encounter.close_and_sign') }}
  </button>
  ```
- Mount the wizard at the bottom of the template:
  ```vue
  <SignWizard
      v-if="encounter"
      :encounter="encounter"
      :open="signWizardOpen"
      :current-user="currentUser"
      @close="signWizardOpen = false"
  />
  ```
- Add the props declaration: extend the existing `defineProps<>` to include `currentUser: { id: number; name: string; has_signature: boolean };`.

(Read the file fully and integrate without breaking other behaviour.)

- [ ] **Step 3: Update `Encounters/Show.vue` with signed banner, signatures block, rectify button, PDF button**

In the existing template, after the encounter header card and before the grid, add:

```vue
<div v-if="encounter.status === 'completed'" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
    🔒 {{ t('encounter.sign_locked_banner', { at: formatDateTime(encounter.dentist_signed_at!) }) }}
</div>

<div v-if="encounter.rectifies" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
    {{ t('encounter.rectifies_banner', { id: encounter.rectifies.id }) }}
</div>

<div v-if="encounter.rectifier" class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
    {{ t('encounter.rectified_by_banner', { id: encounter.rectifier.id, at: encounter.rectifier.encounter_date }) }}
</div>
```

Replace the existing `Edit` / `Delete` action group when the encounter is `completed` with:

```vue
<div v-if="encounter.status === 'completed'" class="flex flex-wrap gap-2">
    <a
        :href="route('encounters.pdf', encounter.id)"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
    >
        <ArrowDownTrayIcon class="h-4 w-4" />
        {{ t('encounter.download_signed_pdf') }}
    </a>
    <button
        type="button"
        @click="rectify"
        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-700 shadow-sm hover:bg-amber-50"
    >
        {{ t('encounter.rectify') }}
    </button>
</div>
```

Above the `<script setup>` closing brace, add:

```ts
function rectify() {
    if (!confirm('Create a rectification encounter? The original stays locked.')) return;
    router.post(route('encounters.rectify', props.encounter.id));
}
```

Add a Signatures section in the sidebar / main grid:

```vue
<div v-if="encounter.status === 'completed'" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ t('encounter.signatures') }}</h3>
    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-lg border border-gray-200 p-3">
            <img v-if="encounter.patient_signature_data" :src="encounter.patient_signature_data" alt="patient signature" class="max-h-24" />
            <p class="mt-2 text-sm font-medium">{{ encounter.patient?.first_name }} {{ encounter.patient?.last_name }}</p>
            <p class="text-xs text-gray-500">{{ formatDateTime(encounter.patient_signed_at!) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 p-3">
            <img v-if="encounter.dentist_signature_data" :src="encounter.dentist_signature_data" alt="dentist signature" class="max-h-24" />
            <p class="mt-2 text-sm font-medium">{{ encounter.dentist_signer?.name ?? encounter.provider?.name }}</p>
            <p class="text-xs text-gray-500">{{ formatDateTime(encounter.dentist_signed_at!) }}</p>
        </div>
    </div>
</div>
```

Update `resources/js/types/index.ts` (or equivalent) to add the new optional fields on `Encounter`:

```ts
patient_signature_data?: string | null;
dentist_signature_data?: string | null;
patient_signed_at?: string | null;
dentist_signed_at?: string | null;
signed_ip?: string | null;
signed_hash?: string | null;
rectifies_encounter_id?: number | null;
rectifies?: { id: number; encounter_date: string } | null;
rectifier?: { id: number; encounter_date: string; rectifies_encounter_id: number } | null;
dentist_signer?: { id: number; name: string } | null;
```

(Read the existing types file path first via `grep -rn "interface Encounter" resources/js/types`.)

- [ ] **Step 4: Update `Patients/Show.vue` encounter timeline**

In the encounter list rendering (the timeline shown in the screenshot), add next to the status badge:

```vue
<span v-if="enc.status === 'completed' && enc.patient_signature_data && enc.dentist_signature_data" class="ml-1 text-emerald-600" title="Signed and locked">🔒</span>
<span v-else-if="enc.status === 'completed'" class="ml-1 text-amber-600" title="Completed without signatures (legacy)">⚠️</span>
<span v-if="enc.rectifies_encounter_id" class="ml-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">Rectifies #{{ enc.rectifies_encounter_id }}</span>
```

Above the timeline, next to the "New Encounter" button, add:

```vue
<a
    :href="route('patients.clinical-history', patient.id)"
    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
>
    <ArrowDownTrayIcon class="h-4 w-4" />
    {{ t('encounter.download_full_history') }}
</a>
```

Make sure `ArrowDownTrayIcon` is imported.

- [ ] **Step 5: Manual smoke test**

Start dev servers, log in as admin, open a patient, create an encounter with a treatment, click **Close and sign visit**, walk through the 3 steps, draw signatures, submit. Verify:
- Redirect to `Encounters/Show.vue` with the green locked banner.
- Edit button replaced by Rectify + Download PDF.
- Back on `Patients/Show.vue`, the encounter row has the 🔒 icon.
- "Download full clinical history" button generates a PDF.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Encounters/Editor.vue \
        resources/js/Pages/Encounters/Show.vue \
        resources/js/Pages/Patients/Show.vue \
        resources/js/types/index.ts \
        app/Http/Controllers/EncounterController.php
git commit -m "$(cat <<'EOF'
Wire SignWizard into editor + signed banner, rectify, PDF buttons

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 12: Audit Logs UI — surface `signed` + `rectified`

**Files:**
- Modify: `resources/js/Pages/AuditLogs/Index.vue`
- Modify: `resources/js/i18n/en.json`
- Modify: `resources/js/i18n/ro.json`

- [ ] **Step 1: Add i18n keys**

In both i18n files inside the `audit` (or equivalent) namespace, add:

```json
"action_signed": "Signed",
"action_rectified": "Rectified"
```

In `ro.json`:

```json
"action_signed": "Semnată",
"action_rectified": "Rectificată"
```

- [ ] **Step 2: Update the action filter dropdown**

Read `resources/js/Pages/AuditLogs/Index.vue`. Find the dropdown that filters by action and add the new entries. Example:

```vue
<option value="signed">{{ t('audit.action_signed') }}</option>
<option value="rectified">{{ t('audit.action_rectified') }}</option>
```

If the action filter is server-side, ensure the controller is passing the available action values. Read `app/Http/Controllers/AuditLogController.php` and confirm: if the actions list is dynamic from `DB::table('audit_logs')->distinct()->pluck('action')`, no controller change is needed; if it's a hardcoded array, append `'signed'` and `'rectified'`.

- [ ] **Step 3: Manual smoke**

Log in as admin, navigate to Audit Logs, filter by Signed and Rectified — confirm the entries created in previous tasks appear with their metadata.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/AuditLogs/Index.vue \
        resources/js/i18n/en.json \
        resources/js/i18n/ro.json
git commit -m "$(cat <<'EOF'
Surface signed and rectified actions in audit logs UI

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 13: Full regression sweep + push

- [ ] **Step 1: Run the full test suite**

```bash
/usr/local/opt/php@8.3/bin/php artisan test
```
Expected: every test passes. If anything pre-existing breaks (Auth tests, Profile tests), investigate before continuing — do not adjust the existing tests to fit new behaviour; fix the new code.

- [ ] **Step 2: Build the front-end**

```bash
npm run build
```
Expected: clean build with no TypeScript errors.

- [ ] **Step 3: Manual end-to-end walkthrough**

Log in with seed users; for each role:
- `admin@clinic.com` — can sign, rectify, download both PDFs.
- `maria@clinic.com` (dentist) — same.
- `elena@clinic.com` (assistant) — can edit treatments on `in_progress` but cannot sign, cannot rectify.
- `ana@clinic.com` (receptionist) — read-only on completed encounters, no signing.

Verify the GDPR Export ZIP contains `clinical-history.pdf` (extract and open).

- [ ] **Step 4: Push**

```bash
git push
```

---

## Self-review

Coverage check against the spec:
- **Migration / data model** — Task 1 ✓
- **`isLocked` helper** — Task 2 ✓
- **Policy lockdown on Encounter/Treatment/Attachment** — Task 3 ✓
- **FormRequest defence-in-depth** — Task 3 ✓
- **Sign endpoint with both signatures, IP, hash** — Task 4 ✓
- **Explicit `signed` audit log** — Task 4 ✓
- **Rectification flow + `rectified` audit log** — Task 5 ✓
- **Per-encounter PDF + downloadPdf policy** — Task 6 ✓
- **Consolidated clinical history PDF** — Task 7 ✓
- **GDPR Export ZIP integration** — Task 8 ✓
- **`SignaturePad.vue` extraction** — Task 9 ✓
- **3-step SignWizard modal with stored-dentist toggle** — Task 10 ✓
- **Editor "Close and sign" button** — Task 11 ✓
- **Show.vue signed banner, signatures block, rectify, PDF button** — Task 11 ✓
- **Patients/Show.vue lock icons + full history button** — Task 11 ✓
- **Audit Logs UI filter for signed/rectified** — Task 12 ✓
- **Regression sweep** — Task 13 ✓

All spec sections accounted for. No placeholders detected on review. The `dentist_signer` relation defined in Task 2 (`dentistSigner()`) and consumed in Task 11 and Tasks 6/7 blade templates uses consistent naming.
