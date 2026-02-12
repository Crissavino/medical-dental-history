export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'dentist' | 'assistant' | 'receptionist';
    email_verified_at?: string;
    has_signature: boolean;
    created_at: string;
    updated_at: string;
}

export interface Patient {
    id: number;
    identifier: string;
    first_name: string;
    last_name: string;
    full_name?: string;
    date_of_birth?: string;
    gender?: 'male' | 'female' | 'other';
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    county?: string;
    cnp?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
    latest_anamnesis?: AnamnesisVersion;
    encounters?: Encounter[];
    attachments?: Attachment[];
    anamnesis_versions?: AnamnesisVersion[];
}

export interface AnamnesisVersion {
    id: number;
    patient_id: number;
    version: number;
    form_data: AnamnesisFormData;
    consent_given: boolean;
    consent_given_at?: string;
    consent_ip?: string;
    language: 'en' | 'ro' | 'es';
    recorded_by?: number;
    recorder?: User;
    dentist_signature_data?: string | null;
    signed_by?: number | null;
    signed_at?: string | null;
    signer?: User;
    created_at: string;
    updated_at: string;
}

export interface AnamnesisFormData {
    special_situations: {
        pregnant: boolean;
        menstruating: boolean;
        gestational_age?: string;
    };
    allergies: {
        drug_allergies: string[];
        non_drug_allergies: string[];
    };
    current_treatment: {
        medications: Medication[];
        antibiotics_last_2_weeks: boolean;
        antibiotic_details: AntibioticDetail[];
        anticoagulants: boolean;
        anticoagulant_inr?: string;
        bisphosphonates: boolean;
        bisphosphonate_route?: 'oral' | 'iv' | '';
        bisphosphonate_duration?: string;
        bisphosphonate_beta_ctx?: string;
    };
    diseases: {
        heart: {
            angina_pectoris: boolean;
            myocardial_infarction: boolean;
            arrhythmias: boolean;
            blocks: boolean;
            heart_failure: boolean;
            heart_failure_nyha?: string;
            valvulopathies: boolean;
            endocarditis: boolean;
            cardiac_surgery: boolean;
        };
        vascular: {
            peripheral_arterial_disease: boolean;
            thrombophlebitis: boolean;
            hypotension: boolean;
            hypertension: boolean;
            hypertension_max?: string;
            stroke: boolean;
        };
        respiratory: {
            asthma: boolean;
            emphysema: boolean;
            chronic_bronchitis: boolean;
            tuberculosis: boolean;
        };
        digestive: {
            gastritis_ulcer: boolean;
        };
        hepatic: {
            steatosis: boolean;
            chronic_hepatitis: boolean;
            cirrhosis: boolean;
        };
        renal: {
            insufficiency: boolean;
            hemodialysis: boolean;
        };
        diabetes: {
            insulin_dependent: boolean;
            oral_antidiabetics: boolean;
        };
        endocrine: {
            hypothyroidism: boolean;
            hyperthyroidism: boolean;
        };
        rheumatic: {
            rheumatoid_arthritis: boolean;
            collagenoses: boolean;
        };
        skeletal: {
            osteoporosis: boolean;
        };
        neurological: {
            epilepsy: boolean;
        };
        psychiatric: {
            depression: boolean;
            schizophrenia: boolean;
        };
        neurovegetative: {
            panic_attacks: boolean;
        };
        hematologic: {
            anemia: boolean;
            thalassemia: boolean;
            leukemia: boolean;
            hemophilia: boolean;
            thrombocytopenia: boolean;
            von_willebrand: boolean;
        };
        infectious: {
            hepatitis_b: boolean;
            hepatitis_c: boolean;
            hepatitis_d: boolean;
            hiv: boolean;
        };
        neoplasms: boolean;
        neoplasms_details?: string;
        other_diseases: boolean;
        other_diseases_details?: string;
    };
    surgical_history: {
        previous_surgeries: string;
        transfusions: boolean;
        surgery_complications: string;
    };
    dental_history: {
        anesthesia_types: {
            local: boolean;
            plexal: boolean;
            troncular: boolean;
            general: boolean;
        };
        adverse_reactions: string;
    };
    habits: {
        tobacco: boolean;
        tobacco_amount?: string;
        tobacco_duration?: string;
        alcohol: boolean;
        alcohol_amount?: string;
        alcohol_duration?: string;
        drugs: boolean;
        drugs_amount?: string;
        drugs_duration?: string;
    };
}

export interface Medication {
    name: string;
    dose: string;
}

export interface AntibioticDetail {
    drug: string;
    dose: string;
}

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
}

export interface Treatment {
    id: number;
    encounter_id: number;
    tooth_number?: string;
    treatment_code: string;
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

export interface Attachment {
    id: number;
    attachable_type: string;
    attachable_id: number;
    uploaded_by: number;
    file_name: string;
    file_path: string;
    mime_type: string;
    file_size: number;
    description?: string;
    created_at: string;
    updated_at: string;
    uploader?: User;
}

export interface AuditLog {
    id: number;
    entity_type: string;
    entity_id: number;
    user_id?: number;
    action: 'created' | 'updated' | 'deleted';
    metadata_json?: Record<string, unknown>;
    ip_address?: string;
    created_at: string;
    user?: User;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

export interface PaginationLink {
    url?: string;
    label: string;
    active: boolean;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    flash?: {
        success?: string;
        error?: string;
    };
};
