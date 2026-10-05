<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['1年A組', '1年B組'] as $name) {
            SchoolClass::firstOrCreate(['name' => $name]);
        }

        Subject::firstOrCreate(['name' => '数学', 'day_of_week' => 1, 'period_start' => 1, 'period_end' => 1]);
        Subject::firstOrCreate(['name' => '情報実習', 'day_of_week' => 3, 'period_start' => 3, 'period_end' => 5]);
    }
}