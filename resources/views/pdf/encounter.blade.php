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
