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
