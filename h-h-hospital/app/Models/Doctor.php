<?php

namespace App\Models;

use App\Enums\StaffRole;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'specialization', 'biography', 'photo_path', 'normal_fee_minor', 'priority_surcharge_minor', 'is_active'])]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'normal_fee_minor' => 'integer', 'priority_surcharge_minor' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(DoctorWeeklySchedule::class)->orderBy('weekday');
    }

    public function days(): HasMany
    {
        return $this->hasMany(DoctorDay::class);
    }

    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('user', fn (Builder $users): Builder => $users->where('is_active', true)->where('role', StaffRole::Doctor));
    }

    public static function feeInput(int $minor): string
    {
        return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function feeDisplay(int $minor): string
    {
        return config('hospital.currency').' '.number_format($minor / 100, 2);
    }
}
