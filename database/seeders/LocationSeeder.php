<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['name' => 'الأحمدي'], // Al Ahmadi
            ['name' => 'العاصمة'], // Al Asimah (Capital)
            ['name' => 'الفروانية'], // Al Farwaniyah
            ['name' => 'الجهراء'], // Al Jahra
            ['name' => 'حولي'], // Hawalli
            ['name' => 'مبارك الكبير'], // Mubarak Al-Kabeer
        ];

        foreach ($provinces as $provinceData) {
            $province = Province::create($provinceData);

            $cities = [];
            switch ($province->name) {
                case 'الأحمدي':
                    $cities = [
                        ['name' => 'الأحمدي'], // Al Ahmadi (City)
                        ['name' => 'الفنطاس'], // Al Fintas
                        ['name' => 'المهبولة'], // Al Mahboula
                    ];
                    break;
                case 'العاصمة':
                    $cities = [
                        ['name' => 'مدينة الكويت'], // Kuwait City
                        ['name' => 'الشرق'], // Sharq
                        ['name' => 'بنيد القار'], // Bneid Al-Gar
                    ];
                    break;
                case 'الفروانية':
                    $cities = [
                        ['name' => 'الفروانية'], // Al Farwaniyah (City)
                        ['name' => 'خيطان'], // Khaitan
                        ['name' => 'جليب الشيوخ'], // Jleeb Al-Shuyoukh
                    ];
                    break;
                case 'الجهراء':
                    $cities = [
                        ['name' => 'الجهراء'], // Al Jahra (City)
                        ['name' => 'العيون'], // Al Oyoun
                        ['name' => 'النعيم'], // Al Naim
                    ];
                    break;
                case 'حولي':
                    $cities = [
                        ['name' => 'حولي'], // Hawalli (City)
                        ['name' => 'السالمية'], // Salmiya
                        ['name' => 'مشرف'], // Mishref
                    ];
                    break;
                case 'مبارك الكبير':
                    $cities = [
                        ['name' => 'مبارك الكبير'], // Mubarak Al-Kabeer (City)
                        ['name' => 'العدان'], // Adan
                        ['name' => 'القصور'], // Al Qusour
                    ];
                    break;
            }

            foreach ($cities as $cityData) {
                $city = $province->cities()->create($cityData);

                $addresses = [
                    [
                        'street' => 'شارع 1', // Street 1
                        'building' => 'مبنى أ', // Building A
                        'floor' => 2,
                        'apartment_number' => '101',
                    ],
                    [
                        'street' => 'شارع 2', // Street 2
                        'building' => 'مبنى ب', // Building B

                    ],
                    // Add more addresses as needed for each city
                ];

                foreach ($addresses as $addressData) {
                    $addressData['city_id'] = $city->id;
                    $addressData['province_id'] = $province->id;
                    Address::create($addressData);
                }
            }
        }
    }
}
