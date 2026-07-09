<?php

namespace Database\Seeders;

use App\Models\BrandVoice;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DemoDataSeeder — local development/testing convenience only. Creates
 * a realistic starting dataset (categories matching Equiper's real
 * corrected taxonomy, sample HORECA products, an active Brand Voice)
 * so a developer can exercise F4/F5/F6/F7 end-to-end without waiting on
 * a real Salla sync.
 *
 * Guarded against production — never seeds fake data into a live
 * environment.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoDataSeeder skipped — refusing to seed demo data in production.');

            return;
        }

        $organization = Organization::query()->where('slug', 'equiper')->firstOrFail();

        $categoryNames = [
            'ماكينات إسبريسو', 'مطاحن قهوة', 'أفران تجارية', 'ثلاجات وتبريد',
            'أدوات مطبخ', 'مستلزمات تيك أواي',
        ];

        $categories = collect($categoryNames)->mapWithKeys(function (string $name) use ($organization) {
            $category = Category::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => Str::slug($name)],
                ['name' => $name, 'status' => 'active']
            );

            return [$name => $category];
        });

        $sampleProducts = [
            ['name' => 'ماكينة إسبريسو Wega Concept', 'category' => 'ماكينات إسبريسو', 'brand' => 'Wega', 'agency' => true, 'price' => 18500],
            ['name' => 'فرن حراري UNOX ChefTop', 'category' => 'أفران تجارية', 'brand' => 'UNOX', 'agency' => true, 'price' => 32000],
            ['name' => 'مطحنة قهوة BFC Zenith', 'category' => 'مطاحن قهوة', 'brand' => 'BFC', 'agency' => true, 'price' => 4200],
            ['name' => 'ثلاجة عرض تجارية EKA', 'category' => 'ثلاجات وتبريد', 'brand' => 'EKA', 'agency' => true, 'price' => 9800],
        ];

        foreach ($sampleProducts as $data) {
            Product::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'salla_product_id' => 'demo-'.Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'category_id' => $categories[$data['category']]->id,
                    'salla_category_name' => $data['category'], // matches, so NOT flagged as miscategorized
                    'brand_name' => $data['brand'],
                    'is_agency_brand' => $data['agency'],
                    'price' => $data['price'],
                    'lifecycle_state' => 'draft',
                    'stock_quantity' => 25,
                    'stock_status' => 'in_stock',
                ]
            );
        }

        // A deliberately MIScategorized demo product, so F4's bulk
        // re-categorization workflow has something real to fix.
        Product::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'salla_product_id' => 'demo-miscategorized-fridge'],
            [
                'name' => 'ثلاجة تخزين عمودية',
                'category_id' => $categories['أدوات مطبخ']->id, // wrong on purpose
                'salla_category_name' => 'ثلاجات وتبريد', // Salla says the correct thing; ours is wrong — mirrors the real known issue
                'brand_name' => 'EKA',
                'is_agency_brand' => true,
                'price' => 7600,
                'lifecycle_state' => 'draft',
                'stock_quantity' => 3, // low stock, exercises F14
                'stock_status' => 'low_stock',
            ]
        );

        BrandVoice::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'title' => 'صوت EQUIPER الرسمي'],
            [
                'tone_guidelines' => 'احترافي، واثق، يخاطب أصحاب المطاعم والفنادق كخبير موثوق لا كبائع.',
                'vocabulary_notes' => 'استخدم "معدات احترافية" بدل "أجهزة"، و"شريك تشغيلك" بدل "عميلنا".',
                'things_to_avoid' => 'تجنب المبالغة التسويقية والعبارات الفضفاضة مثل "الأفضل في السوق".',
                'brand_facts' => 'وكيل رسمي معتمد للعلامات الإيطالية Wega وBFC وUNOX وEKA — ميزة تنافسية لا يملكها أي منافس آخر على سلة.',
                'status' => 'active',
            ]
        );

        $this->command?->info('Demo data seeded: '.$categories->count().' categories, '.(count($sampleProducts) + 1).' products, 1 active Brand Voice.');
    }
}
