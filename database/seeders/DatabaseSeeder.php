<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        if (app()->environment() === 'production') {
            $this->call([
                StatusSeeder::class,
            ]);
        } else {
            $this->call([
                StatusSeeder::class,
            ]);
        }
    }
}
