<?php

namespace App\Models\Contact;

use Carbon\Carbon;
use App\Traits\HasUuid;
use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ModelBindingHasherWithContact as Model;

/**
 * A reminder has two states: active and inactive.
 * An inactive reminder is basically a one_time reminder that has already be
 * sent once and has been marked inactive so we don't schedule it again.
 *
 * @property string $next_expected_date_human_readable
 * @property string $next_expected_date
 */
class Reminder extends Model
{
    use HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_birthday' => 'boolean',
        'delible' => 'boolean',
        'inactive' => 'boolean',
        'initial_date' => 'date:Y-m-d',
        'pattern_day_of_week' => 'integer',
        'pattern_week_number' => 'integer',
        'pattern_month' => 'integer',
    ];

    /**
     * Valid value for frequency type.
     *
     * @var array
     */
    public static $frequencyTypes = [
        'one_time', 'week', 'month', 'year', 'pattern',
    ];

    /**
     * Valid pattern types for advanced reminder scheduling.
     *
     * @var array
     */
    public static $patternTypes = [
        'nth_weekday_of_month',  // e.g., "3rd Monday of every month"
        'nth_weekday_of_year',   // e.g., "3rd Monday of October"
        'last_weekday_of_month', // e.g., "Last Friday of every month"
    ];

    /**
     * Get the account record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the Reminder Outbox records associated with the account.
     *
     * @return HasMany
     */
    public function reminderOutboxes()
    {
        return $this->hasMany(ReminderOutbox::class);
    }

    /**
     * Scope a query to only include active reminders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('inactive', false);
    }

    /**
     * Test if this reminder is the contact's birthday reminder.
     *
     * @return bool
     */
    public function isBirthdayReminder(): bool
    {
        return $this->contact !== null
            && $this->contact->birthday_reminder_id === $this->id;
    }

    /**
     * Calculate the next expected date for this reminder.
     *
     * @return Carbon
     */
    public function calculateNextExpectedDate($date = null)
    {
        if ($this->frequency_type === 'pattern' && $this->pattern_type) {
            // For pattern reminders, always calculate from current date/time
            // to ensure proper recalculation after each occurrence
            return $this->calculatePatternDate(null);
        }

        if (is_null($date)) {
            $date = $this->initial_date;
        }

        while ($date->isPast()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        if ($date->isToday()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        return $date;
    }

    /**
     * Calculate the next expected date using user timezone for this reminder.
     *
     * @return Carbon
     */
    public function calculateNextExpectedDateOnTimezone()
    {
        $date = $this->initial_date;
        $date = Carbon::create($date->year, $date->month, $date->day, 0, 0, 0,
                    DateHelper::getTimezone() ?? config('app.timezone'));

        return $this->calculateNextExpectedDate($date);
    }

    /**
     * Calculate next date for pattern-based reminder.
     *
     * @param  Carbon|null  $fromDate
     * @return Carbon
     */
    public function calculatePatternDate($fromDate = null)
    {
        if (!$fromDate) {
            $fromDate = now();
        }

        switch ($this->pattern_type) {
            case 'nth_weekday_of_month':
                return $this->getNthWeekdayOfMonth($fromDate);
            case 'nth_weekday_of_year':
                return $this->getNthWeekdayOfYear($fromDate);
            case 'last_weekday_of_month':
                return $this->getLastWeekdayOfMonth($fromDate);
            default:
                return $fromDate;
        }
    }

    /**
     * Get the nth weekday of the month.
     *
     * @param  Carbon  $date
     * @return Carbon
     */
    private function getNthWeekdayOfMonth(Carbon $date)
    {
        $targetDate = $date->copy()->startOfMonth();

        // Find first occurrence of the target weekday
        while ($targetDate->dayOfWeek != $this->pattern_day_of_week) {
            $targetDate->addDay();
        }

        // Add weeks to get to nth occurrence
        if ($this->pattern_week_number > 0) {
            $targetDate->addWeeks($this->pattern_week_number - 1);
        }

        // If the calculated date doesn't exist in this month (e.g., 5th Monday)
        // or if it's in the past, move to next month
        if ($targetDate->month != $date->month || $targetDate->lte($date)) {
            return $this->getNthWeekdayOfMonth($date->copy()->addMonth()->startOfMonth());
        }

        return $targetDate;
    }

    /**
     * Get the last weekday of the month.
     *
     * @param  Carbon  $date
     * @return Carbon
     */
    private function getLastWeekdayOfMonth(Carbon $date)
    {
        $targetDate = $date->copy()->endOfMonth()->startOfDay();

        // Find last occurrence of the target weekday
        while ($targetDate->dayOfWeek != $this->pattern_day_of_week) {
            $targetDate->subDay();
        }

        // If date is in past, move to next month
        if ($targetDate->lte($date)) {
            return $this->getLastWeekdayOfMonth($date->copy()->addMonth());
        }

        return $targetDate;
    }

    /**
     * Get the nth weekday of a specific month in the year.
     *
     * @param  Carbon  $date
     * @return Carbon
     */
    private function getNthWeekdayOfYear(Carbon $date)
    {
        // Start from the specified month of current year
        $targetDate = $date->copy()->month($this->pattern_month)->startOfMonth();

        // Find first occurrence of the target weekday in the month
        while ($targetDate->dayOfWeek != $this->pattern_day_of_week) {
            $targetDate->addDay();
        }

        // Add weeks to get to nth occurrence
        if ($this->pattern_week_number > 0) {
            $targetDate->addWeeks($this->pattern_week_number - 1);
        } elseif ($this->pattern_week_number == -1) {
            // For last occurrence, start from end of month
            $targetDate = $targetDate->copy()->endOfMonth()->startOfDay();
            while ($targetDate->dayOfWeek != $this->pattern_day_of_week) {
                $targetDate->subDay();
            }
        }

        // If we're past this year's occurrence, move to next year
        if ($targetDate->lte($date)) {
            $targetDate->addYear();
        }

        return $targetDate;
    }

    /**
     * Schedule the reminder to be sent.
     *
     * @param  User  $user
     * @return void
     */
    public function schedule(User $user)
    {
        // remove any existing scheduled reminders
        $this->reminderOutboxes->each->delete();

        // when should we send this reminder?
        $triggerDate = $this->calculateNextExpectedDate();

        // schedule the reminder in the outbox, one for each user of the account
        ReminderOutbox::create([
            'account_id' => $this->account_id,
            'reminder_id' => $this->id,
            'user_id' => $user->id,
            'planned_date' => $triggerDate,
            'nature' => 'reminder',
        ]);

        $this->scheduleNotifications($triggerDate, $user);
    }

    /**
     * Create all the notifications that are supposed to be sent
     * 30 and 7 days prior to the actual reminder.
     *
     * @param  Carbon  $triggerDate
     * @param  User  $user
     * @return void
     */
    public function scheduleNotifications(Carbon $triggerDate, User $user)
    {
        $date = $triggerDate->toDateString();
        $reminderRules = $this->account->reminderRules()->where('active', 1)->get();

        foreach ($reminderRules as $reminderRule) {
            $datePrior = Carbon::createFromFormat('Y-m-d', $date)
                ->subDays($reminderRule->number_of_days_before);

            if ($datePrior->lessThanOrEqualTo(now())) {
                continue;
            }

            ReminderOutbox::create([
                'account_id' => $this->account_id,
                'reminder_id' => $this->id,
                'user_id' => $user->id,
                'planned_date' => $datePrior->toDateString(),
                'nature' => 'notification',
                'notification_number_days_before' => $reminderRule->number_of_days_before,
            ]);
        }
    }
}
