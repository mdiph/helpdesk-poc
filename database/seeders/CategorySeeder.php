<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Hardware',
            'Software',
            'Network',
            'Account & Access',
            'Email',
            'Security',
            'Other',
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
