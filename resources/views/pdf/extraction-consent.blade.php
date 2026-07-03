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
