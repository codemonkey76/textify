<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $account = Account::create([
            'email' => 'shane@textify.asgcom.net'
        ]);

        $account->destinations()->create(['phone' => '0400588588']);
        $account->destinations()->create(['phone' => '0432146173']);
    }
}
