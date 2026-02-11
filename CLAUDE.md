# Dental EHR - Clinical History

## Build & Run Commands

```bash
# Install dependencies
composer install
npm install

# Database setup (requires MySQL 8 running with 'clinical_history' database)
php artisan migrate --seed

# Development
npm run dev          # Vite dev server (HMR)
php artisan serve    # Laravel dev server

# Production build
npm run build

# Run tests
php artisan test

# Clear caches
php artisan optimize:clear
```

## Architecture Overview

- **Laravel 11 LTS** + **Inertia.js** + **Vue 3** (TypeScript) monorepo
- **MySQL 8** database, **Tailwind CSS 3** styling
- **Laravel Breeze** (Inertia + Vue) for authentication scaffolding
- **Sanctum** session-based auth (built into Breeze)
- **vue-i18n** for bilingual UI (EN/RO)

## Key Patterns

### Audit Observer
`app/Observers/AuditObserver.php` is registered on Patient, AnamnesisVersion, Encounter, Treatment, and Attachment models. It logs `created`, `updated`, `deleted` events with old/new values to the `audit_logs` table.

### Polymorphic Attachments
`Attachment` model uses `morphTo` relationship (`attachable_type` + `attachable_id`). Supported types: Patient, Encounter, Treatment. Files stored in `storage/app/attachments/`.

### Role-Based Authorization
Simple enum role on `users` table: `admin`, `dentist`, `assistant`, `receptionist`.
- `EnsureRole` middleware for route-level checks (aliased as `role:`)
- Policy classes for model-level authorization
- `User::hasRole()` helper method

### Anamnesis Versioning
Each anamnesis submission creates a new `AnamnesisVersion` record (immutable). Versions are auto-incremented per patient. Form data is stored as JSON.

### Public Intake Wizard
The `/intake` route is publicly accessible (no auth). Patients can fill out their anamnesis form in EN or RO. Creates patient record if needed.

## Database Schema

| Table | Key Columns |
|-------|-------------|
| users | name, email, role (enum), password |
| patients | identifier (unique), first_name, last_name, date_of_birth, gender, phone, email, cnp, soft_deletes |
| anamnesis_versions | patient_id, version, form_data (JSON), consent_given, language |
| encounters | patient_id, provider_id, encounter_date, chief_complaint, clinical_notes, diagnosis, status, soft_deletes |
| treatments | encounter_id, tooth_number (FDI), treatment_code, description, surface, cost, status |
| attachments | attachable_type, attachable_id (polymorphic), uploaded_by, file_name, file_path, mime_type, file_size |
| audit_logs | entity_type, entity_id, user_id, action, metadata_json, ip_address |

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/    # 8 controllers (Patient, Encounter, Treatment, Attachment, Anamnesis, Intake, AuditLog, GdprExport)
│   ├── Middleware/      # EnsureRole middleware
│   └── Requests/       # 8 form request validators
├── Models/             # 7 models (User, Patient, AnamnesisVersion, Encounter, Treatment, Attachment, AuditLog)
├── Observers/          # AuditObserver
└── Policies/           # 4 policies (Patient, Encounter, Treatment, Attachment)

resources/js/
├── Pages/              # Inertia pages (Dashboard, Patients/*, Encounters/*, Intake/*, AuditLogs/*)
├── Components/         # Vue components (Layout/*, Patient/*, Encounter/*, Treatment/*, Attachment/*, Anamnesis/*, UI/*)
├── Composables/        # useToothNotation, useResponsive
├── i18n/               # EN/RO translation files
└── types/              # TypeScript interfaces
```

## Roles & Permissions

| Action | Admin | Dentist | Assistant | Receptionist |
|--------|-------|---------|-----------|--------------|
| CRUD Patients | ✓ | ✓ | Read only | ✓ |
| CRUD Encounters | ✓ | ✓ | Create/Edit | Read only |
| CRUD Treatments | ✓ | ✓ | Create/Edit | ✗ |
| CRUD Attachments | ✓ | ✓ | Create/View | ✗ |
| View Audit Logs | ✓ | ✗ | ✗ | ✗ |
| GDPR Export | ✓ | ✓ | ✗ | ✗ |

## Seed Credentials

| Email | Password | Role |
|-------|----------|------|
| admin@clinic.com | password | admin |
| maria@clinic.com | password | dentist |
| andrei@clinic.com | password | dentist |
| elena@clinic.com | password | assistant |
| ana@clinic.com | password | receptionist |
