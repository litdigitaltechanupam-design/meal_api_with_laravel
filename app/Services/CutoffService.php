<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class CutoffService
{
    public function ensureCustomerCalendarChangeAllowed(string $scheduleDate, string $mealTime): void
    {
        $timezone = $this->timezone();
        $today = Carbon::now($timezone)->toDateString();

        if ($scheduleDate !== $today) {
            return;
        }

        $cutoffTime = $mealTime === 'lunch'
            ? $this->setting('lunch_cutoff_time', '10:00')
            : $this->setting('dinner_cutoff_time', '16:00');

        $cutoffAt = Carbon::parse($scheduleDate.' '.$cutoffTime, $timezone);
        $now = Carbon::now($timezone);

        abort_if(
            $now->greaterThanOrEqualTo($cutoffAt),
            422,
            ucfirst($mealTime).' selection is locked for today.'
        );
    }

    public function timezone(): string
    {
        return $this->setting('app_timezone', 'Asia/Dhaka');
    }

    private function setting(string $key, string $default): string
    {
        return (string) (Setting::query()->where('key', $key)->value('value') ?? $default);
    }
}
