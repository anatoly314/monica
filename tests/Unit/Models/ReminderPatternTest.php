<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\Contact\Reminder;
use App\Models\Contact\Contact;
use App\Models\Account\Account;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReminderPatternTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test third Monday of October pattern.
     *
     * @return void
     */
    public function test_third_monday_of_october_pattern()
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $reminder = new Reminder([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'frequency_type' => 'pattern',
            'pattern_type' => 'nth_weekday_of_year',
            'pattern_day_of_week' => 1, // Monday
            'pattern_week_number' => 3, // Third
            'pattern_month' => 10, // October
            'initial_date' => '2025-01-01',
            'title' => 'Test reminder',
        ]);
        $reminder->save();

        Carbon::setTestNow('2025-01-01');
        $nextDate = $reminder->calculateNextExpectedDate();

        // Third Monday of October 2025 is October 20th
        $this->assertEquals('2025-10-20', $nextDate->toDateString());
    }

    /**
     * Test last Friday of every month pattern.
     *
     * @return void
     */
    public function test_last_friday_of_every_month_pattern()
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $reminder = new Reminder([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'frequency_type' => 'pattern',
            'pattern_type' => 'last_weekday_of_month',
            'pattern_day_of_week' => 5, // Friday
            'pattern_week_number' => -1, // Not used for last_weekday_of_month
            'pattern_month' => null, // Every month
            'initial_date' => '2025-01-15',
            'title' => 'Test reminder',
        ]);
        $reminder->save();

        Carbon::setTestNow('2025-01-15');
        $nextDate = $reminder->calculateNextExpectedDate();

        // Last Friday of January 2025 is January 31st
        $this->assertEquals('2025-01-31', $nextDate->toDateString());

        // Test for February
        Carbon::setTestNow('2025-02-01');
        $nextDate = $reminder->calculateNextExpectedDate();

        // Last Friday of February 2025 is February 28th
        $this->assertEquals('2025-02-28', $nextDate->toDateString());
    }

    /**
     * Test first Monday of every month pattern.
     *
     * @return void
     */
    public function test_first_monday_of_every_month_pattern()
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $reminder = new Reminder([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'frequency_type' => 'pattern',
            'pattern_type' => 'nth_weekday_of_month',
            'pattern_day_of_week' => 1, // Monday
            'pattern_week_number' => 1, // First
            'pattern_month' => null, // Every month
            'initial_date' => '2025-03-01',
            'title' => 'Test reminder',
        ]);
        $reminder->save();

        Carbon::setTestNow('2025-03-01');
        $nextDate = $reminder->calculateNextExpectedDate();

        // First Monday of March 2025 is March 3rd
        $this->assertEquals('2025-03-03', $nextDate->toDateString());
    }

    /**
     * Test second Wednesday of every month pattern.
     *
     * @return void
     */
    public function test_second_wednesday_of_every_month_pattern()
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $reminder = new Reminder([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'frequency_type' => 'pattern',
            'pattern_type' => 'nth_weekday_of_month',
            'pattern_day_of_week' => 3, // Wednesday
            'pattern_week_number' => 2, // Second
            'pattern_month' => null,
            'initial_date' => '2025-04-01',
            'title' => 'Test reminder',
        ]);
        $reminder->save();

        Carbon::setTestNow('2025-04-01');
        $nextDate = $reminder->calculateNextExpectedDate();

        // Second Wednesday of April 2025 is April 9th
        $this->assertEquals('2025-04-09', $nextDate->toDateString());
    }

    /**
     * Test pattern moves to next month when current month's occurrence has passed.
     *
     * @return void
     */
    public function test_pattern_moves_to_next_month_when_passed()
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $reminder = new Reminder([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'frequency_type' => 'pattern',
            'pattern_type' => 'nth_weekday_of_month',
            'pattern_day_of_week' => 1, // Monday
            'pattern_week_number' => 1, // First
            'pattern_month' => null,
            'initial_date' => '2025-03-01',
            'title' => 'Test reminder',
        ]);
        $reminder->save();

        // Set current date after first Monday of March (March 3rd)
        Carbon::setTestNow('2025-03-10');
        $nextDate = $reminder->calculateNextExpectedDate();

        // Should return first Monday of April 2025 which is April 7th
        $this->assertEquals('2025-04-07', $nextDate->toDateString());
    }

    /**
     * Test pattern moves to next year when current year's occurrence has passed.
     *
     * @return void
     */
    public function test_pattern_moves_to_next_year_when_passed()
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $reminder = new Reminder([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'frequency_type' => 'pattern',
            'pattern_type' => 'nth_weekday_of_year',
            'pattern_day_of_week' => 4, // Thursday
            'pattern_week_number' => 4, // Fourth
            'pattern_month' => 11, // November (Thanksgiving)
            'initial_date' => '2025-01-01',
            'title' => 'Test reminder',
        ]);
        $reminder->save();

        // Set current date after Thanksgiving 2025
        Carbon::setTestNow('2025-12-01');
        $nextDate = $reminder->calculateNextExpectedDate();

        // Should return fourth Thursday of November 2026
        $this->assertEquals('2026-11-26', $nextDate->toDateString());
    }

    /**
     * Test last Sunday of specific month pattern.
     *
     * @return void
     */
    public function test_last_sunday_of_specific_month()
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $reminder = new Reminder([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'frequency_type' => 'pattern',
            'pattern_type' => 'nth_weekday_of_year',
            'pattern_day_of_week' => 0, // Sunday
            'pattern_week_number' => -1, // Last
            'pattern_month' => 5, // May
            'initial_date' => '2025-01-01',
            'title' => 'Test reminder',
        ]);
        $reminder->save();

        Carbon::setTestNow('2025-01-01');
        $nextDate = $reminder->calculateNextExpectedDate();

        // Last Sunday of May 2025 is May 25th
        $this->assertEquals('2025-05-25', $nextDate->toDateString());
    }
}