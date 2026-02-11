<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            line-height: 1.4;
        }
        h1 {
            font-size: 14pt;
            text-align: center;
            margin: 0 0 2px 0;
        }
        h2 {
            font-size: 10pt;
            background-color: #e8e8e8;
            padding: 4px 8px;
            margin: 10px 0 6px 0;
            border-left: 3px solid #4a4a4a;
        }
        .clinic-name {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 4px;
            color: #333;
        }
        .subtitle {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-bottom: 12px;
        }
        .patient-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .patient-table td {
            padding: 3px 6px;
            border: 1px solid #ccc;
            font-size: 9pt;
        }
        .patient-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 30%;
        }
        .check {
            font-size: 10pt;
        }
        .check-row {
            margin: 2px 0;
        }
        .inline-field {
            display: inline;
            color: #333;
        }
        .field-value {
            color: #0055aa;
        }
        .meds-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .meds-table th, .meds-table td {
            padding: 3px 6px;
            border: 1px solid #ccc;
            font-size: 8pt;
            text-align: left;
        }
        .meds-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .diseases-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .diseases-table td {
            padding: 2px 4px;
            border: 1px solid #ddd;
            font-size: 8pt;
            vertical-align: top;
        }
        .diseases-table .cat-header {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .consent-box {
            border: 1px solid #999;
            padding: 8px;
            margin: 10px 0;
            font-size: 8pt;
            line-height: 1.5;
        }
        .consent-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 4px;
        }
        .signature-area {
            margin-top: 20px;
            width: 100%;
        }
        .signature-area td {
            padding: 4px;
            width: 50%;
            font-size: 9pt;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            height: 30px;
            margin-top: 10px;
        }
        .footer-note {
            font-size: 7pt;
            color: #888;
            text-align: center;
            margin-top: 15px;
            border-top: 1px solid #ddd;
            padding-top: 6px;
        }
        .page-break {
            page-break-before: always;
        }
        .habit-row td {
            padding: 3px 6px;
            border: 1px solid #ccc;
            font-size: 8pt;
        }
    </style>
</head>
<body>

{{-- ========== PAGE 1 ========== --}}
<div class="clinic-name">{{ $t['anamnesis.clinic_name'] }}</div>
<h1>{{ $t['anamnesis.title'] }}</h1>
<div class="subtitle">{{ $t['anamnesis.version'] }} {{ $version->version }} &mdash; {{ $version->created_at->format('d/m/Y H:i') }}</div>

{{-- Patient details --}}
<table class="patient-table">
    <tr>
        <td class="label">{{ $t['anamnesis.patient_full_name'] }}</td>
        <td>{{ $patient->last_name }}, {{ $patient->first_name }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_cnp_passport'] }}</td>
        <td>{{ $patient->cnp ?? '---' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_home_address'] }}</td>
        <td>{{ implode(', ', array_filter([$patient->address, $patient->city, $patient->county])) ?: '---' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_phone'] }}</td>
        <td>{{ $patient->phone ?? '---' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.patient_email'] }}</td>
        <td>{{ $patient->email ?? '---' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['patient.date_of_birth'] }}</td>
        <td>{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y') : '---' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['patient.gender'] }}</td>
        <td>
            @if($patient->gender)
                {{ $t['patient.' . $patient->gender] ?? $patient->gender }}
            @else
                ---
            @endif
        </td>
    </tr>
</table>

{{-- Section 1: Special Situations --}}
<h2>{{ $t['anamnesis.section_1'] }}</h2>
@php $ss = $formData['special_situations'] ?? []; @endphp
<div class="check-row">
    <span class="check">{{ !empty($ss['pregnant']) ? '☑' : '☐' }}</span> {{ $t['anamnesis.pregnant'] }}
    @if(!empty($ss['pregnant']) && !empty($ss['gestational_age']))
        &mdash; {{ $t['anamnesis.gestational_age'] }}: <span class="field-value">{{ $ss['gestational_age'] }}</span>
    @endif
</div>
<div class="check-row">
    <span class="check">{{ !empty($ss['menstruating']) ? '☑' : '☐' }}</span> {{ $t['anamnesis.menstruating'] }}
</div>

{{-- Section 2: Allergies --}}
<h2>{{ $t['anamnesis.section_2'] }}</h2>
@php $al = $formData['allergies'] ?? []; @endphp
<table class="patient-table">
    <tr>
        <td class="label">{{ $t['anamnesis.drug_allergies'] }}</td>
        <td>
            @if(!empty($al['drug_allergies']) && is_array($al['drug_allergies']))
                {{ implode(', ', $al['drug_allergies']) }}
            @else
                ---
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.non_drug_allergies'] }}</td>
        <td>
            @if(!empty($al['non_drug_allergies']) && is_array($al['non_drug_allergies']))
                {{ implode(', ', $al['non_drug_allergies']) }}
            @else
                ---
            @endif
        </td>
    </tr>
</table>

{{-- Section 3: Current Treatment --}}
<h2>{{ $t['anamnesis.section_3'] }}</h2>
@php $ct = $formData['current_treatment'] ?? []; @endphp

{{-- Medications table --}}
<p style="font-weight: bold; font-size: 8.5pt; margin: 4px 0 2px 0;">{{ $t['anamnesis.medications'] }}</p>
@if(!empty($ct['medications']) && is_array($ct['medications']) && count($ct['medications']) > 0)
    <table class="meds-table">
        <tr>
            <th style="width: 60%">{{ $t['anamnesis.medication_name'] }}</th>
            <th style="width: 40%">{{ $t['anamnesis.medication_dose'] }}</th>
        </tr>
        @foreach($ct['medications'] as $med)
            <tr>
                <td>{{ $med['name'] ?? '---' }}</td>
                <td>{{ $med['dose'] ?? '---' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p style="font-size: 8pt; color: #888;">---</p>
@endif

{{-- Antibiotics --}}
<p style="font-weight: bold; font-size: 8.5pt; margin: 8px 0 2px 0;">{{ $t['anamnesis.antibiotics_last_2_weeks'] }}</p>
@if(!empty($ct['antibiotics']) && is_array($ct['antibiotics']) && count($ct['antibiotics']) > 0)
    <table class="meds-table">
        <tr>
            <th style="width: 60%">{{ $t['anamnesis.antibiotic_drug'] }}</th>
            <th style="width: 40%">{{ $t['anamnesis.antibiotic_dose'] }}</th>
        </tr>
        @foreach($ct['antibiotics'] as $ab)
            <tr>
                <td>{{ $ab['name'] ?? $ab['drug'] ?? '---' }}</td>
                <td>{{ $ab['dose'] ?? '---' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p style="font-size: 8pt; color: #888;">---</p>
@endif

{{-- Anticoagulants, Bisphosphonates --}}
<div style="margin-top: 6px;">
    <span class="check">{{ !empty($ct['anticoagulants']) ? '☑' : '☐' }}</span> {{ $t['anamnesis.anticoagulants'] }}
    @if(!empty($ct['anticoagulants']) && !empty($ct['anticoagulant_inr']))
        &mdash; {{ $t['anamnesis.anticoagulant_inr'] }}: <span class="field-value">{{ $ct['anticoagulant_inr'] }}</span>
    @endif
</div>
<div style="margin-top: 3px;">
    <span class="check">{{ !empty($ct['bisphosphonates']) ? '☑' : '☐' }}</span> {{ $t['anamnesis.bisphosphonates'] }}
    @if(!empty($ct['bisphosphonates']))
        @if(!empty($ct['bisphosphonate_route']))
            &mdash; {{ $t['anamnesis.bisphosphonate_route'] }}:
            <span class="field-value">
                @if($ct['bisphosphonate_route'] === 'oral')
                    {{ $t['anamnesis.bisphosphonate_oral'] }}
                @elseif($ct['bisphosphonate_route'] === 'iv')
                    {{ $t['anamnesis.bisphosphonate_iv'] }}
                @else
                    {{ $ct['bisphosphonate_route'] }}
                @endif
            </span>
        @endif
        @if(!empty($ct['bisphosphonate_duration']))
            &mdash; {{ $t['anamnesis.bisphosphonate_duration'] }}: <span class="field-value">{{ $ct['bisphosphonate_duration'] }}</span>
        @endif
        @if(!empty($ct['bisphosphonate_beta_ctx']))
            &mdash; {{ $t['anamnesis.bisphosphonate_beta_ctx'] }}: <span class="field-value">{{ $ct['bisphosphonate_beta_ctx'] }}</span>
        @endif
    @endif
</div>


{{-- ========== PAGE 2: Diseases ========== --}}
<div class="page-break"></div>
<div class="clinic-name" style="font-size: 9pt;">{{ $t['anamnesis.clinic_name'] }}</div>
<h2>{{ $t['anamnesis.section_4'] }}</h2>

@php
    $diseases = $formData['diseases'] ?? [];

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

<table class="diseases-table">
    @foreach($categories as $catKey => $fields)
        <tr>
            <td class="cat-header" colspan="2">{{ $t['anamnesis.diseases_' . $catKey] }}</td>
        </tr>
        @php $catData = $diseases[$catKey] ?? []; @endphp
        @foreach(array_chunk($fields, 2) as $pair)
            <tr>
                @foreach($pair as $field)
                    <td style="width: 50%;">
                        <span class="check">{{ !empty($catData[$field]) ? '☑' : '☐' }}</span>
                        {{ $t['anamnesis.' . $catKey . '_' . $field] }}
                        @if($field === 'failure' && !empty($catData['failure']) && !empty($catData['failure_nyha']))
                            (NYHA: {{ $catData['failure_nyha'] }})
                        @endif
                        @if($field === 'hypertension' && !empty($catData['hypertension']) && !empty($catData['hypertension_max']))
                            (Max: {{ $catData['hypertension_max'] }})
                        @endif
                    </td>
                @endforeach
                @if(count($pair) === 1)
                    <td style="width: 50%;"></td>
                @endif
            </tr>
        @endforeach
    @endforeach
</table>

{{-- Neoplasms --}}
<div style="margin-top: 8px;">
    <span class="check">{{ !empty($diseases['neoplasms']) ? '☑' : '☐' }}</span>
    <strong>{{ $t['anamnesis.diseases_neoplasms'] }}</strong>
    @if(!empty($diseases['neoplasms']) && !empty($diseases['neoplasms_details']))
        &mdash; <span class="field-value">{{ $diseases['neoplasms_details'] }}</span>
    @endif
</div>

{{-- Other diseases --}}
<div style="margin-top: 4px;">
    <span class="check">{{ !empty($diseases['other_diseases']) ? '☑' : '☐' }}</span>
    <strong>{{ $t['anamnesis.diseases_other'] }}</strong>
    @if(!empty($diseases['other_diseases']) && !empty($diseases['other_diseases_details']))
        &mdash; <span class="field-value">{{ $diseases['other_diseases_details'] }}</span>
    @endif
</div>


{{-- ========== PAGE 3: Surgical/Dental History, Habits, Consent ========== --}}
<div class="page-break"></div>
<div class="clinic-name" style="font-size: 9pt;">{{ $t['anamnesis.clinic_name'] }}</div>

{{-- Section 5: Surgical History --}}
<h2>{{ $t['anamnesis.section_5'] }}</h2>
@php $sh = $formData['surgical_history'] ?? []; @endphp
<table class="patient-table">
    <tr>
        <td class="label">{{ $t['anamnesis.previous_surgeries'] }}</td>
        <td>{{ !empty($sh['previous_surgeries']) ? $sh['previous_surgeries'] : '---' }}</td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.transfusions'] }}</td>
        <td>
            <span class="check">{{ !empty($sh['transfusions']) ? '☑' : '☐' }}</span>
            {{ !empty($sh['transfusions']) ? ($t['app.yes'] ?? 'Yes') : ($t['app.no'] ?? 'No') }}
        </td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.surgery_complications'] }}</td>
        <td>{{ !empty($sh['complications']) ? $sh['complications'] : '---' }}</td>
    </tr>
</table>

{{-- Section 6: Dental History --}}
<h2>{{ $t['anamnesis.section_6'] }}</h2>
@php $dh = $formData['dental_history'] ?? []; @endphp

<p style="font-weight: bold; font-size: 8.5pt; margin: 4px 0 2px 0;">{{ $t['anamnesis.dental_anesthesia_types'] }}</p>
@php
    $anesthesiaTypes = [
        'local' => $t['anamnesis.anesthesia_local'],
        'plexal' => $t['anamnesis.anesthesia_plexal'],
        'troncular' => $t['anamnesis.anesthesia_troncular'],
        'general' => $t['anamnesis.anesthesia_general'],
    ];
    $anesthesiaData = $dh['anesthesia_types'] ?? [];
@endphp
@foreach($anesthesiaTypes as $key => $label)
    <div class="check-row">
        <span class="check">{{ !empty($anesthesiaData[$key]) ? '☑' : '☐' }}</span> {{ $label }}
    </div>
@endforeach

<table class="patient-table" style="margin-top: 6px;">
    <tr>
        <td class="label">{{ $t['anamnesis.adverse_reactions'] }}</td>
        <td>{{ !empty($dh['adverse_reactions']) ? $dh['adverse_reactions'] : '---' }}</td>
    </tr>
</table>

{{-- Section 7: Habits --}}
<h2>{{ $t['anamnesis.section_7'] }}</h2>
@php $hab = $formData['habits'] ?? []; @endphp
<table class="patient-table">
    <tr>
        <td class="label" style="width: 25%;">{{ $t['anamnesis.tobacco'] }}</td>
        <td style="width: 8%; text-align: center;">
            <span class="check">{{ !empty($hab['tobacco']) ? '☑' : '☐' }}</span>
        </td>
        <td style="width: 33%;">
            @if(!empty($hab['tobacco']))
                {{ $t['anamnesis.tobacco_amount'] }}: <span class="field-value">{{ $hab['tobacco_amount'] ?? '---' }}</span>
            @endif
        </td>
        <td style="width: 34%;">
            @if(!empty($hab['tobacco']))
                {{ $t['anamnesis.tobacco_duration'] }}: <span class="field-value">{{ $hab['tobacco_duration'] ?? '---' }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.alcohol'] }}</td>
        <td style="text-align: center;">
            <span class="check">{{ !empty($hab['alcohol']) ? '☑' : '☐' }}</span>
        </td>
        <td>
            @if(!empty($hab['alcohol']))
                {{ $t['anamnesis.alcohol_amount'] }}: <span class="field-value">{{ $hab['alcohol_amount'] ?? '---' }}</span>
            @endif
        </td>
        <td>
            @if(!empty($hab['alcohol']))
                {{ $t['anamnesis.alcohol_duration'] }}: <span class="field-value">{{ $hab['alcohol_duration'] ?? '---' }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">{{ $t['anamnesis.drugs'] }}</td>
        <td style="text-align: center;">
            <span class="check">{{ !empty($hab['drugs']) ? '☑' : '☐' }}</span>
        </td>
        <td>
            @if(!empty($hab['drugs']))
                {{ $t['anamnesis.drugs_amount'] }}: <span class="field-value">{{ $hab['drugs_amount'] ?? '---' }}</span>
            @endif
        </td>
        <td>
            @if(!empty($hab['drugs']))
                {{ $t['anamnesis.drugs_duration'] }}: <span class="field-value">{{ $hab['drugs_duration'] ?? '---' }}</span>
            @endif
        </td>
    </tr>
</table>

{{-- GDPR Consent --}}
<div class="consent-box">
    <div class="consent-title">{{ $t['anamnesis.consent_title'] }}</div>
    <p>{{ $t['anamnesis.consent_text'] }}</p>
    <div style="margin-top: 6px;">
        <span class="check">{{ $version->consent_given ? '☑' : '☐' }}</span>
        {{ $t['anamnesis.consent_agree'] }}
    </div>
</div>

{{-- Signature area --}}
<table class="signature-area">
    <tr>
        <td>
            <strong>{{ $t['anamnesis.consent_date'] }}:</strong>
            {{ $version->consent_given_at ? $version->consent_given_at->format('d/m/Y') : $version->created_at->format('d/m/Y') }}
        </td>
        <td style="text-align: right;">
            <strong>{{ $t['anamnesis.consent_signature'] }}:</strong>
            <div class="signature-line"></div>
        </td>
    </tr>
</table>

{{-- Footer --}}
<div class="footer-note">
    {{ $t['anamnesis.clinic_name'] }} &mdash; {{ $t['anamnesis.title'] }}
    &mdash; {{ $t['anamnesis.version'] }} {{ $version->version }}
    &mdash; {{ $patient->identifier }}
    <br>
    @if($lang === 'ro')
        Document confidențial. Datele medicale sunt protejate conform GDPR (Regulamentul UE 2016/679).
    @else
        Confidential document. Medical data is protected under GDPR (EU Regulation 2016/679).
    @endif
</div>

</body>
</html>
