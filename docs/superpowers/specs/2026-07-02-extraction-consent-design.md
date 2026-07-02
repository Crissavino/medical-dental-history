# Extraction Informed Consent — Design

**Date:** 2026-07-02
**Status:** Approved

## Goal

Capture a patient's informed consent specifically for tooth extractions, separate from
the general GDPR data-processing consent (intake) and separate from the encounter's
closing signatures (SignWizard). Legally this consent must be obtained **before** the
extraction is performed, not when the visit is closed out.

## Success Criteria

- Staff can mark a treatment as an extraction (`is_extraction`).
- When an encounter has at least one extraction treatment, staff can capture the
  patient's signature against a fixed informed-consent text, tied to that encounter.
- The encounter cannot be signed/closed (`SignWizard`) while it has an unconsented
  extraction treatment.
- The signed consent is immutable, downloadable as a PDF, and included in the patient's
  GDPR export ZIP.
- Works in EN / RO / ES.

## Out of Scope (YAGNI)

- No generic/parametrized "procedure consent" type — extraction-only for now.
- No re-signing/versioning flow if a new extraction treatment is added to an encounter
  after the consent was already signed (edge case, not requested).
- No dentist counter-signature on this document — patient signature only.
- No admin UI to edit the consent copy — text lives in i18n files like the existing
  GDPR consent text.

## Data Model

### Migration — add `is_extraction` to `treatments`

| Column | Type | Notes |
|--------|------|-------|
| is_extraction | boolean, default false | set via checkbox in the treatment editor |

### Migration — `extraction_consents` table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint pk | |
| encounter_id | foreignId → encounters | `cascadeOnDelete`, **unique** (one consent per encounter) |
| consent_text | longText | snapshot of the copy shown at signing time (immutable even if i18n copy changes later) |
| language | string(2) | `en` / `ro` / `es` |
| patient_signature_data | longText | base64 data URI, like other signature fields in the app |
| signed_at | timestamp | |
| signed_ip | string(45) | |
| recorded_by | foreignId → users, nullable | `nullOnDelete`; who captured the signature |
| created_at / updated_at | timestamps | |

### Model — `app/Models/ExtractionConsent.php`

- `belongsTo(Encounter::class)`
- `recorder(): belongsTo(User::class, 'recorded_by')`
- `$fillable = ['encounter_id', 'consent_text', 'language', 'patient_signature_data', 'signed_at', 'signed_ip', 'recorded_by']`
- `casts(): ['signed_at' => 'datetime']`
- No update/delete routes are exposed — the record is treated as immutable once created,
  consistent with the tamper-evidence approach used for encounter signatures.

### `Encounter` model additions

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

### `Treatment` model

Add `is_extraction` to `$fillable` and cast as `boolean`.

### Audit logging

Register in `AppServiceProvider`, same as the other clinical models:

```php
ExtractionConsent::observe(AuditObserver::class);
```

## Backend

### Policy

Add to `EncounterPolicy`:

```php
public function consentExtraction(User $user, Encounter $encounter): bool
{
    return !$encounter->isLocked()
        && $user->hasRole('admin', 'dentist', 'assistant');
}
```

(Same roles allowed to create/edit encounters and treatments — an assistant may capture
this signature while prepping the patient, before the dentist starts the procedure.)

### FormRequest — `StoreExtractionConsentRequest`

- `authorize()`: `$this->user()->can('consentExtraction', $encounter)`.
- `rules()`:
  ```php
  [
      'patient_signature_data' => ['required', 'string', 'starts_with:data:image/'],
      'language' => ['required', 'in:en,ro,es'],
  ]
  ```
- `withValidator()`:
  - Reject if the encounter has no `is_extraction` treatment.
  - Reject if `$encounter->extractionConsent()->exists()` (already signed — one per
    encounter).

### Controller — `app/Http/Controllers/ExtractionConsentController.php`

- `store(StoreExtractionConsentRequest $request, Encounter $encounter)`
  - Resolve `consent_text` server-side from `config('extraction-consent.text.{language}')`
    (new `config/extraction-consent.php`, one string per language — the same wording as
    the frontend copy below, kept in sync manually since it's static content, not
    client-supplied) so the snapshot is server-authoritative.
  - Create the `ExtractionConsent`: `signed_at = now()`, `signed_ip = $request->ip()`,
    `recorded_by = auth()->id()`.
  - Redirect back with a success flash message, same pattern as `TreatmentController`.
- `pdf(ExtractionConsent $extractionConsent, ExtractionConsentPdfService $pdfService)`
  - `$this->authorize('downloadPdf', $extractionConsent->encounter)` (reuse the existing
    encounter ability — same roles who can download the encounter PDF).
  - Stream download, same pattern as `EncounterController::pdf`.

### `SignEncounterRequest` change

In `withValidator()`, add:

```php
if ($encounter && $encounter->hasUnconsentedExtractions()) {
    $validator->errors()->add(
        'extraction_consent',
        'Extraction consent must be signed before closing this encounter.'
    );
}
```

### PDF — `ExtractionConsentPdfService` + `resources/views/pdf/extraction-consent.blade.php`

Same shape as `AnamnesisPdfService` / `anamnesis.blade.php`: clinic header, patient
identification, the `consent_text` snapshot, the signature image, `signed_at` and
`signed_ip` for tamper evidence. `filename()` returns something like
`consentimiento-extraccion-{patient-identifier}-{encounter-id}.pdf`.

### GDPR export

In `GdprExportController::export`:
- Eager-load `encounters.extractionConsent`.
- For each encounter with a non-null `extractionConsent`, add its PDF to the zip:
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

### Routes (inside existing `auth` middleware group)

```php
Route::post('/encounters/{encounter}/extraction-consent', [ExtractionConsentController::class, 'store'])
    ->name('extraction-consents.store');
Route::get('/extraction-consents/{extractionConsent}/pdf', [ExtractionConsentController::class, 'pdf'])
    ->name('extraction-consents.pdf');
```

## Frontend

### TS types (`resources/js/types/index.d.ts`)

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

- Add `is_extraction: boolean` to the `Treatment` interface.
- Add `extraction_consent?: ExtractionConsent | null` to the `Encounter` interface.
- `EncounterController@show` eager-loads `extractionConsent` and passes it as part of
  `encounter`, same as `treatments`/`attachments` today.

### `TreatmentEditor.vue`

- Add a checkbox bound to `form.is_extraction`, labeled `t('treatment.is_extraction')`,
  placed near the tooth selector. Included in the create/update payload like the other
  fields.

### `TreatmentList.vue`

- Small badge/icon next to the tooth number when `treatment.is_extraction` is true (for
  quick visual scanning), reusing existing badge styling conventions in that component.

### New component — `resources/js/Components/Encounter/ExtractionConsentModal.vue`

Single-step modal (simpler than `SignWizard` — one signature, no multi-party flow):
- Renders the consent text for the current locale (`t('extractionConsent.text')`).
- `SignaturePad` (reused component) bound to `patientSig`.
- Checkbox "He leído y entiendo este consentimiento" required to enable submit, mirroring
  the `instrumentsConfirmed` pattern in `SignWizard.vue`.
- On submit: `router.post(route('extraction-consents.store', encounter.id), { patient_signature_data, language: locale })`.

### `Encounters/Show.vue`

- Compute `hasExtractionTreatments = encounter.treatments?.some(t => t.is_extraction)`.
- If `hasExtractionTreatments && !encounter.extraction_consent`: show a warning banner
  (same visual style as the existing yellow warning in `SignWizard` step 1) with a
  "Firmar consentimiento" button that opens `ExtractionConsentModal`.
- If `encounter.extraction_consent` exists: show a compact read-only summary (signed
  date + link to `extraction-consents.pdf`), similar to how the locked banner shows
  signature info today.
- The existing "Firmar" button that opens `SignWizard` gets an added disabled condition:
  `hasExtractionTreatments && !encounter.extraction_consent` (mirrors the current
  `(encounter.treatments?.length ?? 0) > 0` guard), so the client reflects the same rule
  the backend enforces in `SignEncounterRequest`.

### i18n (en / ro / es)

New `extractionConsent` namespace: `title`, `text` (the informed-consent body), `agree`
(checkbox label), `sign_button`, `pending_banner`, `signed_at`, `download_pdf`.
Plus `treatment.is_extraction` label.

**Spanish (`es.json`) — `extractionConsent.text`:**

> Yo, el/la abajo firmante, declaro haber sido informado/a de forma clara por el/la
> profesional tratante sobre la naturaleza del procedimiento de extracción dentaria
> indicado, incluyendo: el motivo de la extracción, el tipo de anestesia a utilizar, las
> alternativas de tratamiento disponibles, y los riesgos y complicaciones posibles —
> entre ellos dolor, inflamación, sangrado, infección, alveolitis seca, lesión de dientes
> o restauraciones adyacentes, fractura radicular con posible fragmento residual,
> parestesia transitoria o permanente de labio, mentón o lengua, comunicación
> oro-sinusal, fractura mandibular, necesidad de tratamiento adicional, y reacciones a la
> anestesia. He podido formular las preguntas que consideré necesarias y las mismas
> fueron respondidas satisfactoriamente. Entiendo que la odontología no es una ciencia
> exacta y que no se garantizan resultados específicos. Presto mi consentimiento libre,
> informado y voluntario para la realización de dicho procedimiento.

**English (`en.json`) — `extractionConsent.text`:**

> I, the undersigned, declare that I have been clearly informed by the treating
> professional about the nature of the indicated tooth extraction procedure, including:
> the reason for the extraction, the type of anesthesia to be used, the available
> treatment alternatives, and the possible risks and complications — including pain,
> swelling, bleeding, infection, dry socket, injury to adjacent teeth or restorations,
> root fracture with a possible residual fragment, transient or permanent numbness of
> the lip, chin or tongue, oro-sinus communication, jaw fracture, the need for
> additional treatment, and reactions to anesthesia. I have been able to ask any
> questions I considered necessary and they were answered satisfactorily. I understand
> that dentistry is not an exact science and that specific outcomes are not guaranteed.
> I give my free, informed and voluntary consent for this procedure to be performed.

**Romanian (`ro.json`) — `extractionConsent.text`:**

> Subsemnatul/subsemnata declar că am fost informat/informată în mod clar de către
> medicul curant cu privire la natura procedurii de extracție dentară indicate,
> inclusiv: motivul extracției, tipul de anestezie ce urmează a fi utilizat,
> alternativele de tratament disponibile, precum și riscurile și complicațiile posibile
> — printre care durere, inflamație, sângerare, infecție, alveolită uscată, lezarea
> dinților sau restaurărilor adiacente, fractură radiculară cu posibil fragment
> restant, parestezie tranzitorie sau permanentă a buzei, bărbiei sau limbii,
> comunicare oro-sinuzală, fractură mandibulară, necesitatea unui tratament suplimentar
> și reacții la anestezie. Am avut posibilitatea de a adresa întrebările pe care le-am
> considerat necesare, iar acestea au primit răspunsuri satisfăcătoare. Înțeleg că
> stomatologia nu este o știință exactă și că nu se garantează rezultate specifice.
> Îmi acord consimțământul liber, informat și voluntar pentru efectuarea acestei
> proceduri.

> **Note:** the Romanian text above is a translation for scaffolding purposes only —
> flag it for review by a native-speaking clinician/legal reviewer before this goes to
> production, since it's a medico-legal document.

## Testing

- Feature test: creating an `ExtractionConsent` on an encounter with no `is_extraction`
  treatment fails validation.
- Feature test: creating a second `ExtractionConsent` on the same encounter fails
  (unique constraint / validation).
- Feature test: `SignEncounterRequest` rejects signing when an extraction treatment has
  no consent; succeeds once one is created.
- Feature test: assistant role can create a consent; receptionist cannot (403).
- Feature test: `GdprExportController::export` includes the extraction-consent PDF when
  present.
