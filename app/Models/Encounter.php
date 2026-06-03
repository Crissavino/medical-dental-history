<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    /** @use HasFactory<\Database\Factories\EncounterFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'provider_id',
        'encounter_date',
        'chief_complaint',
        'clinical_notes',
        'diagnosis',
        'status',
        'patient_signature_data',
        'dentist_signature_data',
        'patient_signed_at',
        'dentist_signed_by',
        'dentist_signed_at',
        'signed_ip',
        'signed_hash',
        'rectifies_encounter_id',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
            'patient_signed_at' => 'datetime',
            'dentist_signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function dentistSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_signed_by');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function rectifies(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rectifies_encounter_id');
    }

    public function rectifier(): HasOne
    {
        return $this->hasOne(self::class, 'rectifies_encounter_id');
    }

    public function isLocked(): bool
    {
        return $this->patient_signature_data !== null
            && $this->dentist_signature_data !== null;
    }

    public function isSigned(): bool
    {
        return $this->status === 'completed'
            && $this->patient_signature_data !== null
            && $this->dentist_signature_data !== null;
    }
}
