<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'paid',
        'benefit',
        'period_duration',
        'period_unit',
    ];

    public const PERIOD_UNITS = ['day', 'week', 'month', 'year'];

    /**
     * Get a human-readable period label (e.g. "1 month", "2 months").
     * Handles null period_unit/period_duration for pre-migration data.
     */
    public function getPeriodLabelAttribute(): string
    {
        $unit = $this->period_unit ?? 'month';
        $duration = (int) ($this->period_duration ?? 1);
        $unitKey = in_array($unit, self::PERIOD_UNITS) ? $unit : 'month';
        $suffix = $duration > 1 ? '_plural' : '';
        $key = 'period_' . $unitKey . $suffix;

        return $duration . ' ' . (__('messages.' . $key) ?: $unit);
    }

    public function clientSubscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }

    public function getDetails()
    {
        return __('messages.paid') . ': ' . $this->paid . ' | ' . __('messages.benefit') . ': ' . $this->benefit . ' | ' . __('messages.period') . ': ' . $this->period_label;
    }

    /**
     * Get the period end date from a given start date.
     */
    public function getPeriodEndFrom(\DateTimeInterface $from): \Carbon\Carbon
    {
        $carbon = \Carbon\Carbon::parse($from);
        return match ($this->period_unit) {
            'day' => $carbon->copy()->addDays($this->period_duration),
            'week' => $carbon->copy()->addWeeks($this->period_duration),
            'month' => $carbon->copy()->addMonths($this->period_duration),
            'year' => $carbon->copy()->addYears($this->period_duration),
            default => $carbon->copy()->addMonths($this->period_duration),
        };
    }
}
