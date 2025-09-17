<form method="POST" action="{{ $action }}">
    @method($method)
    @csrf

    <h2>{{ trans('people.reminders_add_title', ['name' => $contact->first_name]) }}</h2>

    @include('partials.errors')

    <p>{{ trans('people.reminders_add_description') }}</p>

    {{-- Nature of reminder --}}
    <fieldset class="form-group nature">
        <div class="form-group">
            <input type="text" class="form-control" name="title" value="{{ old('title') ?? $reminder->title }}" required>
        </div>
    </fieldset>

    {{-- Date --}}
    <div class="form-group">
        <label for="initial_date">{{ trans('people.reminders_add_next_time') }}</label>

        <input type="date" id="initial_date" name="initial_date" class="form-control"
        @if (is_null($reminder->initial_date))
          value="{{ old('initial_date') ?? now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}"
        @else
          value="{{ old('initial_date') ?? $reminder->initial_date->toDateString() }}"
        @endif
          min="{{ now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}"
          max="{{ now(\App\Helpers\DateHelper::getTimezone())->addYears(10)->toDateString() }}"
        >

        <fieldset class="form-group frequency{{ $errors->has('frequency_type') ? ' has-error' : '' }}">

            {{-- One time reminder --}}
            <div class="form-check">
                <label class="form-check-label" for="frequency_type_once">
                    <input type="radio" id="frequency_type_once" class="form-check-input" name="frequency_type"
                           value="one_time"
                           {{ $reminder->frequency_type === 'one_time' ? 'checked' : '' }}
                           @change="frequencyType = 'one_time'"
                    >
                    {{ trans('people.reminders_add_once') }}
                </label>
            </div>

            {{-- Recurring reminder --}}
            <div class="form-check">
                <label class="form-check-label" for="frequency_type_recurrent">
                    <input type="radio" id="frequency_type_recurrent" class="form-check-input frequency-radio" name="frequency_type"
                           value="recurrent"
                           {{ $reminder->frequency_type !== null && $reminder->frequency_type !== 'one_time' && $reminder->frequency_type !== 'pattern' ? 'checked' : '' }}
                           @change="frequencyType = 'recurrent'"
                    >

                    {{ trans('people.reminders_add_recurrent') }}

                    <input type="number" class="form-control frequency-type" name="frequency_number"
                           value="{{ $reminder->frequency_number ?? 1 }}"
                           min="1"
                           max="115"
                           :disabled="reminders_frequency == 'one_time'">

                    <select name="frequency_number_select" :disabled="reminders_frequency == 'one_time'">
                        <option value="week"{{ $reminder->frequency_type  === 'week' ? 'selected' : '' }}>{{ trans('people.reminders_type_week') }}</option>
                        <option value="month"{{ $reminder->frequency_type  === 'month' ? 'selected' : '' }}>{{ trans('people.reminders_type_month') }}</option>
                        <option value="year"{{ $reminder->frequency_type  === 'year' ? 'selected' : '' }}>{{ trans('people.reminders_type_year') }}</option>
                    </select>

                    {{ trans('people.reminders_add_starting_from') }}

                </label>
            </div>

            {{-- Pattern-based reminder --}}
            <div class="form-check">
                <label class="form-check-label" for="frequency_type_pattern">
                    <input type="radio" id="frequency_type_pattern" class="form-check-input frequency-radio" name="frequency_type"
                           value="pattern"
                           {{ $reminder->frequency_type === 'pattern' ? 'checked' : '' }}
                           @change="frequencyType = 'pattern'"
                    >
                    {{ trans('people.reminders_add_pattern') }}
                </label>

                <div id="pattern_options" v-show="frequencyType === 'pattern'" style="margin-top: 15px; padding-left: 25px;">
                    <div class="form-group">
                        <label for="pattern_type">{{ trans('people.reminders_pattern_type') }}</label>
                        <select id="pattern_type" name="pattern_type" class="form-control" v-model="patternType" @change="updatePatternVisibility">
                            <option value="nth_weekday_of_month"{{ $reminder->pattern_type === 'nth_weekday_of_month' ? ' selected' : '' }}>{{ trans('people.reminders_pattern_nth_weekday_month') }}</option>
                            <option value="nth_weekday_of_year"{{ $reminder->pattern_type === 'nth_weekday_of_year' ? ' selected' : '' }}>{{ trans('people.reminders_pattern_nth_weekday_year') }}</option>
                            <option value="last_weekday_of_month"{{ $reminder->pattern_type === 'last_weekday_of_month' ? ' selected' : '' }}>{{ trans('people.reminders_pattern_last_weekday_month') }}</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pattern_week_number">{{ trans('people.reminders_pattern_week') }}</label>
                                <select id="pattern_week_number" name="pattern_week_number" class="form-control">
                                    <option value="1"{{ $reminder->pattern_week_number == 1 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_first') }}</option>
                                    <option value="2"{{ $reminder->pattern_week_number == 2 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_second') }}</option>
                                    <option value="3"{{ $reminder->pattern_week_number == 3 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_third') }}</option>
                                    <option value="4"{{ $reminder->pattern_week_number == 4 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_fourth') }}</option>
                                    <option value="-1"{{ $reminder->pattern_week_number == -1 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_last') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pattern_day_of_week">{{ trans('people.reminders_pattern_weekday') }}</label>
                                <select id="pattern_day_of_week" name="pattern_day_of_week" class="form-control">
                                    <option value="1"{{ $reminder->pattern_day_of_week == 1 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_monday') }}</option>
                                    <option value="2"{{ $reminder->pattern_day_of_week == 2 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_tuesday') }}</option>
                                    <option value="3"{{ $reminder->pattern_day_of_week == 3 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_wednesday') }}</option>
                                    <option value="4"{{ $reminder->pattern_day_of_week == 4 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_thursday') }}</option>
                                    <option value="5"{{ $reminder->pattern_day_of_week == 5 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_friday') }}</option>
                                    <option value="6"{{ $reminder->pattern_day_of_week == 6 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_saturday') }}</option>
                                    <option value="0"{{ $reminder->pattern_day_of_week == 0 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_sunday') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4" v-show="patternType === 'nth_weekday_of_year'">
                            <div class="form-group">
                                <label for="pattern_month">{{ trans('people.reminders_pattern_month') }}</label>
                                <select id="pattern_month" name="pattern_month" class="form-control">
                                    <option value=""{{ !$reminder->pattern_month ? ' selected' : '' }}>{{ trans('people.reminders_pattern_every_month') }}</option>
                                    <option value="1"{{ $reminder->pattern_month == 1 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_january') }}</option>
                                    <option value="2"{{ $reminder->pattern_month == 2 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_february') }}</option>
                                    <option value="3"{{ $reminder->pattern_month == 3 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_march') }}</option>
                                    <option value="4"{{ $reminder->pattern_month == 4 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_april') }}</option>
                                    <option value="5"{{ $reminder->pattern_month == 5 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_may') }}</option>
                                    <option value="6"{{ $reminder->pattern_month == 6 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_june') }}</option>
                                    <option value="7"{{ $reminder->pattern_month == 7 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_july') }}</option>
                                    <option value="8"{{ $reminder->pattern_month == 8 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_august') }}</option>
                                    <option value="9"{{ $reminder->pattern_month == 9 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_september') }}</option>
                                    <option value="10"{{ $reminder->pattern_month == 10 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_october') }}</option>
                                    <option value="11"{{ $reminder->pattern_month == 11 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_november') }}</option>
                                    <option value="12"{{ $reminder->pattern_month == 12 ? ' selected' : '' }}>{{ trans('people.reminders_pattern_december') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

    <div class="form-group">
        <label for="description">{{ trans('people.reminders_add_optional_comment') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') ?? $reminder->description }}</textarea>
    </div>

    <div class="form-group actions">
        <button type="submit" class="btn btn-primary">
            @if($update_or_add == 'add')
            {{ trans('people.reminders_add_cta') }}
            @elseif ($update_or_add == 'edit')
            {{ trans('people.reminders_edit_update_cta') }}
            @endif
        </button>
        <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div>
</form>
