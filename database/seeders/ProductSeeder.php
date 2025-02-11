<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product; // Import your Product model

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            ['name' => 'بیشب - Bisht'],
            ['name' => 'العباءة - Abaya'],
            ['name' => 'دراعة قطعتين - Daraa (Two-piece)'],
            ['name' => 'دشداشا صیفی - Dishdasha (Summer)'],
            ['name' => 'دراعة - Daraa'],
            ['name' => 'بیشت شنوی - Besht (Winter)'],
            ['name' => 'دشداشا شنوی - Dishdasha (Winter)'],
            ['name' => 'غترة - Gutra'],
            ['name' => 'عصابة - Egal (Headband)'],
            ['name' => 'حجاب - Hijab'],
            ['name' => 'شال - Scarf'],
            ['name' => 'ساری - Sari'],
            ['name' => 'البنجابي - Punjabi'],
            ['name' => 'نقاب - Niqab'],
            ['name' => 'شماغ - Shimagh'],
            ['name' => 'سترة صوف - Cardigan'],
            ['name' => 'بلوفر أطفال - Children\'s Sweater'],
            ['name' => 'قميص أطفال - Children\'s Shirt'],
            ['name' => 'بلوزة - Blouse'],
            ['name' => 'تي شيرت أطفال - Children\'s T-shirt'],
            ['name' => 'جاكيت - Jacket'],
            ['name' => 'معطف - Coat'],
            ['name' => 'بنطلون - Pants/Trousers'],
            ['name' => 'تنورة - Skirt'],
            ['name' => 'فستان - Dress'],
            ['name' => 'قميص رجالي - Men\'s Shirt'],
            ['name' => 'تي شيرت - T-shirt'],
            ['name' => 'بدلة - Suit'],
            ['name' => 'كيمونو - Kimono'],
            ['name' => 'جلباب - Jilbab'],
            ['name' => 'سراويل قطنية - Cotton Pants'],
            ['name' => 'سترة رياضية - Tracksuit'],
            ['name' => 'قميص نوم - Nightgown'],
            ['name' => 'بيجاما - Pajamas'],
            ['name' => 'ملابس داخلية - Underwear'],
            ['name' => 'جوارب - Socks'],
            ['name' => 'قفازات - Gloves'],
            ['name' => 'وشاح - Muffler'],
            ['name' => 'كوفية - Keffiyeh'],
            ['name' => 'طاقية - Cap'],
        ];

        Product::insert($products);
    }
}
