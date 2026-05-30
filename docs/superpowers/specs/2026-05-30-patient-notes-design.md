# Patient Notes — Design

**Date:** 2026-05-30
**Status:** Approved

## Goal

Let staff record general, timestamped annotations about a patient ("called to confirm",
"prefers afternoon appointments", etc.). Surface them under a new **Notes** tab on the
patient detail page, placed next to **Attachments**.

This is a *running log* of separate entries — distinct from the existing single
`patients.notes` free-text field (shown in the Profile tab, edited via the Edit form),
which stays as-is.

## Success Criteria

- A 5th tab "Notes" appears next to "Attachments" on the patient page.
- Any staff role (admin, dentist, assistant, receptionist) can add a note.
- Each note shows its body, author name, and date/time, newest first.
- A user can edit/delete their own notes; admin can edit/delete any note.
- Empty state matches the look of the Attachments tab.
- Works in EN / RO / ES.

## Out of Scope (YAGNI)

- No audit-log (`AuditObserver`) registration.
- Not included in the GDPR export ZIP.
- No PDF rendering, no rich text, no soft-deletes, no attachments-on-notes.

## Backend

### Migration — `patient_notes` table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint pk | |
| patient_id | foreignId → patients | `cascadeOnDelete` |
| user_id | foreignId → users, nullable | author; `nullOnDelete` |
| body | text | |
| created_at / updated_at | timestamps | |

### Model — `app/Models/PatientNote.php`

- `belongsTo(Patient::class)`
- `author(): belongsTo(User::class, 'user_id')`
- `$fillable = ['patient_id', 'user_id', 'body']`

### Patient model

Add relation **`notesLog(): HasMany`** → `hasMany(PatientNote::class)`.

> Named `notesLog`, **not** `notes`, to avoid colliding with the existing `notes`
> string column/attribute on `patients`.

### Controller — `app/Http/Controllers/PatientNoteController.php`

- `store(Request, Patient $patient)`
  - `$this->authorize('view', $patient)` — any staff who can see the patient may add.
  - Validate: `body` => `required|string|max:5000`.
  - Create with `user_id = auth()->id()`.
  - Redirect back (`preserveScroll` on the frontend).
- `update(Request, PatientNote $patientNote)`
  - Guard: `abort_unless($patientNote->user_id === auth()->id() || auth()->user()->hasRole('admin'), 403)`.
  - Validate `body` as above; update.
- `destroy(PatientNote $patientNote)`
  - Same author-or-admin guard; delete.

Auth uses inline guards in the project's simple role style — no dedicated Policy class.

### Routes (inside the existing `auth` middleware group in `routes/web.php`)

```php
Route::post('/patients/{patient}/notes', [PatientNoteController::class, 'store'])
    ->name('patient-notes.store');
Route::put('/patient-notes/{patientNote}', [PatientNoteController::class, 'update'])
    ->name('patient-notes.update');
Route::delete('/patient-notes/{patientNote}', [PatientNoteController::class, 'destroy'])
    ->name('patient-notes.destroy');
```

### `PatientController@show`

- Eager-load `notesLog.author:id,name` ordered newest-first.
- Pass as a **separate `notes` Inertia prop** (array of notes), leaving `patient.notes`
  (the string) untouched.

```php
$patient->load([
    // ...existing loads...
    'notesLog' => fn ($q) => $q->orderByDesc('created_at'),
    'notesLog.author:id,name',
]);

return Inertia::render('Patients/Show', [
    'patient' => $patient,
    'anamnesisVersions' => /* unchanged */,
    'notes' => $patient->notesLog,
]);
```

## Frontend

### TS types (`resources/js/types/index.d.ts`)

```ts
export interface PatientNote {
    id: number;
    patient_id: number;
    user_id: number | null;
    body: string;
    author?: { id: number; name: string };
    created_at: string;
    updated_at: string;
}
```

Add `notes?: PatientNote[]` as a prop on Show.vue (distinct from `patient.notes: string`).

### `Show.vue`

- Add `t('patient.tab_notes')` to the `tabs` computed array → it renders as the 5th tab
  button automatically (the tab bar is a `v-for`). New index = 4.
- New panel `<div v-show="currentTab === 4">`:
  - Header: "Notes" + **"+ Add Note"** button → opens an add/edit modal reusing the
    `Modal` UI component (same pattern as the attachment-upload modal). The modal holds a
    single `body` textarea + Save/Cancel; reused for both add and edit (edit pre-fills body).
  - Note cards: `body` with `whitespace-pre-wrap`, author name + `formatDateTime(created_at)`,
    and **edit / delete** icon buttons rendered only when
    `note.user_id === page.props.auth.user.id || page.props.auth.user.role === 'admin'`.
  - Empty state mirroring the Attachments tab (icon + message + add button).
- Add: `router.post(route('patient-notes.store', patient.id), { body }, { preserveScroll: true })`.
- Edit: `router.put(route('patient-notes.update', noteId), { body }, { preserveScroll: true })`.
- Delete: confirm, then `router.delete(route('patient-notes.destroy', noteId), { preserveScroll: true })`.

### i18n (en / ro / es)

- `patient.tab_notes` → "Notes" / "Note" / "Notas"
- New `notes` namespace: `add`, `edit`, `placeholder`, `empty`, `delete_confirm`, `save`.

## Testing

- Feature test: a receptionist can POST a note to a patient (201/redirect, row created with
  `user_id`).
- Feature test: user A cannot update/delete user B's note (403); admin can.
- Feature test: `PatientController@show` returns the `notes` prop with author eager-loaded.
