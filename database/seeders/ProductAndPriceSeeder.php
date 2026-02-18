<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use Illuminate\Support\Facades\DB;

class ProductAndPriceSeeder extends Seeder
{
    /**
     * Services (columns in the price table):
     * 1 = N-Wash Iron  (Normal Wash + Iron)
     * 2 = Ex-Wash Iron (Express Wash + Iron)
     * 3 = N-Iron       (Normal Iron only)
     * 4 = Ex-Iron      (Express Iron only)
     *
     * Prices are in KWD. null means the service is not available for that product.
     * Format: [product_name, n_wash_iron, ex_wash_iron, n_iron, ex_iron]
     */
    public function run(): void
    {
        // -------------------------------------------------------
        // 1. Clear existing data (safe order to avoid FK issues)
        // -------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProductServicePrice::truncate();
        // Only delete products/services that are NOT referenced by orders
        // to avoid breaking existing order data. We use forceDelete for soft-deleted.
        DB::table('product_service_prices')->truncate();
        DB::table('products')->whereNotIn('id', function ($q) {
            $q->select('product_id')->from('order_product_services');
        })->delete();
        DB::table('product_services')->whereNotIn('id', function ($q) {
            $q->select('product_service_id')->from('order_product_services');
        })->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // -------------------------------------------------------
        // 2. Seed the 4 services
        // -------------------------------------------------------
        $serviceNames = [
            'غسيل عادي + كوي - Normal Wash & Iron',
            'غسيل مستعجل + كوي - Express Wash & Iron',
            'كوي عادي - Normal Iron',
            'كوي مستعجل - Express Iron',
        ];

        $serviceIds = [];
        foreach ($serviceNames as $name) {
            $service = ProductService::firstOrCreate(['name' => $name]);
            $serviceIds[] = $service->id;
        }

        [$s1, $s2, $s3, $s4] = $serviceIds;

        // -------------------------------------------------------
        // 3. Products with prices
        // Format: [name, n_wash_iron, ex_wash_iron, n_iron, ex_iron]
        // null = service not available for this product
        // -------------------------------------------------------
        $products = [
            // From the price table (Image 2)
            ['هودي - Hoodie',               1.000, 1.500, 0.500, 0.750],
            ['بلوزة - Blouse',              0.500, 0.750, 0.250, 0.400],
            ['فانيلة داخلية - Singlet',     0.350, 0.500, 0.250, 0.300],
            ['سليب - Slip',                 0.400, 0.600, 0.250, 0.300],
            ['بيجاما - Night Suit',         1.000, 1.300, 0.500, 0.750],
            ['قميص نوم - Night Gown',       1.250, 1.500, 0.600, 0.750],
            ['قبعة - Hat',                  1.000, 1.500, null,  null ],
            ['ملابس رياضية - Gym Clothes',  0.600, 0.850, 0.300, 0.500],
            ['فستان ضيق - Sheath Dress',    0.650, 0.800, 0.300, 0.500],
            ['جوارب - Stockings',           0.600, 0.800, 0.300, 0.500],
            ['وشاح - Scarf',                0.500, 0.750, 0.250, 0.400],
            ['بيني - Beanie',               0.500, 0.750, null,  null ],
            ['مريلة أطفال - Baby Apron',    0.500, 0.650, null,  null ],
            ['ماكسي - Maxi',                1.500, 2.000, 0.750, 1.000],
            ['حقيبة يد - Handbag',          1.000, 1.500, null,  null ],
            ['سترة صوف - Sweater',          0.750, 1.000, 0.500, 0.650],
            ['مايوه - Swimsuit',            0.750, 1.000, 0.500, 0.650],
            ['ثونج - Thong',                0.250, 0.350, 0.250, 0.350],
            ['تانك توب - Tank Top',         0.350, 0.500, 0.250, 0.350],
            ['حمالة صدر - Bra',             0.350, 0.500, 0.250, 0.350],
            ['تنورة - Skirt',               1.000, 1.250, 0.500, 0.750],
            ['سجادة باب - Doormat',         0.750, 1.000, null,  null ],
            ['بدلة قفز - Jumpsuit',         1.250, 1.500, 0.600, 0.850],

            // From the product list (Image 1) — services priced similarly
            ['قفازات - Gloves',             0.500, 0.750, null,  null ],
            ['ملابس صلاة - Prayer Clothes', 0.750, 1.000, 0.500, 0.650],
            ['كابتن - Captain Uniform',     1.000, 1.500, 0.500, 0.750],
            ['يونيفورم مكتب - Office Uniform', 1.000, 1.500, 0.500, 0.750],
            ['بدلة عمل - Boiler Suit',      1.000, 1.500, 0.500, 0.750],
            ['مفرش طاولة - Tablecloth',     1.000, 1.500, 0.500, 0.750],
            ['ستارة - Curtains',            2.000, 3.000, 1.000, 1.500],
            ['سجادة - Carpet',              2.000, 3.000, null,  null ],
            ['شنطة - Bag',                  1.000, 1.500, null,  null ],
            ['حذاء - Shoes',                1.000, 1.500, null,  null ],
            ['ربطة عنق - Neck Tie',         0.500, 0.750, 0.250, 0.400],
            ['طاقية - Cap',                 0.500, 0.750, null,  null ],
            ['بطانية مفردة - Blanket (Single)', 1.500, 2.000, 0.750, 1.000],
            ['بطانية مزدوجة - Blanket (Double)', 2.000, 2.500, 1.000, 1.500],
            ['بطانية صغيرة - Blanket (Small)', 1.000, 1.500, 0.500, 0.750],
            ['بطانية غبة - Blanket (Gabha)', 2.500, 3.000, 1.250, 1.750],
            ['شرشف - Bed Sheet',            1.000, 1.500, 0.500, 0.750],
            ['روب حمام - Bathrobe',         1.000, 1.500, 0.500, 0.750],
            ['منشفة حمام - Bath Towel',     0.750, 1.000, 0.500, 0.650],
            ['يونيفورم مدرسة أطفال - Children\'s School Uniform', 1.000, 1.500, 0.500, 0.750],
            ['بدلة أطفال - Children\'s Suit', 1.000, 1.500, 0.500, 0.750],
            ['فستان زفاف - Wedding Dress',  3.000, 4.000, 1.500, 2.000],
            ['فستان سهرة - Evening Dress',  2.000, 2.500, 1.000, 1.500],
            ['فستان أطفال - Children\'s Dress', 0.750, 1.000, 0.500, 0.650],
            ['ملابس داخلية - Underwear',    0.350, 0.500, 0.250, 0.350],
            ['جوارب - Socks',               0.250, 0.350, null,  null ],
            ['بيجاما - Pajamas',            1.000, 1.300, 0.500, 0.750],
            ['مكسر - Mukasar',              0.750, 1.000, 0.500, 0.650],
            ['جينز - Jeans',                1.000, 1.500, 0.500, 0.750],
            ['جاكيت - Jacket',              1.000, 1.500, 0.500, 0.750],
            ['صديري - Vest/Gilet',          0.750, 1.000, 0.500, 0.650],
            ['معطف أطباء - Doctor\'s Coat', 1.000, 1.500, 0.500, 0.750],
            ['قميص - Shirt',                0.750, 1.000, 0.500, 0.650],
            ['بنطلون - Trousers/Pants',     1.000, 1.250, 0.500, 0.750],
            ['بدلة رياضية - Tracksuit',     1.000, 1.500, 0.500, 0.750],
            ['شورت - Shorts',               0.500, 0.750, 0.250, 0.400],
            ['تي شيرت - T-shirt',           0.500, 0.750, 0.250, 0.400],
            ['يونيفورم ضابط - Officer Uniform', 1.500, 2.000, 0.750, 1.000],
            ['ساري - Sari',                 1.500, 2.000, 0.750, 1.000],
            ['عمامة - Turban',              1.000, 1.500, 0.500, 0.750],
            ['نقاب - Niqab',                0.500, 0.750, 0.250, 0.400],
            ['حجاب/شال - Hijab/Scarf',      0.500, 0.750, 0.250, 0.400],
            ['شال - Shal',                  0.750, 1.000, 0.500, 0.650],
            ['غترة - Gutra',                0.750, 1.000, 0.500, 0.650],
            ['دشداشا صيفي - Dishdasha (Summer)', 1.500, 2.000, 0.750, 1.000],
            ['دشداشا شتوي - Dishdasha (Winter)', 2.000, 2.500, 1.000, 1.500],
            ['دراعة - Daraa',               1.500, 2.000, 0.750, 1.000],
            ['دراعة قطعتين - Daraa (Two-piece)', 2.000, 2.500, 1.000, 1.500],
            ['بشت شتوي - Besht (Winter)',   3.000, 4.000, 1.500, 2.000],
            ['عباءة - Abaya',               1.500, 2.000, 0.750, 1.000],
        ];

        // -------------------------------------------------------
        // 4. Insert products and their prices
        // -------------------------------------------------------
        foreach ($products as [$name, $p1, $p2, $p3, $p4]) {
            $product = Product::firstOrCreate(
                ['name' => $name],
                ['image_path' => 'products/logo.png']
            );
            
            // If already exists but has no image, update it
            if (!$product->image_path) {
                $product->update(['image_path' => 'products/logo.png']);
            }

            $prices = [
                $s1 => $p1,
                $s2 => $p2,
                $s3 => $p3,
                $s4 => $p4,
            ];

            foreach ($prices as $serviceId => $price) {
                if ($price !== null) {
                    ProductServicePrice::updateOrCreate(
                        [
                            'product_id'         => $product->id,
                            'product_service_id' => $serviceId,
                        ],
                        ['price' => $price]
                    );
                }
            }
        }

        $this->command->info('✅ Products and prices seeded successfully! (' . count($products) . ' products, 4 services)');
    }
}
