<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'tooth_number',
        'treatment_code',
        'description',
        'notes',
        'surface',
        'cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
