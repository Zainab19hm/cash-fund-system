<?php
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'رواتب', 'type' => 'payment'],
            ['name' => 'مستلزمات مكتبية', 'type' => 'payment'],
            ['name' => 'صيانة', 'type' => 'payment'],
            ['name' => 'إيرادات مبيعات', 'type' => 'receipt'],
            ['name' => 'دعم مستثمرين', 'type' => 'receipt'],
            ['name' => 'خدمات متنوعة', 'type' => 'receipt'],
            ['name' => 'تعاون ونشاط', 'type' => 'receipt'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['name' => $cat['name']], ['type' => $cat['type']]);
        }
    }
}
