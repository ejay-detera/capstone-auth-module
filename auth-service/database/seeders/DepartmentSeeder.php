<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'IT'],
            ['name' => 'Human Resources'],
            ['name' => 'Finance'],
            ['name' => 'Operations'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']]);
        }
    }
}
