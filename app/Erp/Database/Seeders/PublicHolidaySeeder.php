<?php

namespace App\Erp\Database\Seeders;

use App\Erp\Models\LeaveType;
use App\Erp\Models\PublicHoliday;
use Illuminate\Database\Seeder;

class PublicHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['name' => 'Yılbaşı',                        'date' => '2026-01-01'],
            ['name' => 'Ulusal Egemenlik ve Çocuk Bayramı','date' => '2026-04-23'],
            ['name' => 'Emek ve Dayanışma Günü',          'date' => '2026-05-01'],
            ['name' => 'Atatürk\'ü Anma, Gençlik ve Spor Bayramı','date' => '2026-05-19'],
            ['name' => 'Ramazan Bayramı 1. Gün',          'date' => '2026-03-30'],
            ['name' => 'Ramazan Bayramı 2. Gün',          'date' => '2026-03-31'],
            ['name' => 'Ramazan Bayramı 3. Gün',          'date' => '2026-04-01'],
            ['name' => 'Kurban Bayramı 1. Gün',           'date' => '2026-06-06'],
            ['name' => 'Kurban Bayramı 2. Gün',           'date' => '2026-06-07'],
            ['name' => 'Kurban Bayramı 3. Gün',           'date' => '2026-06-08'],
            ['name' => 'Kurban Bayramı 4. Gün',           'date' => '2026-06-09'],
            ['name' => 'Demokrasi ve Millî Birlik Günü',  'date' => '2026-07-15'],
            ['name' => 'Zafer Bayramı',                   'date' => '2026-08-30'],
            ['name' => 'Cumhuriyet Bayramı',              'date' => '2026-10-29'],
        ];

        foreach ($holidays as $h) {
            PublicHoliday::firstOrCreate(['date' => $h['date']], array_merge($h, ['is_recurring' => false]));
        }

        // Varsayılan izin tipleri
        $leaveTypes = [
            ['name' => 'Yıllık İzin',    'days_per_year' => 0,  'requires_approval' => true,  'is_paid' => true,  'carry_over' => true,  'max_carry_over_days' => 10],
            ['name' => 'Hastalık İzni',  'days_per_year' => 10, 'requires_approval' => false, 'is_paid' => true,  'carry_over' => false, 'max_carry_over_days' => 0],
            ['name' => 'Mazeret İzni',   'days_per_year' => 5,  'requires_approval' => true,  'is_paid' => true,  'carry_over' => false, 'max_carry_over_days' => 0],
            ['name' => 'Ücretsiz İzin',  'days_per_year' => 0,  'requires_approval' => true,  'is_paid' => false, 'carry_over' => false, 'max_carry_over_days' => 0],
            ['name' => 'Doğum İzni',     'days_per_year' => 0,  'requires_approval' => true,  'is_paid' => true,  'carry_over' => false, 'max_carry_over_days' => 0],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::firstOrCreate(['name' => $lt['name']], array_merge($lt, ['is_active' => true]));
        }
    }
}
