<?php

namespace App\Erp\Database\Seeders;

use App\Erp\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ── DÖNEN VARLIKLAR (1xx) ─────────────────────────────────
            ['code' => '100', 'name' => 'Kasa',                          'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '102', 'name' => 'Bankalar',                      'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '108', 'name' => 'Diğer Hazır Değerler',          'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '120', 'name' => 'Alıcılar',                      'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '128', 'name' => 'Şüpheli Ticari Alacaklar',      'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '150', 'name' => 'İlk Madde ve Malzeme',          'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '153', 'name' => 'Ticari Mallar',                 'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '159', 'name' => 'Verilen Sipariş Avansları',     'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '191', 'name' => 'İndirilecek KDV',               'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '195', 'name' => 'İş Avansları',                  'type' => 'asset',     'normal_balance' => 'debit'],

            // ── DURAN VARLIKLAR (2xx) ─────────────────────────────────
            ['code' => '250', 'name' => 'Arazi ve Arsalar',              'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '252', 'name' => 'Binalar',                       'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '253', 'name' => 'Tesis, Makina ve Cihazlar',     'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '254', 'name' => 'Taşıtlar',                      'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '255', 'name' => 'Demirbaşlar',                   'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => '257', 'name' => 'Birikmiş Amortismanlar (-)',     'type' => 'asset',     'normal_balance' => 'credit'],

            // ── KISA VADELİ YABANCI KAYNAKLAR (3xx) ──────────────────
            ['code' => '300', 'name' => 'Banka Kredileri',               'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '320', 'name' => 'Satıcılar',                     'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '329', 'name' => 'Diğer Ticari Borçlar',         'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '335', 'name' => 'Personele Borçlar',             'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '360', 'name' => 'Ödenecek Vergiler ve Fonlar',   'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '361', 'name' => 'Ödenecek Sosyal Güvenlik Kes.', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '380', 'name' => 'Gelecek Aylara Ait Gelirler',  'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '391', 'name' => 'Hesaplanan KDV',                'type' => 'liability', 'normal_balance' => 'credit'],

            // ── UZUN VADELİ YABANCI KAYNAKLAR (4xx) ─────────────────
            ['code' => '400', 'name' => 'Banka Kredileri (Uzun)',        'type' => 'liability', 'normal_balance' => 'credit'],

            // ── ÖZKAYNAKLAR (5xx) ─────────────────────────────────────
            ['code' => '500', 'name' => 'Sermaye',                       'type' => 'equity',    'normal_balance' => 'credit'],
            ['code' => '570', 'name' => 'Geçmiş Yıl Kârları',           'type' => 'equity',    'normal_balance' => 'credit'],
            ['code' => '580', 'name' => 'Geçmiş Yıl Zararları (-)',      'type' => 'equity',    'normal_balance' => 'debit'],
            ['code' => '590', 'name' => 'Dönem Net Kârı',                'type' => 'equity',    'normal_balance' => 'credit'],

            // ── GELİR HESAPLARI (6xx) ────────────────────────────────
            ['code' => '600', 'name' => 'Yurt İçi Satışlar',            'type' => 'revenue',   'normal_balance' => 'credit'],
            ['code' => '601', 'name' => 'Yurt Dışı Satışlar',           'type' => 'revenue',   'normal_balance' => 'credit'],
            ['code' => '610', 'name' => 'Satıştan İadeler (-)',           'type' => 'revenue',   'normal_balance' => 'debit'],
            ['code' => '620', 'name' => 'Satılan Ticari Mallar Maliyeti','type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '649', 'name' => 'Diğer Olağan Gelirler',         'type' => 'revenue',   'normal_balance' => 'credit'],

            // ── GİDER HESAPLARI (7xx) ────────────────────────────────
            ['code' => '700', 'name' => 'Maliyet Muhasebesi Bağlantı',   'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '710', 'name' => 'Direkt İlk Madde Giderleri',    'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '720', 'name' => 'Direkt İşçilik Giderleri',      'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '730', 'name' => 'Genel Üretim Giderleri',        'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '740', 'name' => 'Hizmet Üretim Maliyeti',        'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '760', 'name' => 'Araştırma ve Geliştirme Gid.',  'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '770', 'name' => 'Genel Yönetim Giderleri',       'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => '780', 'name' => 'Finansman Giderleri',           'type' => 'expense',   'normal_balance' => 'debit'],
        ];

        foreach ($accounts as $data) {
            Account::firstOrCreate(['code' => $data['code']], array_merge($data, [
                'is_active'          => true,
                'allow_manual_entry' => true,
            ]));
        }
    }
}
