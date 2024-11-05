<?php

namespace Database\Seeders\v1\Tempo;

use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Database\Seeder;

class TimeEntriesSeeder extends Seeder
{
    public function run(): void
    {
        TimeEntry::factory()->count(5)->create();
    }
}
