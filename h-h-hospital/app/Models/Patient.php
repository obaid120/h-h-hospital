<?php

namespace App\Models;

use App\Enums\StaffRole;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

#[Fillable(['name', 'phone', 'age_years'])]
#[Hidden(['registration_key'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['age_years' => 'integer', 'registered_at' => 'immutable_datetime', 'is_demo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient): void {
            $patient->mr_number = 'TMP-'.Str::ulid();
        });
        static::created(function (Patient $patient): void {
            $patient->mr_number = 'HH-'.str_pad((string) $patient->id, 8, '0', STR_PAD_LEFT);
            $patient->saveQuietly();
        });
        static::updating(function (Patient $patient): void {
            if ($patient->isDirty('mr_number')) {
                throw new LogicException('A permanent patient MR cannot be changed.');
            }
        });
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeVisibleTo(Builder $query, User $actor): void
    {
        if (! $actor->is_active) {
            $query->whereRaw('1 = 0');
        } elseif ($actor->role === StaffRole::Doctor) {
            $query->whereHas('appointments', fn (Builder $visits): Builder => $visits->visibleTo($actor));
        }
    }
}
