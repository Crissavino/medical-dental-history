<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 18mm 15mm 18mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            line-height: 1.45;
        }
        .logo-area {
            text-align: center;
            margin-bottom: 8px;
        }
        .logo-area img {
            height: 50px;
        }
        h1 {
            font-size: 14pt;
            text-align: center;
            margin: 6px 0 2px 0;
            font-weight: bold;
        }
        .subtitle {
            text-align: center;
            font-size: 8pt;
            font-style: italic;
            color: #555;
            margin-bottom: 12px;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 12px 0 5px 0;
            padding: 3px 0;
            border-bottom: 1px solid #333;
        }
        .patient-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .patient-table td {
            padding: 4px 6px;
            border: 1px solid #999;
            font-size: 9pt;
        }
        .patient-table .label {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 30%;
        }
        .inline-section {
            margin: 3px 0;
            font-size: 9pt;
            line-height: 1.6;
        }
        .field-value {
            color: #0055aa;
            text-decoration: underline;
        }
        .field-blank {
            display: inline-block;
            min-width: 80px;
            border-bottom: 1px solid #999;
        }
        .disease-line {
            margin: 2px 0;
            font-size: 8.5pt;
            line-height: 1.5;
        }
        .disease-label {
            font-weight: bold;
            font-size: 9pt;
        }
        .consent-box {
            border: 1px solid #666;
            padding: 10px 12px;
            margin: 12px 0;
            font-size: 8pt;
            line-height: 1.6;
        }
        .consent-box .consent-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 6px;
        }
        .consent-box ul {
            margin: 4px 0 4px 16px;
            padding: 0;
        }
        .consent-box li {
            margin-bottom: 2px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .signature-table td {
            border: 1px solid #999;
            padding: 6px 10px;
            font-size: 9pt;
            width: 50%;
            vertical-align: top;
        }
        .signature-table .sig-header {
            font-weight: bold;
            text-align: center;
            background-color: #f0f0f0;
        }
        .sig-line {
            border-bottom: 1px solid #333;
            height: 28px;
            margin-top: 4px;
        }
        .footer-note {
            font-size: 7pt;
            color: #666;
            text-align: center;
            margin-top: 14px;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            font-style: italic;
        }
        .page-break {
            page-break-before: always;
        }
        .page-header {
            text-align: center;
            font-size: 8pt;
            color: #888;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

@php
    // Checkbox helper
    $chk = function($val) { return !empty($val) ? '☑' : '☐'; };

    // Join array or return dash
    $joinOrDash = function($arr) {
        if (!empty($arr) && is_array($arr)) {
            return implode(', ', array_filter($arr));
        }
        return '___';
    };

    $yes = $t['anamnesis.yes_word'];
    $no  = $t['anamnesis.no_word'];

    $ss = $formData['special_situations'] ?? [];
    $al = $formData['allergies'] ?? [];
    $ct = $formData['current_treatment'] ?? [];
    $diseases = $formData['diseases'] ?? [];
    $sh = $formData['surgical_history'] ?? [];
    $dh = $formData['dental_history'] ?? [];
    $hab = $formData['habits'] ?? [];
@endphp

{{-- ========== PAGE 1 ========== --}}

{{-- Logo --}}
<div class="logo-area">
    <img src="data:image/svg+xml;base64,{{ $logoBase64 }}" alt="Dental Wellness">
</div>

{{-- Title --}}
<h1>{{ $t['anamnesis.title'] }}</h1>
<div class="subtitle">{{ $t['anamnesis.pdf_subtitle'] }}</div>

{{-- Patient Details --}}
<table class="patient-table">
    <tr>
        <td class="label">{{ $t['anamnesis.patient_full_name'] }}</td>
        <td>{{ $patient->last_name }}, {{ $patient->first_name }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_cnp_passport'] }}</td>
        <td>{{ $patient->cnp ?? '___' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_home_address'] }}</td>
        <td>{{ implode(', ', array_filter([$patient->address ?? '', $patient->city ?? '', $patient->county ?? ''])) ?: '___' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_phone'] }} / {{ $t['anamnesis.patient_email'] }}</td>
        <td>{{ $patient->phone ?? '___' }} / {{ $patient->email ?? '___' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_first_visit_date'] }}</td>
        <td>{{ $version->created_at->format('d/m/Y') }}</td>
    </tr>
</table>

{{-- Section 1: Special Situations --}}
<div class="section-title">1. {{ $t['anamnesis.section_1'] }}</div>
<div class="inline-section">
    {{ $chk($ss['pregnant'] ?? false) }} {{ $t['anamnesis.pregnant'] }}
    {{ $chk(!($ss['pregnant'] ?? false)) }} {{ $no }}
    @if(!empty($ss['pregnant']) && !empty($ss['gestational_age']))
        &nbsp;&mdash; {{ $t['anamnesis.gestational_age'] }}: <span class="field-value">{{ $ss['gestational_age'] }}</span>
    @endif
    &nbsp;&nbsp;|&nbsp;&nbsp;
    {{ $chk($ss['menstruating'] ?? false) }} {{ $t['anamnesis.menstruating'] }}
    {{ $chk(!($ss['menstruating'] ?? false)) }} {{ $no }}
</div>

{{-- Section 2: Allergies / Intolerances --}}
<div class="section-title">2. {{ $t['anamnesis.section_2'] }}</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.drug_allergies'] }}:</strong>
    <span class="field-value">{{ $joinOrDash($al['drug_allergies'] ?? []) }}</span>
</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.non_drug_allergies'] }}:</strong>
    <span class="field-value">{{ $joinOrDash($al['non_drug_allergies'] ?? []) }}</span>
</div>

{{-- Section 3: Current Treatment --}}
<div class="section-title">3. {{ $t['anamnesis.section_3'] }}</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.medications'] }}:</strong>
    @if(!empty($ct['medications']) && is_array($ct['medications']) && count($ct['medications']) > 0)
        @foreach($ct['medications'] as $i => $med)
            <span class="field-value">{{ $med['name'] ?? '?' }} ({{ $med['dose'] ?? '?' }})</span>@if($i < count($ct['medications']) - 1), @endif
        @endforeach
    @else
        ___
    @endif
</div>

<div class="inline-section">
    <strong>{{ $t['anamnesis.antibiotics_last_2_weeks'] }}:</strong>
    {{ $chk(!empty($ct['antibiotics']) && is_array($ct['antibiotics']) && count($ct['antibiotics']) > 0) }}
    {{ $yes }}
    {{ $chk(empty($ct['antibiotics']) || !is_array($ct['antibiotics']) || count($ct['antibiotics']) === 0) }}
    {{ $no }}
    @if(!empty($ct['antibiotics']) && is_array($ct['antibiotics']))
        &nbsp;&mdash;
        @foreach($ct['antibiotics'] as $i => $ab)
            <span class="field-value">{{ $ab['name'] ?? $ab['drug'] ?? '?' }} ({{ $ab['dose'] ?? '?' }})</span>@if($i < count($ct['antibiotics']) - 1), @endif
        @endforeach
    @endif
</div>

<div class="inline-section">
    <strong>{{ $t['anamnesis.anticoagulants'] }}:</strong>
    {{ $chk($ct['anticoagulants'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($ct['anticoagulants'] ?? false)) }}
    {{ $no }}
    @if(!empty($ct['anticoagulants']))
        @if(!empty($ct['anticoagulant_drug']))
            &nbsp;&mdash; {{ $t['anamnesis.drug_and_dose'] }}: <span class="field-value">{{ $ct['anticoagulant_drug'] }}</span>
        @endif
        @if(!empty($ct['anticoagulant_inr']))
            &nbsp; INR: <span class="field-value">{{ $ct['anticoagulant_inr'] }}</span>
        @endif
    @endif
</div>

<div class="inline-section">
    <strong>{{ $t['anamnesis.bisphosphonates'] }}:</strong>
    {{ $chk($ct['bisphosphonates'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($ct['bisphosphonates'] ?? false)) }}
    {{ $no }}
    @if(!empty($ct['bisphosphonates']))
        &nbsp;&mdash;
        {{ $t['anamnesis.route_label'] }}:
        {{ $chk(($ct['bisphosphonate_route'] ?? '') === 'iv') }} i.v.
        {{ $chk(($ct['bisphosphonate_route'] ?? '') === 'oral') }} {{ $t['anamnesis.bisphosphonate_oral'] }}
        @if(!empty($ct['bisphosphonate_duration']))
            &nbsp; {{ $t['anamnesis.bisphosphonate_duration'] }}: <span class="field-value">{{ $ct['bisphosphonate_duration'] }}</span>
        @endif
        @if(!empty($ct['bisphosphonate_beta_ctx']))
            &nbsp; β-CTX: <span class="field-value">{{ $ct['bisphosphonate_beta_ctx'] }}</span>
        @endif
    @endif
</div>

{{-- Section 4: Diseases / Medical History --}}
<div class="section-title">4. {{ $t['anamnesis.section_4'] }}</div>

<div class="inline-section">
    <strong>{{ $t['anamnesis.congenital_diseases'] }}:</strong>
    <span class="field-value">{{ !empty($diseases['congenital_diseases']) ? $diseases['congenital_diseases'] : '___' }}</span>
    &nbsp;&nbsp;
    <strong>{{ $t['anamnesis.occupational_diseases'] }}:</strong>
    <span class="field-value">{{ !empty($diseases['occupational_diseases']) ? $diseases['occupational_diseases'] : '___' }}</span>
</div>

@php
    $categories = [
        'heart' => [
            'angina_pectoris', 'myocardial_infarction', 'arrhythmias', 'blocks',
            'failure', 'valvulopathies', 'endocarditis', 'cardiac_surgery',
        ],
        'vascular' => [
            'peripheral_arterial_disease', 'thrombophlebitis', 'hypotension',
            'hypertension', 'stroke',
        ],
        'respiratory' => ['asthma', 'emphysema', 'chronic_bronchitis', 'tuberculosis'],
        'digestive' => ['gastritis_ulcer'],
        'hepatic' => ['steatosis', 'chronic_hepatitis', 'cirrhosis'],
        'renal' => ['insufficiency', 'hemodialysis'],
        'diabetes' => ['insulin', 'oral'],
        'endocrine' => ['hypothyroidism', 'hyperthyroidism'],
        'rheumatic' => ['rheumatoid_arthritis', 'collagenoses'],
        'skeletal' => ['osteoporosis'],
        'neurological' => ['epilepsy'],
        'psychiatric' => ['depression', 'schizophrenia'],
        'neurovegetative' => ['panic_attacks'],
        'hematologic' => ['anemia', 'thalassemia', 'leukemia', 'hemophilia', 'thrombocytopenia', 'von_willebrand'],
        'infectious' => ['hepatitis_b', 'hepatitis_c', 'hepatitis_d', 'hiv'],
    ];
@endphp

@foreach($categories as $catKey => $fields)
    @php $catData = $diseases[$catKey] ?? []; @endphp
    <div class="disease-line">
        <span class="disease-label">{{ $t['anamnesis.diseases_' . $catKey] }}:</span>
        @foreach($fields as $i => $field)
            {{ $chk($catData[$field] ?? false) }} {{ $t['anamnesis.' . $catKey . '_' . $field] }}
            @if($field === 'myocardial_infarction' && !empty($catData['myocardial_infarction']))
                ({{ $t['anamnesis.when_label'] }}: <span class="field-value">{{ $catData['myocardial_infarction_when'] ?? '___' }}</span>)
            @endif
            @if($field === 'failure' && !empty($catData['failure']) && !empty($catData['failure_nyha']))
                (NYHA: <span class="field-value">{{ $catData['failure_nyha'] }}</span>)
            @endif
            @if($field === 'hypertension' && !empty($catData['hypertension']) && !empty($catData['hypertension_max']))
                (Max: <span class="field-value">{{ $catData['hypertension_max'] }}</span>)
            @endif
            @if($i < count($fields) - 1) &nbsp; @endif
        @endforeach
    </div>
@endforeach

{{-- Neoplasms --}}
<div class="disease-line">
    <span class="disease-label">{{ $t['anamnesis.diseases_neoplasms'] }}:</span>
    {{ $chk($diseases['neoplasms'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($diseases['neoplasms'] ?? false)) }}
    {{ $no }}
    @if(!empty($diseases['neoplasms']) && !empty($diseases['neoplasms_details']))
        &mdash; <span class="field-value">{{ $diseases['neoplasms_details'] }}</span>
    @endif
</div>

{{-- Other diseases --}}
<div class="disease-line">
    <span class="disease-label">{{ $t['anamnesis.diseases_other'] }}:</span>
    {{ $chk($diseases['other_diseases'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($diseases['other_diseases'] ?? false)) }}
    {{ $no }}
    @if(!empty($diseases['other_diseases']) && !empty($diseases['other_diseases_details']))
        &mdash; <span class="field-value">{{ $diseases['other_diseases_details'] }}</span>
    @endif
</div>


{{-- ========== PAGE 2: Surgical/Dental, Habits, Consent ========== --}}
<div class="page-break"></div>
<div class="page-header">{{ $t['anamnesis.clinic_name'] }} &mdash; {{ $t['anamnesis.title'] }}</div>

{{-- Section 5: Surgical History / Transfusions --}}
<div class="section-title">5. {{ $t['anamnesis.section_5'] }}</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.previous_surgeries'] }}:</strong>
    <span class="field-value">{{ !empty($sh['previous_surgeries']) ? $sh['previous_surgeries'] : '___' }}</span>
</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.transfusions'] }}:</strong>
    {{ $chk($sh['transfusions'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($sh['transfusions'] ?? false)) }}
    {{ $no }}
</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.surgery_complications'] }}:</strong>
    <span class="field-value">{{ !empty($sh['complications']) ? $sh['complications'] : '___' }}</span>
</div>

{{-- Section 6: Dental History --}}
<div class="section-title">6. {{ $t['anamnesis.section_6'] }}</div>
@php $anesthData = $dh['anesthesia_types'] ?? []; @endphp
<div class="inline-section">
    <strong>{{ $t['anamnesis.dental_anesthesia_types'] }}:</strong>
    {{ $chk($anesthData['local'] ?? false) }} {{ $t['anamnesis.anesthesia_local'] }}
    &nbsp;
    {{ $chk($anesthData['plexal'] ?? false) }} {{ $t['anamnesis.anesthesia_plexal'] }}
    &nbsp;
    {{ $chk($anesthData['troncular'] ?? false) }} {{ $t['anamnesis.anesthesia_troncular'] }}
    &nbsp;
    {{ $chk($anesthData['general'] ?? false) }} {{ $t['anamnesis.anesthesia_general'] }}
</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.adverse_reactions'] }}:</strong>
    <span class="field-value">{{ !empty($dh['adverse_reactions']) ? $dh['adverse_reactions'] : '___' }}</span>
</div>

{{-- Section 7: Habits / Substance Use --}}
<div class="section-title">7. {{ $t['anamnesis.section_7'] }}</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.tobacco'] }}:</strong>
    {{ $chk($hab['tobacco'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($hab['tobacco'] ?? false)) }}
    {{ $no }}
    @if(!empty($hab['tobacco']))
        &nbsp;&mdash; {{ $t['anamnesis.tobacco_amount'] }}: <span class="field-value">{{ $hab['tobacco_amount'] ?? '___' }}</span>
        &nbsp; {{ $t['anamnesis.tobacco_duration'] }}: <span class="field-value">{{ $hab['tobacco_duration'] ?? '___' }}</span>
    @endif
</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.alcohol'] }}:</strong>
    {{ $chk($hab['alcohol'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($hab['alcohol'] ?? false)) }}
    {{ $no }}
    @if(!empty($hab['alcohol']))
        &nbsp;&mdash; {{ $t['anamnesis.alcohol_amount'] }}: <span class="field-value">{{ $hab['alcohol_amount'] ?? '___' }}</span>
        &nbsp; {{ $t['anamnesis.alcohol_duration'] }}: <span class="field-value">{{ $hab['alcohol_duration'] ?? '___' }}</span>
    @endif
</div>
<div class="inline-section">
    <strong>{{ $t['anamnesis.drugs'] }}:</strong>
    {{ $chk($hab['drugs'] ?? false) }}
    {{ $yes }}
    {{ $chk(!($hab['drugs'] ?? false)) }}
    {{ $no }}
    @if(!empty($hab['drugs']))
        &nbsp;&mdash; {{ $t['anamnesis.drugs_amount'] }}: <span class="field-value">{{ $hab['drugs_amount'] ?? '___' }}</span>
        &nbsp; {{ $t['anamnesis.drugs_duration'] }}: <span class="field-value">{{ $hab['drugs_duration'] ?? '___' }}</span>
    @endif
</div>

{{-- GDPR & Informed Consent --}}
<div class="consent-box">
    <div class="consent-title">{{ $t['anamnesis.consent_title'] }}</div>
    <p>{{ $t['anamnesis.consent_text'] }}</p>
    <div style="margin-top: 6px;">
        {{ $chk($version->consent_given) }}
        {{ $t['anamnesis.consent_agree'] }}
    </div>
</div>

{{-- Signature Table --}}
<table class="signature-table">
    <tr>
        <td class="sig-header">{{ $t['anamnesis.dentist_label'] }}</td>
        <td class="sig-header">{{ $t['anamnesis.patient_label'] }}</td>
    </tr>
    <tr>
        <td>
            <div>{{ $t['anamnesis.signature_label'] }}:</div>
            <div class="sig-line"></div>
        </td>
        <td>
            <div>{{ $t['anamnesis.consent_signature'] }}:</div>
            @if(!empty($version->signature_data))
                <div style="margin-top: 4px;"><img src="{{ $version->signature_data }}" style="max-height: 50px; max-width: 100%;" /></div>
            @else
                <div class="sig-line"></div>
            @endif
        </td>
    </tr>
    <tr>
        <td>
            <div>{{ $t['anamnesis.name_label'] }}: <span class="field-blank"></span></div>
        </td>
        <td>
            <div>{{ $t['anamnesis.name_label'] }}: {{ $patient->last_name }}, {{ $patient->first_name }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div>{{ $t['anamnesis.consent_date'] }}: <span class="field-blank"></span></div>
        </td>
        <td>
            <div>{{ $t['anamnesis.consent_date'] }}: {{ $version->consent_given_at ? $version->consent_given_at->format('d/m/Y H:i') : $version->created_at->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

{{-- Footer --}}
<div class="footer-note">
    {{ $t['anamnesis.pdf_footer'] }}
</div>

</body>
</html>
