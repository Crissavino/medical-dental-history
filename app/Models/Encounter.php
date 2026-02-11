<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'provider_id',
        'encounter_date',
        'chief_complaint',
        'clinical_notes',
        'diagnosis',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
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

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
