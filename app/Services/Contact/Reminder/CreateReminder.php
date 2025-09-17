<?php

namespace App\Services\Contact\Reminder;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;

class CreateReminder extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'initial_date' => 'required|date_format:Y-m-d',
            'frequency_type' => [
                'required',
                Rule::in(Reminder::$frequencyTypes),
            ],
            'frequency_number' => 'required_unless:frequency_type,pattern|nullable|integer',
            'title' => 'required|string|max:100000',
            'description' => 'nullable|max:1000000',
            'delible' => 'nullable|boolean',
            // Pattern-specific validation rules
            'pattern_type' => [
                'required_if:frequency_type,pattern',
                'nullable',
                Rule::in(Reminder::$patternTypes),
            ],
            'pattern_day_of_week' => 'required_if:frequency_type,pattern|nullable|integer|between:0,6',
            'pattern_week_number' => 'required_if:frequency_type,pattern|nullable|integer|between:-1,4',
            'pattern_month' => 'nullable|integer|between:1,12',
        ];
    }

    /**
     * Create a reminder.
     *
     * @param  array  $data
     * @return Reminder
     */
    public function execute(array $data): Reminder
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        $reminder = Reminder::create([
            'account_id' => $data['account_id'],
            'contact_id' => $data['contact_id'],
            'title' => $data['title'],
            'description' => $this->nullOrValue($data, 'description'),
            'initial_date' => $data['initial_date'],
            'frequency_type' => $data['frequency_type'],
            'frequency_number' => $data['frequency_type'] !== 'pattern' ? $data['frequency_number'] : null,
            'delible' => (isset($data['delible']) ? $data['delible'] : true),
            // Pattern fields
            'pattern_type' => $this->nullOrValue($data, 'pattern_type'),
            'pattern_day_of_week' => $this->nullOrValue($data, 'pattern_day_of_week'),
            'pattern_week_number' => $this->nullOrValue($data, 'pattern_week_number'),
            'pattern_month' => $this->nullOrValue($data, 'pattern_month'),
        ]);

        foreach ($contact->account->users as $user) {
            $reminder->schedule($user);
        }

        return $reminder;
    }
}
