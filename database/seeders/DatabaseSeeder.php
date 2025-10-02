<?php

namespace Database\Seeders;

use Database\Seeders\Central\OperationsAdminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OperationsAdminSeeder::class,
        ]);
    }
}
