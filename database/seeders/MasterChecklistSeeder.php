<?php

namespace Database\Seeders;

use App\Models\MasterChecklist;
use Illuminate\Database\Seeder;

class MasterChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $defaultItems = [
            // Kondisi Fisik (condition)
            ['name' => 'Bodi & Cat Mulus', 'category' => 'condition', 'sort_order' => 1],
            ['name' => 'Kondisi Ban Baik', 'category' => 'condition', 'sort_order' => 2],
            ['name' => 'Lampu-Lampu Nyala', 'category' => 'condition', 'sort_order' => 3],
            ['name' => 'Kaca & Spion Utuh', 'category' => 'condition', 'sort_order' => 4],
            ['name' => 'Fungsi Rem Normal', 'category' => 'condition', 'sort_order' => 5],
            ['name' => 'Kebersihan Kabin', 'category' => 'condition', 'sort_order' => 6],
            
            // Kelengkapan Dinas (completeness)
            ['name' => 'STNK Fisik', 'category' => 'completeness', 'sort_order' => 1],
            ['name' => 'Ban Serep', 'category' => 'completeness', 'sort_order' => 2],
            ['name' => 'Dongkrak & Tools', 'category' => 'completeness', 'sort_order' => 3],
            ['name' => 'Kotak P3K', 'category' => 'completeness', 'sort_order' => 4],
        ];

        foreach ($defaultItems as$item) {
            MasterChecklist::updateOrCreate(
                ['name' => $item['name'], 'category' =>$item['category']],
                ['is_active' => true, 'sort_order' => $item['sort_order']]
            );
        }
    }
}