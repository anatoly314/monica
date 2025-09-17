<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPatternFieldsToRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            // Add new pattern fields for advanced reminder patterns
            $table->string('pattern_type')->nullable()->after('frequency_number');
            // Values: 'nth_weekday_of_month', 'nth_weekday_of_year', 'last_weekday_of_month'

            $table->integer('pattern_day_of_week')->nullable()->after('pattern_type');
            // 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday

            $table->integer('pattern_week_number')->nullable()->after('pattern_day_of_week');
            // 1=First, 2=Second, 3=Third, 4=Fourth, -1=Last

            $table->integer('pattern_month')->nullable()->after('pattern_week_number');
            // 1=January, ... 12=December, null=every month (for monthly patterns)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn([
                'pattern_type',
                'pattern_day_of_week',
                'pattern_week_number',
                'pattern_month'
            ]);
        });
    }
}