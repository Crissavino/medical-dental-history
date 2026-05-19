# Encounter Signatures — Design Spec

**Date:** 2026-05-19
**Status:** Approved (brainstorm phase complete, ready for implementation plan)
**Owner:** Cristian Savino

## Context

The Dental Wellness EHR (Laravel 11 + Inertia + Vue 3) already supports digital signature capture for the patient's anamnesis (intake) and the dentist's countersignature on that anamnesis. Romanian dental regulations require that **each clinical encounter** be signed as evidence that the treatments registered were actually performed and accepted by the patient.

This spec defines how encounter-level signatures are captured, locked, audited and exported for inspection.

## Goals

1. Every completed encounter carries a fresh signature from both the patient and the responsible dentist.
2. Signed encounters become immutable. Corrections happen via a new "rectification" encounter that references the original.
3. A GDPR/regulatory inspection can be served:
   - A per-encounter signed PDF
   - A consolidated clinical history PDF (anamnesis + all encounters)
4. The audit trail records who signed, when, from what IP, and a content hash that detects later tampering.

## Non-goals

- Re-using the patient's saved signature with a one-click button. The previous design considered it but was rejected in favor of fresh signatures on every encounter (strongest legal position, simpler code path, ~10s UX cost per visit).
- Encounter versioning (à la `AnamnesisVersion`). Lockdown + rectification is simpler and preserves the legal evidence trail without a parallel versioning system.
- Storing the patient's signature on the `patients` table. The signature only lives at the moment of consent: anamnesis (intake) or encounter (sign-off).
- Allowing edits to `treatments` or `attachments` once their parent encounter is locked. Late-arriving artefacts (e.g. an X-ray result the next day) attach to the patient or to a new follow-up encounter.

## Key decisions and trade-offs

| Decision | Chosen | Rationale |
|---|---|---|
| What is signed | One signature per encounter, covering all its treatments | Matches Romanian regulation interpretation; matches existing anamnesis pattern. |
| Who signs | Both patient and dentist | Coherent with anamnesis; strongest defence on inspection. |
| When signing happens | On transition `in_progress → completed` only | Signing **is** the closing act. Avoids ambiguous states. |
| Post-signature mutability | Total lockdown; corrections via rectification encounter | Strongest legal evidence; matches immutable patterns already in use (`AnamnesisVersion`). |
| Patient signature reuse | None — always fresh capture | Treating every encounter as high-risk removes the auditability gap of one-click reuse. |
| Dentist signature reuse | Allowed via `users.signature_data` | Dentist is authenticated; the audit trail captures the user id. Convenience for staff without weakening the patient-side argument. |
| Inspection PDFs | Both per-encounter and consolidated clinical history | Per-encounter is the unit inspectors typically ask for; consolidated extends the existing GDPR Export. |

## Data model

### Migration: `add_signature_fields_to_encounters_table`

```php
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
```

**Field semantics:**
- `patient_signature_data`, `dentist_signature_data`: base64 PNG produced by the signature pad (same format used by `anamnesis_versions.signature_data` and `anamnesis_versions.dentist_signature_data`).
- `patient_signed_at` and `dentist_signed_at`: separate timestamps; patient and dentist may sign seconds apart, both moments are preserved.
- `dentist_signed_by`: explicit FK to `users`, may differ from `provider_id` if the day's responsible dentist changed.
- `signed_ip`: IPv4/IPv6 of the browser that submitted the signing request. 45 chars to accommodate IPv6.
- `signed_hash`: SHA-256 (hex, 64 chars) computed and persisted at sign time. Stored alongside the signed data so a later tamper-detection check can recompute and compare. See "Document hash" below for the canonical input and limitations.
- `rectifies_encounter_id`: nullable self-FK. `null` for original encounters; set when the encounter exists to correct a previous one.

**Unchanged tables:** `treatments`, `attachments`, `anamnesis_versions`, `audit_logs`, `users` (the dentist signature column was added in an earlier migration and is reused).

## State machine and policies

### Encounter status flow

```
scheduled ──┐
            ├──► in_progress ──► completed  (LOCKED — terminal)
            └──► cancelled                  (terminal, no signature, no edits)
```

Transitions out of `completed` or `cancelled` are forbidden. `completed` is only reachable via the signing endpoint, never via a direct status update.

### `Encounter::isLocked(): bool`

Returns `true` when `status === 'completed'`. Used by policies, request validation, and UI gating.

### Policy changes

| Policy method | When `isLocked()` is true |
|---|---|
| `EncounterPolicy::update` | deny |
| `EncounterPolicy::delete` | deny (also blocks soft-delete) |
| `TreatmentPolicy::create` (encounter-scoped) | deny |
| `TreatmentPolicy::update` | deny (navigate to parent encounter) |
| `TreatmentPolicy::delete` | deny |
| `AttachmentPolicy::create` (encounter-scoped) | deny |
| `AttachmentPolicy::delete` (encounter-scoped) | deny |
| Creating a new encounter with `rectifies_encounter_id = X` | allowed |

Defense in depth: the corresponding `FormRequest` classes also reject mutations on locked encounters, so a missing policy gate cannot leak through.

### Rectification flow

1. UI on `Encounters/Show.vue` shows a `Rectify` button (replacing `Edit`) for locked encounters.
2. Backend creates a new `Encounter` row with:
   - `rectifies_encounter_id = $original->id`
   - Same `patient_id`, `provider_id` (defaults to current user, editable)
   - Pre-filled `chief_complaint`, `clinical_notes`, `diagnosis` (editable copies)
   - Pre-filled treatments (editable copies)
   - `status = 'in_progress'`
3. User edits, completes, signs — normal flow.
4. The original encounter shows the banner *"Rectified by #X on DD/MM/YYYY"*.
5. The rectifier encounter shows the banner *"Rectifies #Y"*.

A chain is allowed (`#X` rectifies `#Y` rectifies `#Z`). Each link is traversable.

### GDPR `forceDelete` of a patient

Existing behaviour preserved: `forceDelete` cascades to encounters (and their signatures). This is legally correct for the "right to be forgotten". `softDelete` keeps encounters intact.

## UI flow — signing wizard

### Entry point

In `Encounters/Editor.vue`, when:
- `status === 'in_progress'`, and
- The encounter has at least one treatment,

a primary green button **"Close and sign visit"** appears. The plain status dropdown no longer offers `completed` directly — that transition is gated by the wizard.

### Step 1 — Review (read-only)

Modal opens showing the immutable snapshot the patient is about to confirm:
- Date
- Chief complaint
- Diagnosis
- Clinical notes
- Treatments table: code, tooth (FDI + name), surface, description, cost
- Yellow warning banner: *"Once signed, this encounter is locked. Errors must be fixed now."*
- Actions: `Back to edit` (closes modal) / `Continue to signature`

### Step 2 — Patient signature

- `SignaturePad.vue` (same component used in intake) on the canvas.
- Confirmation text (EN/RO): *"I, [Patient full name], confirm that the treatments listed above were performed on me correctly."*
- `Continue` disabled until the canvas has strokes.
- Designed for handoff to a tablet at the chair.

### Step 3 — Dentist signature

- Same `SignaturePad.vue`.
- Confirmation text: *"I, Dr. [Name], certify that the listed treatments were performed as recorded."*
- If `auth()->user()->signature_data` is set, a toggle appears: **"Use my stored professional signature"**. When on, the canvas is replaced by a preview of the stored signature.
- Action: `Sign and close visit`.

### Submit

`POST /encounters/{id}/sign` with `{ patient_signature_data, dentist_signature_data }`.

Server-side checks (FormRequest):
- Encounter is in `in_progress`.
- Authenticated user has role `admin` or `dentist`.
- Both signatures are non-empty.
- At least one treatment exists.

On success, server sets:
```
patient_signature_data, patient_signed_at = now(),
dentist_signature_data, dentist_signed_by = auth()->id(), dentist_signed_at = now(),
signed_ip = request()->ip(),
signed_hash = sha256(canonical_concat(...)),
status = 'completed'
```

Inertia redirects to `Encounters/Show.vue`.

### Encounters/Show.vue (post-signature)

- Green header banner: 🔒 *"Encounter signed on DD/MM/YYYY HH:mm. Locked."*
- New "Signatures" section: two cards side-by-side rendering each `<img src="data:image/png;base64,...">` with signer name and timestamp.
- Yellow rectification banner if applicable (chain navigation).
- `Edit` is replaced by `Rectify`.
- New `Download signed PDF` button.

## PDF generation

PDFs use `barryvdh/laravel-dompdf` (already in composer). Layout reuses the visual style of the existing anamnesis PDF (clinic logo, typography, signature blocks).

### Per-encounter PDF

**Route:** `GET /encounters/{id}/pdf` (only when `completed`).

**Permissions:** `admin`, `dentist`. Receptionist denied.

**Layout (1–2 compact pages):**
- Header: clinic logo, patient identifier and name, encounter date, provider name, encounter id, optional `Rectifies #X` line.
- Sections: chief complaint, diagnosis, clinical notes.
- Treatments table: code, tooth, surface, description, cost; with totals row.
- Signatures block: two side-by-side cards with the embedded PNG, signer name, timestamp.
- Footer: `Generated DD/MM/YYYY HH:mm · Signed IP X.X.X.X · Document hash sha256:...`.

**Document hash:** SHA-256 over the canonical concatenation
`encounter_id|date|chief_complaint|diagnosis|clinical_notes|treatments_json|patient_signed_at|dentist_signed_at`.
Computed once at sign time, persisted to `encounters.signed_hash`, and printed verbatim in the PDF footer.

Detection use: recompute the hash from current encounter data and compare against the stored `signed_hash`. A mismatch indicates that the underlying record was modified post-signature (bypassing the lockdown).

Limitations: this is not a cryptographic signature. An attacker with DB write access could modify both the data and the `signed_hash` field. The protection it offers is tamper *evidence* against accidental edits or non-coordinated tampering, not tamper *prevention*. Stronger guarantees would require an external signing service, which is out of scope.

### Consolidated clinical history PDF

**Route:** `GET /patients/{id}/clinical-history/pdf`.

**Permissions:** `admin`, `dentist` (same as GDPR Export).

**Structure (single navigable PDF):**
1. **Cover page** — patient identification, identifier, generation date, table of contents.
2. **Current anamnesis** — the latest `AnamnesisVersion`, rendered with the existing anamnesis PDF generator.
3. **Encounter timeline** — every `completed` encounter in chronological order, each rendered with the per-encounter layout (without the cover header).
4. **Cancelled encounters** — final section, minimal listing (date + cancellation reason) so the inspector sees they existed.
5. **Page footer** — `Page X of Y · Patient P-XXXXXX · Generated DD/MM/YYYY HH:mm`.

**Builder:** `ClinicalHistoryPdfBuilder` service composes the document in memory by chaining the anamnesis PDF and a loop over completed encounters.

**Integration with GDPR Export:** the existing `GdprExport` controller is extended to include this PDF inside the export ZIP.

## Audit trail

### Existing coverage

`AuditObserver` already logs `created`, `updated`, `deleted` on `Encounter`. The signing UPDATE will already produce an `updated` entry — the addition below provides a dedicated, easily-filterable event.

### New explicit events

When `POST /encounters/{id}/sign` succeeds, write a dedicated `audit_logs` row:
```
action: 'signed'
entity_type: 'Encounter'
entity_id: {encounter.id}
user_id: auth()->id()
ip_address: request()->ip()
metadata_json: {
  patient_signed_at, dentist_signed_at,
  dentist_signed_by_user_id,
  signed_ip,
  treatments_count,
  encounter_hash
}
```

When a rectification encounter is created, write on the **original** encounter:
```
action: 'rectified'
entity_type: 'Encounter'
entity_id: {original.id}
metadata_json: {
  rectified_by_encounter_id, rectified_by_user_id
}
```

### Audit Logs UI

The action filter dropdown in `AuditLogs` adds `signed` and `rectified` to the existing list (`created`, `updated`, `deleted`).

## Inspection view (UI surface)

`Patients/Show.vue` → `Encounters` tab (the screen the user works from):
- Each encounter row gains a status indicator next to the existing badge:
  - 🔒 green for `completed` with both signatures present.
  - ⚠️ amber for `completed` without signatures (defensive — should not occur in new data, may exist for legacy rows).
  - No icon for `scheduled`, `in_progress`, `cancelled`.
- If the encounter has `rectifies_encounter_id` set, an extra grey badge: *"Rectifies #N"*.
- New top-right action above the timeline: **"Download full clinical history"** → triggers the consolidated PDF endpoint.

`Encounters/Show.vue` for signed encounters: covered above (banner, signatures block, rectification banner, PDF button, `Rectify` replacing `Edit`).

`Dashboard`, `Profile`, `Audit Logs` (apart from the dropdown), and `Attachments` tabs: unchanged.

## Tests to plan (minimum)

- Cannot UPDATE an encounter whose status is `completed` (policy + FormRequest paths).
- Cannot create, update, or delete `treatments` belonging to a `completed` encounter.
- Cannot create or delete `attachments` belonging to a `completed` encounter.
- Successful signing flow: both signatures present → status becomes `completed`, all six fields populated, `audit_logs` has one `signed` row with expected metadata.
- Signing fails (422) when either signature is missing, when the encounter is not `in_progress`, or when the user lacks `dentist`/`admin` role.
- Per-encounter PDF download:
  - returns 200 with PDF mime-type for a `completed` encounter,
  - returns 403 for a non-completed encounter,
  - returns 403 for a `receptionist` user.
- Rectification: creating an encounter with `rectifies_encounter_id` writes a `rectified` audit log row on the original, and the original remains immutable.
- Consolidated clinical history PDF includes the anamnesis section, every `completed` encounter, and the cancelled-encounters summary.

## Open questions

None at this point. All architectural decisions resolved during brainstorm.

## File map (expected touch list)

```
database/migrations/2026_05_19_XXXXXX_add_signature_fields_to_encounters_table.php   (new)
app/Models/Encounter.php                                                              (isLocked, casts, fillable)
app/Policies/EncounterPolicy.php                                                      (lock checks)
app/Policies/TreatmentPolicy.php                                                      (lock checks via parent)
app/Policies/AttachmentPolicy.php                                                     (lock checks for encounter scope)
app/Http/Controllers/EncounterController.php                                          (sign, rectify, pdf actions)
app/Http/Controllers/ClinicalHistoryPdfController.php                                 (new)
app/Http/Requests/SignEncounterRequest.php                                            (new)
app/Http/Requests/EncounterUpdateRequest.php                                          (lock guard)
app/Services/Pdf/EncounterPdfBuilder.php                                              (new)
app/Services/Pdf/ClinicalHistoryPdfBuilder.php                                        (new)
app/Observers/AuditObserver.php                                                       (no changes — explicit logs from controller)
app/Http/Controllers/GdprExportController.php                                         (include history PDF in ZIP)
resources/views/pdfs/encounter.blade.php                                              (new, dompdf template)
resources/views/pdfs/clinical-history.blade.php                                       (new, dompdf wrapper)
resources/js/Pages/Encounters/Editor.vue                                              ("Close and sign visit" button)
resources/js/Pages/Encounters/Show.vue                                                (signed banner, signatures block, rectify, pdf button)
resources/js/Pages/Encounters/SignWizard.vue                                          (new modal component, 3 steps)
resources/js/Pages/Patients/Show.vue                                                  (encounter row lock icon, full-history button)
resources/js/Pages/AuditLogs/Index.vue                                                (filter dropdown additions)
resources/js/i18n/en.ts                                                               (new keys)
resources/js/i18n/ro.ts                                                               (new keys)
routes/web.php                                                                        (sign, rectify, pdf, clinical-history routes)
tests/Feature/EncounterSigningTest.php                                                (new)
tests/Feature/EncounterLockdownTest.php                                               (new)
tests/Feature/EncounterPdfTest.php                                                    (new)
tests/Feature/ClinicalHistoryPdfTest.php                                              (new)
```
