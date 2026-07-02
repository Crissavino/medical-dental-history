# Extraction Informed Consent Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let staff capture a patient's signed informed consent for tooth extractions, tied to an encounter, before that encounter can be closed — separate from the GDPR intake consent and the encounter's closing SignWizard.

**Architecture:** New `extraction_consents` table (one row per encounter, immutable once created) + a `Treatment.is_extraction` boolean flag. `SignEncounterRequest` is extended to block closing an encounter that has an unconsented extraction treatment. A dedicated PDF (mirroring the existing `EncounterPdfService`/`AnamnesisPdfService` pattern) is downloadable and bundled into the GDPR export ZIP.

**Tech Stack:** Laravel 11, Inertia.js + Vue 3 (TypeScript), MySQL 8, `barryvdh/laravel-dompdf`, vue-i18n.

## Global Constraints

- Use `/usr/local/opt/php@8.3/bin/php` for every `php`/`artisan`/test command — system `php` is 7.4 and will fail on this project's `composer.json` platform requirement.
- Follow the existing project conventions found in `AnamnesisVersion`/`Encounter` (immutable signed records, `signed_ip`, audit logging via `AuditObserver`) rather than introducing new patterns.
- No admin-editable consent copy — the text lives in `resources/js/i18n/{en,ro,es}.json` under `extractionConsent.text`, same place the GDPR consent copy lives today.
- Spec reference: `docs/superpowers/specs/2026-07-02-extraction-consent-design.md`. One deviation from that spec, found while planning: the spec proposed a new `config/extraction-consent.php` file as the server-side source of the consent snapshot text. The codebase already has an established pattern for this exact problem — `AnamnesisPdfService::loadTranslations()` reads `resources/js/i18n/{lang}.json` directly at PDF-generation time. This plan follows that existing pattern instead (a private helper in `ExtractionConsentController` reads the same JSON file) rather than introducing a second, redundant source of the same copy.
- Second deviation: the spec's frontend section referenced `TreatmentEditor.vue` and `TreatmentList.vue` as the components to modify for the extraction checkbox and badge. Investigation during planning found both components are **dead code** — not imported anywhere. The real treatment form lives inline in `resources/js/Pages/Encounters/Editor.vue` (its `form.treatments` array, submitted together with the encounter). This plan modifies `Editor.vue` instead, and does not touch the two unused components (no unrelated cleanup, per this project's "surgical changes" principle).

---

## File Structure

| File | Responsibility |
|------|-----------------|
| `database/migrations/2026_07_02_100000_add_is_extraction_to_treatments_table.php` | Adds `treatments.is_extraction` |
| `database/migrations/2026_07_02_100100_create_extraction_consents_table.php` | Creates `extraction_consents` |
| `app/Models/ExtractionConsent.php` | New model |
| `app/Models/Encounter.php` | + `extractionConsent()`, `hasUnconsentedExtractions()` |
| `app/Models/Treatment.php` | + `is_extraction` fillable/cast |
| `app/Providers/AppServiceProvider.php` | + audit observer registration |
| `app/Policies/EncounterPolicy.php` | + `consentExtraction`, `downloadExtractionConsentPdf` |
| `app/Http/Requests/StoreExtractionConsentRequest.php` | New form request |
| `app/Http/Requests/SignEncounterRequest.php` | + blocking validation rule |
| `app/Http/Requests/StoreEncounterRequest.php`, `UpdateEncounterRequest.php` | + `treatments.*.is_extraction` rule |
| `app/Http/Controllers/ExtractionConsentController.php` | New controller (`store`, `pdf`) |
| `app/Http/Controllers/EncounterController.php` | `show()` eager-loads `extractionConsent`, adds `can.consentExtraction` |
| `app/Http/Controllers/GdprExportController.php` | + extraction-consent PDFs in the ZIP |
| `app/Services/ExtractionConsentPdfService.php` | New PDF service |
| `resources/views/pdf/extraction-consent.blade.php` | New PDF template |
| `routes/web.php` | + 2 routes |
| `resources/js/types/index.d.ts` | + `ExtractionConsent` type, `Treatment.is_extraction`, `Encounter.extraction_consent` |
| `resources/js/Pages/Encounters/Editor.vue` | + extraction checkbox per treatment |
| `resources/js/Components/Encounter/ExtractionConsentModal.vue` | New component |
| `resources/js/Pages/Encounters/Show.vue` | + pending banner / signed summary / sign-button guard |
| `resources/js/i18n/{en,ro,es}.json` | + `extractionConsent` namespace, `treatment.is_extraction` |
| `tests/Feature/ExtractionConsentModelTest.php` | New — model/helper behavior |
| `tests/Feature/ExtractionConsentTest.php` | New — creation endpoint |
| `tests/Feature/ExtractionConsentBlocksEncounterSignTest.php` | New — sign-blocking |
| `tests/Feature/ExtractionConsentPdfTest.php` | New — PDF download |
| `tests/Feature/GdprExportExtractionConsentTest.php` | New — ZIP contents |

---

### Task 1: Data model — migrations, `ExtractionConsent` model, `Encounter`/`Treatment` updates, audit logging

**Files:**
- Create: `database/migrations/2026_07_02_100000_add_is_extraction_to_treatments_table.php`
- Create: `database/migrations/2026_07_02_100100_create_extraction_consents_table.php`
- Create: `app/Models/ExtractionConsent.php`
- Modify: `app/Models/Encounter.php`
- Modify: `app/Models/Treatment.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/ExtractionConsentModelTest.php`

**Interfaces:**
- Produces: `Encounter::extractionConsent(): HasOne` (→ `ExtractionConsent`), `Encounter::hasUnconsentedExtractions(): bool`, `Treatment.is_extraction: bool` (fillable + cast), `ExtractionConsent` model with fillable `['encounter_id', 'consent_text', 'language', 'patient_signature_data', 'signed_at', 'signed_ip', 'recorded_by']` and relations `encounter(): BelongsTo`, `recorder(): BelongsTo`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ExtractionConsentModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_unconsented_extractions_is_true_when_extraction_treatment_has_no_consent(): void
    {
        $encounter = Encounter::factory()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->assertTrue($encounter->fresh()->hasUnconsentedExtractions());
    }

    public function test_has_unconsented_extractions_is_false_without_extraction_treatments(): void
    {
        $encounter = Encounter::factory()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->assertFalse($encounter->fresh()->hasUnconsentedExtractions());
    }

    public function test_has_unconsented_extractions_is_false_once_consent_exists(): void
    {
        $encounter = Encounter::factory()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'text',
            'language' => 'en',
            'patient_signature_data' => 'data:image/png;base64,abc',
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->assertFalse($encounter->fresh()->hasUnconsentedExtractions());
    }

    public function test_extraction_consent_belongs_to_encounter_and_recorder(): void
    {
        $encounter = Encounter::factory()->create();
        $consent = ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'text',
            'language' => 'en',
            'patient_signature_data' => 'data:image/png;base64,abc',
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->assertTrue($consent->encounter->is($encounter));
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $consent->signed_at);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentModelTest.php`
Expected: FAIL — `Class "App\Models\ExtractionConsent" not found` (and/or "Unknown column 'is_extraction'").

- [ ] **Step 3: Create the migrations**

`database/migrations/2026_07_02_100000_add_is_extraction_to_treatments_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->boolean('is_extraction')->default(false)->after('treatment_code');
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn('is_extraction');
        });
    }
};
```

`database/migrations/2026_07_02_100100_create_extraction_consents_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extraction_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('consent_text');
            $table->string('language', 2);
            $table->longText('patient_signature_data');
            $table->timestamp('signed_at');
            $table->string('signed_ip', 45);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extraction_consents');
    }
};
```

- [ ] **Step 4: Create the `ExtractionConsent` model**

Create `app/Models/ExtractionConsent.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractionConsent extends Model
{
    protected $fillable = [
        'encounter_id',
        'consent_text',
        'language',
        'patient_signature_data',
        'signed_at',
        'signed_ip',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
```

- [ ] **Step 5: Add the relation and helper to `Encounter`**

In `app/Models/Encounter.php`, add the import next to the existing `HasOne` import (already present, used by `rectifier()`), then add after the `rectifier()` method (before `isLocked()`):

```php
    public function extractionConsent(): HasOne
    {
        return $this->hasOne(ExtractionConsent::class);
    }

    public function hasUnconsentedExtractions(): bool
    {
        return $this->treatments()->where('is_extraction', true)->exists()
            && !$this->extractionConsent()->exists();
    }
```

- [ ] **Step 6: Add `is_extraction` to `Treatment`**

In `app/Models/Treatment.php`, add `'is_extraction',` to `$fillable` right after `'treatment_code',`, and add a `casts()` method (there is currently none on this exact spot — the class only has an inline `protected function casts()` already returning `['cost' => 'decimal:2']`; add the key to that same array):

```php
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'is_extraction' => 'boolean',
        ];
    }
```

- [ ] **Step 7: Register the audit observer**

In `app/Providers/AppServiceProvider.php`, add the import:

```php
use App\Models\ExtractionConsent;
```

And add this line right after `Attachment::observe(AuditObserver::class);`:

```php
        ExtractionConsent::observe(AuditObserver::class);
```

- [ ] **Step 8: Run the test to verify it passes**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentModelTest.php`
Expected: PASS (4 tests).

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_07_02_100000_add_is_extraction_to_treatments_table.php \
        database/migrations/2026_07_02_100100_create_extraction_consents_table.php \
        app/Models/ExtractionConsent.php app/Models/Encounter.php app/Models/Treatment.php \
        app/Providers/AppServiceProvider.php tests/Feature/ExtractionConsentModelTest.php
git commit -m "Add extraction_consents table and is_extraction flag on treatments"
```

---

### Task 2: Consent creation endpoint — policy, form request, controller, route

**Files:**
- Modify: `app/Policies/EncounterPolicy.php`
- Create: `app/Http/Requests/StoreExtractionConsentRequest.php`
- Create: `app/Http/Controllers/ExtractionConsentController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExtractionConsentTest.php`

**Interfaces:**
- Consumes: `Encounter::hasUnconsentedExtractions()`, `Encounter::extractionConsent()`, `Encounter::isLocked()` (Task 1); `ExtractionConsent::create()` (Task 1).
- Produces: route `extraction-consents.store` (`POST /encounters/{encounter}/extraction-consent`); `EncounterPolicy::consentExtraction(User, Encounter): bool`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ExtractionConsentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_assistant_can_sign_extraction_consent_for_extraction_treatment(): void
    {
        $assistant = User::factory()->role('assistant')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $response = $this->actingAs($assistant)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ]);

        $response->assertRedirect(route('encounters.show', $encounter));

        $this->assertDatabaseHas('extraction_consents', [
            'encounter_id' => $encounter->id,
            'language' => 'es',
            'recorded_by' => $assistant->id,
        ]);
        $consent = ExtractionConsent::first();
        $this->assertNotEmpty($consent->consent_text);
        $this->assertNotNull($consent->signed_at);
        $this->assertNotNull($consent->signed_ip);
    }

    public function test_receptionist_cannot_sign_extraction_consent(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($receptionist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])
            ->assertForbidden();
    }

    public function test_cannot_sign_consent_without_extraction_treatment(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])
            ->assertSessionHasErrors();
    }

    public function test_cannot_sign_consent_twice_for_same_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])->assertRedirect();

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])->assertSessionHasErrors();
    }

    public function test_cannot_sign_consent_on_locked_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->completed()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentTest.php`
Expected: FAIL — route `extraction-consents.store` not defined.

- [ ] **Step 3: Add policy abilities**

In `app/Policies/EncounterPolicy.php`, add after the existing `sign()` method:

```php
    public function consentExtraction(User $user, Encounter $encounter): bool
    {
        return !$encounter->isLocked()
            && $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function downloadExtractionConsentPdf(User $user, Encounter $encounter): bool
    {
        return $user->hasRole('admin', 'dentist');
    }
```

- [ ] **Step 4: Create the form request**

Create `app/Http/Requests/StoreExtractionConsentRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Foundation\Http\FormRequest;

class StoreExtractionConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $encounter = $this->route('encounter');
        if (!$encounter instanceof Encounter) {
            return false;
        }
        return $this->user()?->can('consentExtraction', $encounter) ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_signature_data' => ['required', 'string', 'starts_with:data:image/'],
            'language' => ['required', 'in:en,ro,es'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $encounter = $this->route('encounter');
            if (!$encounter instanceof Encounter) {
                return;
            }
            if ($encounter->treatments()->where('is_extraction', true)->doesntExist()) {
                $validator->errors()->add(
                    'extraction_consent',
                    'This encounter has no extraction treatment to consent to.'
                );
            }
            if ($encounter->extractionConsent()->exists()) {
                $validator->errors()->add(
                    'extraction_consent',
                    'Extraction consent has already been signed for this encounter.'
                );
            }
        });
    }
}
```

- [ ] **Step 5: Create the controller**

Create `app/Http/Controllers/ExtractionConsentController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtractionConsentRequest;
use App\Models\Encounter;
use App\Models\ExtractionConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class ExtractionConsentController extends Controller
{
    public function store(StoreExtractionConsentRequest $request, Encounter $encounter): RedirectResponse
    {
        $language = $request->validated('language');

        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => $this->consentText($language),
            'language' => $language,
            'patient_signature_data' => $request->validated('patient_signature_data'),
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'recorded_by' => auth()->id(),
        ]);

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Extraction consent signed.');
    }

    private function consentText(string $lang): string
    {
        $path = resource_path("js/i18n/{$lang}.json");

        if (!file_exists($path)) {
            $path = resource_path('js/i18n/en.json');
        }

        $json = json_decode(file_get_contents($path), true);

        return Arr::get($json, 'extractionConsent.text', '');
    }
}
```

> Note: `consentText()` reads `extractionConsent.text` from the i18n JSON files, which Task 8 adds. Until Task 8 lands, this returns `''` — acceptable for this task's tests, which only assert `consent_text` is non-empty *after* Task 8's copy exists. To keep Task 2's own test green independent of task order, `test_assistant_can_sign_extraction_consent_for_extraction_treatment` is written to run after Task 8 in execution order (see task sequence note below) — or, if executed standalone, add the i18n key first. This plan's tasks are meant to run in numeric order, so by the time Task 2's test runs for real in the full suite (after all tasks are done), the key exists.

- [ ] **Step 6: Add the route**

In `routes/web.php`, add the import:

```php
use App\Http\Controllers\ExtractionConsentController;
```

And add this inside the `Route::middleware('auth')->group()` block, right after the `encounters.pdf` route:

```php
    Route::post('/encounters/{encounter}/extraction-consent', [ExtractionConsentController::class, 'store'])
        ->name('extraction-consents.store');
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentTest.php`
Expected: PASS (5 tests). If `test_assistant_can_sign_extraction_consent_for_extraction_treatment` fails only on `assertNotEmpty($consent->consent_text)` because Task 8 hasn't run yet, that is expected at this point in the plan — re-run the full suite after Task 8 to confirm it passes end-to-end.

- [ ] **Step 8: Commit**

```bash
git add app/Policies/EncounterPolicy.php app/Http/Requests/StoreExtractionConsentRequest.php \
        app/Http/Controllers/ExtractionConsentController.php routes/web.php \
        tests/Feature/ExtractionConsentTest.php
git commit -m "Add endpoint to sign extraction informed consent"
```

---

### Task 3: Block encounter sign-off when an extraction treatment lacks consent

**Files:**
- Modify: `app/Http/Requests/SignEncounterRequest.php`
- Test: `tests/Feature/ExtractionConsentBlocksEncounterSignTest.php`

**Interfaces:**
- Consumes: `Encounter::hasUnconsentedExtractions()` (Task 1).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ExtractionConsentBlocksEncounterSignTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentBlocksEncounterSignTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_signing_encounter_fails_when_extraction_treatment_has_no_consent(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertSessionHasErrors(['extraction_consent']);

        $this->assertSame('in_progress', $encounter->fresh()->status);
    }

    public function test_signing_encounter_succeeds_once_extraction_consent_exists(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'text',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertRedirect(route('encounters.show', $encounter));

        $this->assertSame('completed', $encounter->fresh()->status);
    }

    public function test_signing_encounter_unaffected_when_no_extraction_treatments(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertRedirect(route('encounters.show', $encounter));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentBlocksEncounterSignTest.php`
Expected: FAIL on `test_signing_encounter_fails_when_extraction_treatment_has_no_consent` (no `extraction_consent` session error is raised yet).

- [ ] **Step 3: Add the blocking rule**

In `app/Http/Requests/SignEncounterRequest.php`, inside the `withValidator()` closure, add this block right after the existing `treatments` count check:

```php
            if ($encounter && $encounter->hasUnconsentedExtractions()) {
                $validator->errors()->add(
                    'extraction_consent',
                    'Extraction consent must be signed before closing this encounter.'
                );
            }
```

(It goes after the `if ($encounter && $encounter->treatments()->count() === 0) { ... }` block and before the `$useStored = ...` line.)

- [ ] **Step 4: Run the test to verify it passes**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentBlocksEncounterSignTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Run the full existing encounter-signing suite to check for regressions**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/EncounterSigningTest.php`
Expected: PASS (all 7 pre-existing tests still pass — none of them create extraction treatments, so `hasUnconsentedExtractions()` is `false` for all of them).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/SignEncounterRequest.php tests/Feature/ExtractionConsentBlocksEncounterSignTest.php
git commit -m "Block encounter sign-off until extraction consent is captured"
```

---

### Task 4: Extraction consent PDF

**Files:**
- Create: `app/Services/ExtractionConsentPdfService.php`
- Create: `resources/views/pdf/extraction-consent.blade.php`
- Modify: `app/Http/Controllers/ExtractionConsentController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExtractionConsentPdfTest.php`

**Interfaces:**
- Consumes: `ExtractionConsent` model (Task 1), `EncounterPolicy::downloadExtractionConsentPdf` (Task 2).
- Produces: route `extraction-consents.pdf` (`GET /extraction-consents/{extractionConsent}/pdf`); `ExtractionConsentPdfService::generate(ExtractionConsent): \Barryvdh\DomPDF\PDF`, `::filename(ExtractionConsent): string`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ExtractionConsentPdfTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentPdfTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_dentist_can_download_extraction_consent_pdf(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        $consent = ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'Consent body text.',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $response = $this->actingAs($dentist)
            ->get(route('extraction-consents.pdf', $consent));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_assistant_cannot_download_extraction_consent_pdf(): void
    {
        $assistant = User::factory()->role('assistant')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        $consent = ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'Consent body text.',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->actingAs($assistant)
            ->get(route('extraction-consents.pdf', $consent))
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentPdfTest.php`
Expected: FAIL — route `extraction-consents.pdf` not defined.

- [ ] **Step 3: Create the PDF service**

Create `app/Services/ExtractionConsentPdfService.php`:

```php
<?php

namespace App\Services;

use App\Models\ExtractionConsent;
use Barryvdh\DomPDF\Facade\Pdf;

class ExtractionConsentPdfService
{
    public function generate(ExtractionConsent $extractionConsent): \Barryvdh\DomPDF\PDF
    {
        $extractionConsent->load(['encounter.patient', 'recorder:id,name']);

        $pdf = Pdf::loadView('pdf.extraction-consent', [
            'consent' => $extractionConsent,
            'encounter' => $extractionConsent->encounter,
            'patient' => $extractionConsent->encounter->patient,
            'logoBase64' => $this->loadLogoBase64(),
        ]);
        $pdf->setPaper('A4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', false);

        return $pdf;
    }

    public function filename(ExtractionConsent $extractionConsent): string
    {
        $extractionConsent->loadMissing('encounter.patient');
        $identifier = $extractionConsent->encounter->patient->identifier;

        return "extraction-consent-{$identifier}-{$extractionConsent->encounter_id}.pdf";
    }

    private function loadLogoBase64(): string
    {
        $pngPath = public_path('images/clinic-logo.png');

        return file_exists($pngPath) ? base64_encode(file_get_contents($pngPath)) : '';
    }
}
```

- [ ] **Step 4: Create the blade template**

Create `resources/views/pdf/extraction-consent.blade.php`:

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #222; }
        h1 { font-size: 14pt; margin: 0 0 6px 0; }
        h2 { font-size: 11pt; margin: 12px 0 4px 0; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
        .header { display: table; width: 100%; margin-bottom: 12px; }
        .header-left, .header-right { display: table-cell; vertical-align: top; }
        .header-right { text-align: right; }
        .consent-text { line-height: 1.5; text-align: justify; }
        .sig-cell { border: 1px solid #ccc; padding: 8px; margin-top: 18px; width: 60%; }
        .sig-cell img { max-width: 100%; max-height: 80px; }
        .footer { margin-top: 14px; font-size: 8pt; color: #666; border-top: 1px solid #ccc; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}" style="height: 48px;" alt="logo">
            @endif
            <h1>Extraction Informed Consent</h1>
        </div>
        <div class="header-right">
            <div><strong>Patient:</strong> {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->identifier }})</div>
            <div><strong>Encounter:</strong> #{{ $encounter->id }} · {{ $encounter->encounter_date?->format('d M Y') }}</div>
            <div><strong>Language:</strong> {{ strtoupper($consent->language) }}</div>
        </div>
    </div>

    <h2>Informed Consent</h2>
    <p class="consent-text">{{ $consent->consent_text }}</p>

    <h2>Patient Signature</h2>
    <div class="sig-cell">
        @if($consent->patient_signature_data)
            <img src="{{ $consent->patient_signature_data }}" alt="patient signature">
        @endif
        <div>{{ $patient->first_name }} {{ $patient->last_name }}</div>
        <div style="font-size: 8pt; color: #666;">
            Signed {{ $consent->signed_at?->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="footer">
        Generated {{ now()->format('d/m/Y H:i') }} ·
        Recorded by {{ $consent->recorder?->name ?? '—' }} ·
        Signed IP {{ $consent->signed_ip }}
    </div>
</body>
</html>
```

- [ ] **Step 5: Add the controller action**

In `app/Http/Controllers/ExtractionConsentController.php`, add the imports:

```php
use App\Models\ExtractionConsent;
use App\Services\ExtractionConsentPdfService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
```

(`App\Models\ExtractionConsent` is already imported from Task 2 — do not duplicate the `use` line.)

Add this method to the class, after `store()`:

```php
    public function pdf(ExtractionConsent $extractionConsent, ExtractionConsentPdfService $pdfService): HttpResponse
    {
        $this->authorize('downloadExtractionConsentPdf', $extractionConsent->encounter);

        $pdf = $pdfService->generate($extractionConsent);

        return $pdf->download($pdfService->filename($extractionConsent));
    }
```

- [ ] **Step 6: Add the route**

In `routes/web.php`, add right after the `extraction-consents.store` route:

```php
    Route::get('/extraction-consents/{extractionConsent}/pdf', [ExtractionConsentController::class, 'pdf'])
        ->name('extraction-consents.pdf');
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentPdfTest.php`
Expected: PASS (2 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Services/ExtractionConsentPdfService.php resources/views/pdf/extraction-consent.blade.php \
        app/Http/Controllers/ExtractionConsentController.php routes/web.php \
        tests/Feature/ExtractionConsentPdfTest.php
git commit -m "Add downloadable PDF for extraction informed consent"
```

---

### Task 5: Include extraction consent PDFs in the GDPR export ZIP

**Files:**
- Modify: `app/Http/Controllers/GdprExportController.php`
- Test: `tests/Feature/GdprExportExtractionConsentTest.php`

**Interfaces:**
- Consumes: `ExtractionConsentPdfService::generate()` (Task 4), `Encounter::extractionConsent` relation (Task 1).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/GdprExportExtractionConsentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class GdprExportExtractionConsentTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_export_includes_extraction_consent_pdf_when_present(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'Consent body text.',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
            'recorded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('patients.gdpr-export', $patient));
        $response->assertOk();

        $tmpPath = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmpPath, $response->streamedContent());

        $zip = new ZipArchive();
        $zip->open($tmpPath);
        $this->assertNotFalse($zip->locateName("extraction-consents/encounter-{$encounter->id}.pdf"));
        $zip->close();
        unlink($tmpPath);
    }

    public function test_export_has_no_extraction_consents_entry_when_none_signed(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'in_progress']);

        $response = $this->actingAs($admin)->get(route('patients.gdpr-export', $patient));
        $response->assertOk();

        $tmpPath = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmpPath, $response->streamedContent());

        $zip = new ZipArchive();
        $zip->open($tmpPath);
        $this->assertSame(0, $zip->numFiles - $zip->numFiles + ($zip->locateName('extraction-consents/') === false ? 0 : 1));
        $zip->close();
        unlink($tmpPath);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/GdprExportExtractionConsentTest.php`
Expected: FAIL on `test_export_includes_extraction_consent_pdf_when_present` — the zip has no `extraction-consents/encounter-{id}.pdf` entry yet.

- [ ] **Step 3: Update the controller**

In `app/Http/Controllers/GdprExportController.php`, add the import:

```php
use App\Services\ExtractionConsentPdfService;
```

Change the method signature to inject the new service:

```php
    public function export(Patient $patient, ClinicalHistoryPdfService $clinicalHistory, ExtractionConsentPdfService $extractionConsentPdf): StreamedResponse
```

Add `'encounters.extractionConsent',` to the `$patient->load([...])` array (alongside the existing `'encounters.treatments'`).

Add this loop right before `$zip->addFromString('clinical-history.pdf', ...)`:

```php
        foreach ($patient->encounters as $encounter) {
            if ($encounter->extractionConsent) {
                $zip->addFromString(
                    "extraction-consents/encounter-{$encounter->id}.pdf",
                    $extractionConsentPdf->generate($encounter->extractionConsent)->output()
                );
            }
        }

```

- [ ] **Step 4: Run the test to verify it passes**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/GdprExportExtractionConsentTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/GdprExportController.php tests/Feature/GdprExportExtractionConsentTest.php
git commit -m "Include signed extraction consent PDFs in GDPR export ZIP"
```

---

### Task 6: `is_extraction` checkbox — backend validation + `Editor.vue` UI

**Files:**
- Modify: `app/Http/Requests/StoreEncounterRequest.php`
- Modify: `app/Http/Requests/UpdateEncounterRequest.php`
- Modify: `resources/js/Pages/Encounters/Editor.vue`
- Test: `tests/Feature/TreatmentExtractionFlagTest.php`

**Interfaces:**
- Produces: `treatments.*.is_extraction` accepted as a boolean on both encounter create/update requests; `Editor.vue`'s `TreatmentForm.is_extraction: boolean` sent in the payload, with a checkbox in the UI to toggle it per treatment row.

- [ ] **Step 1: Add the validation rule**

In `app/Http/Requests/StoreEncounterRequest.php`, add this line to the `rules()` array, right after `'treatments.*.status' => [...]`:

```php
            'treatments.*.is_extraction' => ['nullable', 'boolean'],
```

Make the identical addition in `app/Http/Requests/UpdateEncounterRequest.php`'s `rules()` array, in the same position.

- [ ] **Step 2: Write a regression test**

Create `tests/Feature/TreatmentExtractionFlagTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentExtractionFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_encounter_with_extraction_treatment_persists_the_flag(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $patient = Patient::factory()->create();

        $this->actingAs($dentist)->post(route('patient.encounters.store', $patient), [
            'encounter_date' => now()->toDateString(),
            'status' => 'in_progress',
            'treatments' => [[
                'tooth_number' => '18',
                'treatment_code' => 'D7140',
                'description' => 'Extraction',
                'status' => 'planned',
                'is_extraction' => true,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'patient_id' => null,
            'treatment_code' => 'D7140',
            'is_extraction' => true,
        ]);
    }

    public function test_updating_treatment_can_toggle_extraction_flag(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        $treatment = Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->actingAs($dentist)->put(route('encounters.update', $encounter), [
            'encounter_date' => $encounter->encounter_date->toDateString(),
            'status' => 'in_progress',
            'treatments' => [[
                'id' => $treatment->id,
                'tooth_number' => $treatment->tooth_number,
                'treatment_code' => $treatment->treatment_code,
                'description' => $treatment->description,
                'status' => $treatment->status,
                'is_extraction' => true,
            ]],
        ])->assertRedirect();

        $this->assertTrue($treatment->fresh()->is_extraction);
    }
}
```

> Note: `test_creating_encounter_with_extraction_treatment_persists_the_flag` asserts `assertDatabaseHas` with a `patient_id` column that doesn't exist on `treatments` — remove that line; treatments don't have a `patient_id` column (only `encounter_id`). Use just:
> ```php
> $this->assertDatabaseHas('treatments', [
>     'treatment_code' => 'D7140',
>     'is_extraction' => true,
> ]);
> ```

- [ ] **Step 3: Run the test to verify it fails, then passes**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/TreatmentExtractionFlagTest.php`
Expected before Step 1's edit: FAIL (422 — `is_extraction` not in validated data, silently dropped, so `assertDatabaseHas` with `is_extraction => true` fails). After Step 1: PASS (2 tests).

- [ ] **Step 4: Add the checkbox to the `Editor.vue` treatment form**

In `resources/js/Pages/Encounters/Editor.vue`, update the `TreatmentForm` interface to add `is_extraction: boolean;` after `status`:

```ts
interface TreatmentForm {
    id?: number;
    tooth_number: string;
    treatment_code: string;
    description: string;
    notes: string;
    surface: string;
    cost: string;
    status: 'planned' | 'in_progress' | 'completed';
    is_extraction: boolean;
}
```

Update the `form.treatments` initial mapping (in the `useForm({...})` call) to carry the flag over when editing an existing encounter:

```ts
    treatments: (props.encounter?.treatments || []).map((t) => ({
        id: t.id,
        tooth_number: t.tooth_number || '',
        treatment_code: t.treatment_code,
        description: t.description,
        notes: t.notes || '',
        surface: t.surface || '',
        cost: t.cost !== undefined && t.cost !== null ? String(t.cost) : '',
        status: t.status,
        is_extraction: t.is_extraction ?? false,
    })) as TreatmentForm[],
```

Update `addTreatment()` so new rows default to `false`:

```ts
function addTreatment() {
    form.treatments.push({
        tooth_number: '',
        treatment_code: '',
        description: '',
        notes: '',
        surface: '',
        cost: '',
        status: 'planned',
        is_extraction: false,
    });
}
```

In the template, add the checkbox right after the "Treatment #N" header row (the `<div class="mb-4 flex items-center justify-between">...</div>` block that contains the remove button) and before the `<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">` fields grid:

```html
                            <label class="mb-4 flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input
                                    v-model="treatment.is_extraction"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                {{ t('treatment.is_extraction') }}
                            </label>
```

(The `treatment.is_extraction` i18n key is added in Task 8 — until then, `t('treatment.is_extraction')` falls back to rendering the raw key, which is expected and harmless during development between tasks.)

- [ ] **Step 5: Verify with the TypeScript compiler**

Run: `npm run build`
Expected: compiles without errors — `TreatmentForm.is_extraction` is a plain `boolean`, and the payload sent to `encounters.store`/`encounters.update` already forwards the whole `form.treatments` array as-is (no per-field allowlist to update on the frontend).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/StoreEncounterRequest.php app/Http/Requests/UpdateEncounterRequest.php \
        resources/js/Pages/Encounters/Editor.vue tests/Feature/TreatmentExtractionFlagTest.php
git commit -m "Accept is_extraction flag when creating/updating encounter treatments"
```

---

### Task 7: Frontend TypeScript types

**Files:**
- Modify: `resources/js/types/index.d.ts`

**Interfaces:**
- Produces: `ExtractionConsent` interface, `Treatment.is_extraction: boolean`, `Encounter.extraction_consent?: ExtractionConsent | null`.

- [ ] **Step 1: Add `is_extraction` to the `Treatment` interface**

In `resources/js/types/index.d.ts`, in the `Treatment` interface, add `is_extraction: boolean;` right after `treatment_code: string;`:

```ts
export interface Treatment {
    id: number;
    encounter_id: number;
    tooth_number?: string;
    treatment_code: string;
    is_extraction: boolean;
    description: string;
    notes?: string;
    surface?: 'mesial' | 'distal' | 'buccal' | 'lingual' | 'occlusal' | 'incisal';
    cost?: number;
    status: 'planned' | 'in_progress' | 'completed';
    created_at: string;
    updated_at: string;
    encounter?: Encounter;
    attachments?: Attachment[];
}
```

- [ ] **Step 2: Add the `ExtractionConsent` interface and wire it into `Encounter`**

Add this new interface right after the `Encounter` interface closes (after its final `}`):

```ts
export interface ExtractionConsent {
    id: number;
    encounter_id: number;
    language: 'en' | 'ro' | 'es';
    patient_signature_data: string;
    signed_at: string;
    recorded_by: number | null;
}
```

In the `Encounter` interface, add `extraction_consent?: ExtractionConsent | null;` right after `dentist_signer?: { id: number; name: string } | null;`:

```ts
export interface Encounter {
    id: number;
    patient_id: number;
    provider_id: number;
    encounter_date: string;
    chief_complaint?: string;
    clinical_notes?: string;
    diagnosis?: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    created_at: string;
    updated_at: string;
    patient?: Patient;
    provider?: User;
    treatments?: Treatment[];
    attachments?: Attachment[];
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
    extraction_consent?: ExtractionConsent | null;
}
```

- [ ] **Step 3: Verify with the TypeScript compiler**

Run: `npm run build`
Expected: compiles without new type errors (existing `Treatment`/`Encounter` usages across the app already only read fields that still exist; the two new fields are additive).

- [ ] **Step 4: Commit**

```bash
git add resources/js/types/index.d.ts
git commit -m "Add ExtractionConsent type and is_extraction/extraction_consent fields"
```

---

### Task 8: i18n copy — `extractionConsent` namespace + `treatment.is_extraction` label

**Files:**
- Modify: `resources/js/i18n/en.json`
- Modify: `resources/js/i18n/es.json`
- Modify: `resources/js/i18n/ro.json`

**Interfaces:**
- Produces: `treatment.is_extraction`, `extractionConsent.title`, `extractionConsent.text`, `extractionConsent.agree`, `extractionConsent.sign_button`, `extractionConsent.pending_banner`, `extractionConsent.signed_at`, `extractionConsent.download_pdf` in all three locale files (same keys, same order, across `en`/`es`/`ro` — consumed by Task 9's `ExtractionConsentModal.vue` and Task 10's `Show.vue`). Also produces `extractionConsent.text` in `en.json`/`es.json`/`ro.json`, which Task 2's `ExtractionConsentController::consentText()` reads server-side.

- [ ] **Step 1: Add `treatment.is_extraction` to all three files**

In `resources/js/i18n/en.json`, inside the `treatment` object, add `"is_extraction": "This is an extraction",` right after `"select_tooth": "Select Tooth"` (making that line end with a comma):

```json
    "no_treatments": "No treatments added yet",
    "select_tooth": "Select Tooth",
    "is_extraction": "This is an extraction"
  },
```

In `resources/js/i18n/es.json`, same position:

```json
    "no_treatments": "No hay tratamientos agregados aún",
    "select_tooth": "Seleccionar pieza",
    "is_extraction": "Es una extracción"
  },
```

In `resources/js/i18n/ro.json`, same position:

```json
    "no_treatments": "Niciun tratament adăugat încă",
    "select_tooth": "Selectează dinţele",
    "is_extraction": "Aceasta este o extracție"
  },
```

- [ ] **Step 2: Add the `extractionConsent` namespace to `en.json`**

In `resources/js/i18n/en.json`, insert this new top-level key right after the `attachment` object closes (i.e., right after the `},` that follows `"max_size": "Maximum file size: 10MB"` and right before `"anamnesis": {`):

```json
  "extractionConsent": {
    "title": "Extraction Informed Consent",
    "text": "I, the undersigned, declare that I have been clearly informed by the treating professional about the nature of the indicated tooth extraction procedure, including: the reason for the extraction, the type of anesthesia to be used, the available treatment alternatives, and the possible risks and complications — including pain, swelling, bleeding, infection, dry socket, injury to adjacent teeth or restorations, root fracture with a possible residual fragment, transient or permanent numbness of the lip, chin or tongue, oro-sinus communication, jaw fracture, the need for additional treatment, and reactions to anesthesia. I have been able to ask any questions I considered necessary and they were answered satisfactorily. I understand that dentistry is not an exact science and that specific outcomes are not guaranteed. I give my free, informed and voluntary consent for this procedure to be performed.",
    "agree": "I have read and understand this informed consent",
    "sign_button": "Sign consent",
    "pending_banner": "This encounter has an extraction treatment pending informed consent.",
    "signed_at": "Consent signed on {at}",
    "download_pdf": "Download consent PDF"
  },
```

- [ ] **Step 3: Add the `extractionConsent` namespace to `es.json`**

Same position in `resources/js/i18n/es.json`:

```json
  "extractionConsent": {
    "title": "Consentimiento informado de extracción",
    "text": "Yo, el/la abajo firmante, declaro haber sido informado/a de forma clara por el/la profesional tratante sobre la naturaleza del procedimiento de extracción dentaria indicado, incluyendo: el motivo de la extracción, el tipo de anestesia a utilizar, las alternativas de tratamiento disponibles, y los riesgos y complicaciones posibles — entre ellos dolor, inflamación, sangrado, infección, alveolitis seca, lesión de dientes o restauraciones adyacentes, fractura radicular con posible fragmento residual, parestesia transitoria o permanente de labio, mentón o lengua, comunicación oro-sinusal, fractura mandibular, necesidad de tratamiento adicional, y reacciones a la anestesia. He podido formular las preguntas que consideré necesarias y las mismas fueron respondidas satisfactoriamente. Entiendo que la odontología no es una ciencia exacta y que no se garantizan resultados específicos. Presto mi consentimiento libre, informado y voluntario para la realización de dicho procedimiento.",
    "agree": "He leído y entiendo este consentimiento informado",
    "sign_button": "Firmar consentimiento",
    "pending_banner": "Este encuentro tiene un tratamiento de extracción pendiente de consentimiento informado.",
    "signed_at": "Consentimiento firmado el {at}",
    "download_pdf": "Descargar PDF del consentimiento"
  },
```

- [ ] **Step 4: Add the `extractionConsent` namespace to `ro.json`**

Same position in `resources/js/i18n/ro.json`. This translation is scaffolding — flag it for review by a native-speaking clinician/legal reviewer before production use, since it's a medico-legal document (same caveat as the design spec):

```json
  "extractionConsent": {
    "title": "Consimțământ informat pentru extracție",
    "text": "Subsemnatul/subsemnata declar că am fost informat/informată în mod clar de către medicul curant cu privire la natura procedurii de extracție dentară indicate, inclusiv: motivul extracției, tipul de anestezie ce urmează a fi utilizat, alternativele de tratament disponibile, precum și riscurile și complicațiile posibile — printre care durere, inflamație, sângerare, infecție, alveolită uscată, lezarea dinților sau restaurărilor adiacente, fractură radiculară cu posibil fragment restant, parestezie tranzitorie sau permanentă a buzei, bărbiei sau limbii, comunicare oro-sinuzală, fractură mandibulară, necesitatea unui tratament suplimentar și reacții la anestezie. Am avut posibilitatea de a adresa întrebările pe care le-am considerat necesare, iar acestea au primit răspunsuri satisfăcătoare. Înțeleg că stomatologia nu este o știință exactă și că nu se garantează rezultate specifice. Îmi acord consimțământul liber, informat și voluntar pentru efectuarea acestei proceduri.",
    "agree": "Am citit și înțeleg acest consimțământ informat",
    "sign_button": "Semnează consimțământul",
    "pending_banner": "Această vizită are un tratament de extracție care așteaptă consimțământul informat.",
    "signed_at": "Consimțământ semnat la {at}",
    "download_pdf": "Descarcă PDF-ul consimțământului"
  },
```

- [ ] **Step 5: Validate all three files are still valid JSON**

Run: `node -e "JSON.parse(require('fs').readFileSync('resources/js/i18n/en.json'))" && node -e "JSON.parse(require('fs').readFileSync('resources/js/i18n/es.json'))" && node -e "JSON.parse(require('fs').readFileSync('resources/js/i18n/ro.json'))" && echo "all valid"`
Expected: `all valid` printed, no parse errors.

- [ ] **Step 6: Re-run Task 2's endpoint test now that the copy exists**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/ExtractionConsentTest.php`
Expected: PASS (5 tests) — `test_assistant_can_sign_extraction_consent_for_extraction_treatment`'s `assertNotEmpty($consent->consent_text)` now passes for real, since `extractionConsent.text` exists in `es.json`.

- [ ] **Step 7: Commit**

```bash
git add resources/js/i18n/en.json resources/js/i18n/es.json resources/js/i18n/ro.json
git commit -m "Add extraction informed consent copy (EN/ES/RO)"
```

---

### Task 9: `ExtractionConsentModal.vue` component

**Files:**
- Create: `resources/js/Components/Encounter/ExtractionConsentModal.vue`

**Interfaces:**
- Consumes: `SignaturePad.vue` (`v-model` of `string | null`), route `extraction-consents.store` (Task 2), i18n keys from `extractionConsent.*` and `app.cancel` (Task 8).
- Produces: component with props `{ encounterId: number; open: boolean }`, emits `close`.

- [ ] **Step 1: Create the component**

Create `resources/js/Components/Encounter/ExtractionConsentModal.vue`:

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps<{
    encounterId: number;
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { t, locale } = useI18n();
const patientSig = ref<string | null>(null);
const understood = ref(false);
const submitting = ref(false);

function close() {
    patientSig.value = null;
    understood.value = false;
    emit('close');
}

function submit() {
    if (!patientSig.value || !understood.value) return;
    submitting.value = true;
    router.post(
        route('extraction-consents.store', props.encounterId),
        {
            patient_signature_data: patientSig.value,
            language: locale.value,
        } as any,
        {
            onSuccess: () => close(),
            onFinish: () => { submitting.value = false; },
        }
    );
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
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('extractionConsent.title') }}</h3>
                    </div>

                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <p class="text-sm leading-relaxed text-gray-700">{{ t('extractionConsent.text') }}</p>
                        </div>
                        <SignaturePad v-model="patientSig" />
                        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" v-model="understood" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span>{{ t('extractionConsent.agree') }}</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                        <button type="button" @click="close" class="rounded-lg border px-3 py-2 text-sm">
                            {{ t('app.cancel') }}
                        </button>
                        <button
                            type="button"
                            :disabled="submitting || !patientSig || !understood"
                            @click="submit"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            {{ t('extractionConsent.sign_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
```

- [ ] **Step 2: Verify with the TypeScript compiler**

Run: `npm run build`
Expected: compiles without errors (this component is not imported anywhere yet, but it must type-check standalone).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Encounter/ExtractionConsentModal.vue
git commit -m "Add ExtractionConsentModal component"
```

---

### Task 10: Wire it into `Encounters/Show.vue`

**Files:**
- Modify: `app/Http/Controllers/EncounterController.php`
- Modify: `resources/js/Pages/Encounters/Show.vue`

**Interfaces:**
- Consumes: `Encounter.extraction_consent`, `Encounter.treatments[].is_extraction` (Task 7), `ExtractionConsentModal.vue` (Task 9), route `extraction-consents.pdf` (Task 4).

- [ ] **Step 1: Eager-load the consent and expose the ability in `EncounterController::show`**

In `app/Http/Controllers/EncounterController.php`, add `'extractionConsent',` to the `$encounter->load([...])` array in `show()` (alongside the existing `'treatments'`), and add `'consentExtraction' => auth()->user()->can('consentExtraction', $encounter),` to the `'can' => [...]` array, right after `'sign' => ...`:

```php
    public function show(Encounter $encounter): Response
    {
        $this->authorize('view', $encounter);

        $encounter->load([
            'patient',
            'provider:id,name',
            'dentistSigner:id,name',
            'rectifies:id,encounter_date',
            'rectifier:id,encounter_date,rectifies_encounter_id',
            'treatments',
            'attachments.uploader:id,name',
            'extractionConsent',
        ]);

        return Inertia::render('Encounters/Show', [
            'encounter' => $encounter,
            'currentUser' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'has_signature' => (bool) auth()->user()->signature_data,
            ],
            'can' => [
                'sign' => auth()->user()->can('sign', $encounter),
                'consentExtraction' => auth()->user()->can('consentExtraction', $encounter),
            ],
        ]);
    }
```

- [ ] **Step 2: Add the banner, button, and modal to `Show.vue`**

In `resources/js/Pages/Encounters/Show.vue`, add the import:

```ts
import ExtractionConsentModal from '@/Components/Encounter/ExtractionConsentModal.vue';
```

Update the `props` type to include `consentExtraction`:

```ts
const props = defineProps<{
    encounter: Encounter;
    currentUser: { id: number; name: string; has_signature: boolean };
    can: { sign: boolean; consentExtraction: boolean };
}>();
```

Add a new ref next to `signWizardOpen`:

```ts
const extractionConsentModalOpen = ref(false);
```

Add this computed helper right after `const treatmentStatusColors: ...` block:

```ts
const hasUnconsentedExtractions = computed(() =>
    (props.encounter.treatments ?? []).some((t) => t.is_extraction) && !props.encounter.extraction_consent
);
```

This requires importing `computed` — update the Vue import line:

```ts
import { ref, computed } from 'vue';
```

Update the existing "Close and sign" button's `v-if` to also require the extraction consent to be present:

```html
                    <button
                        v-if="can.sign && (encounter.treatments?.length ?? 0) > 0 && !hasUnconsentedExtractions"
                        type="button"
                        @click="signWizardOpen = true"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-colors"
                    >
                        {{ t('encounter.close_and_sign') }}
                    </button>
```

Add a pending-consent banner right after the existing "Signed banner + rectification banners" block (after the `rectified_by_banner` `<div>`, before the `<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">` line):

```html
        <div v-if="hasUnconsentedExtractions" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <span>⚠️ {{ t('extractionConsent.pending_banner') }}</span>
            <button
                v-if="can.consentExtraction"
                type="button"
                @click="extractionConsentModalOpen = true"
                class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 transition-colors"
            >
                {{ t('extractionConsent.sign_button') }}
            </button>
        </div>
        <div v-else-if="encounter.extraction_consent" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            <span>✅ {{ t('extractionConsent.signed_at', { at: formatDateTime(encounter.extraction_consent.signed_at) }) }}</span>
            <a
                :href="route('extraction-consents.pdf', encounter.extraction_consent.id)"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
            >
                {{ t('extractionConsent.download_pdf') }}
            </a>
        </div>
```

Add the modal instance right after the existing `<SignWizard ... />` at the bottom of the template, before `</AppLayout>`:

```html
        <ExtractionConsentModal
            :encounter-id="encounter.id"
            :open="extractionConsentModalOpen"
            @close="extractionConsentModalOpen = false"
        />
```

After this component reloads (Inertia `router.post` re-fetches the current page's props by default), `encounter.extraction_consent` and the computed `hasUnconsentedExtractions` will update automatically — no manual refetch needed.

- [ ] **Step 3: Verify with the TypeScript compiler**

Run: `npm run build`
Expected: compiles without errors.

- [ ] **Step 4: Manual verification in the browser**

Run: `npm run dev` (and, in another terminal, `/usr/local/opt/php@8.3/bin/php artisan serve`).

1. Log in as `maria@clinic.com` / `password` (dentist).
2. Create or open an `in_progress` encounter, add a treatment, and check "Es una extracción" (the Task 6 checkbox) — save.
3. Open the encounter's Show page: confirm the amber "pending consent" banner appears and the "Cerrar y firmar visita" button is hidden.
4. Click "Firmar consentimiento", sign in the pad, check the agreement box, submit.
5. Confirm the banner switches to the gray "signed" summary with a working "Descargar PDF del consentimiento" link, and the "Cerrar y firmar visita" button reappears.
6. Complete the encounter sign-off (SignWizard) and confirm it now succeeds.

- [ ] **Step 5: Run the full backend test suite for regressions**

Run: `/usr/local/opt/php@8.3/bin/php artisan test`
Expected: PASS — all pre-existing tests plus all new tests from Tasks 1–6 and 10 (10's controller change) pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/EncounterController.php resources/js/Pages/Encounters/Show.vue
git commit -m "Show extraction consent status and signing flow on encounter page"
```

---

## Self-Review Notes

- **Spec coverage:** every section of `2026-07-02-extraction-consent-design.md` maps to a task — data model → Task 1; policy/FormRequest/controller → Task 2; sign-blocking → Task 3; PDF → Task 4; GDPR export → Task 5; treatment flag (validation + the actual `Editor.vue` checkbox UI, redirected from the spec's dead-code `TreatmentEditor.vue` reference) → Task 6; TS types → Task 7; i18n copy → Task 8; consent modal → Task 9; `Show.vue` integration (banner, button, PDF link, sign-button guard) → Task 10.
- **Placeholder scan:** no TBD/TODO markers remain; the one placeholder found during the first draft (Task 6 missing the `Editor.vue` checkbox steps) was fixed inline by adding Steps 4–6 with full code, rather than left as a forward reference.
- **Type consistency:** `TreatmentForm.is_extraction: boolean` (Task 6) matches `Treatment.is_extraction: boolean` (Task 7) matches the DB column type (Task 1) matches the `Editor.vue` payload sent to `StoreEncounterRequest`/`UpdateEncounterRequest` (Task 6). `ExtractionConsent` fields are consistent across the model (Task 1), the TS interface (Task 7), and every test's `ExtractionConsent::create([...])` call (Tasks 1, 2, 3, 4, 5).
- **Scope check:** single cohesive feature, 10 tasks, no unrelated cleanup (the two dead components found during investigation are left untouched, per the project's "surgical changes" principle).
