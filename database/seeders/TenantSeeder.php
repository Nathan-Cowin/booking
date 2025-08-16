<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::firstOrCreate([
            'name' => 'test',
            'domain' => 'test.com',
            'database' => 'test',
        ]);
    }
}
