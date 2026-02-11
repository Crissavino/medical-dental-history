<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'version',
        'form_data',
        'consent_given',
        'consent_given_at',
        'consent_ip',
        'language',
        'recorded_by',
        'signature_data',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
            'consent_given' => 'boolean',
            'consent_given_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function booted(): void
    {
        static::creating(function (AnamnesisVersion $version) {
            if (empty($version->version)) {
                $version->version = ($version->patient->anamnesisVersions()->max('version') ?? 0) + 1;
            }
        });
    }
}
