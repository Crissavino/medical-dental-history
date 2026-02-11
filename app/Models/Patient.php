<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'identifier',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
        'city',
        'county',
        'cnp',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function anamnesisVersions(): HasMany
    {
        return $this->hasMany(AnamnesisVersion::class);
    }

    public function latestAnamnesis()
    {
        return $this->hasOne(AnamnesisVersion::class)->latestOfMany();
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            if (empty($patient->identifier)) {
                $patient->identifier = 'P-' . str_pad(
                    (static::withTrashed()->max('id') ?? 0) + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
