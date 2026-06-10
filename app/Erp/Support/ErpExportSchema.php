<?php

namespace App\Erp\Support;

class ErpExportSchema
{
    /**
     * @return list<array{key: string, label: string, default: bool}>
     */
    public static function columns(string $module): array
    {
        return match ($module) {
            'customers' => [
                ['key' => 'name',               'label' => 'Ad',              'default' => true],
                ['key' => 'email',              'label' => 'E-posta',         'default' => true],
                ['key' => 'phone',              'label' => 'Telefon',         'default' => true],
                ['key' => 'tax_number',         'label' => 'Vergi No',        'default' => false],
                ['key' => 'contact_person',     'label' => 'İletişim Kişisi', 'default' => false],
                ['key' => 'address',            'label' => 'Adres',           'default' => false],
                ['key' => 'payment_terms_days', 'label' => 'Ödeme Vadesi',    'default' => true],
                ['key' => 'credit_limit',       'label' => 'Kredi Limiti',    'default' => true],
                ['key' => 'status',             'label' => 'Durum',           'default' => true],
            ],
            'suppliers' => [
                ['key' => 'name',               'label' => 'Ad',              'default' => true],
                ['key' => 'email',              'label' => 'E-posta',         'default' => true],
                ['key' => 'phone',              'label' => 'Telefon',         'default' => true],
                ['key' => 'tax_number',         'label' => 'Vergi No',        'default' => false],
                ['key' => 'contact_person',     'label' => 'İletişim Kişisi', 'default' => false],
                ['key' => 'payment_terms_days', 'label' => 'Ödeme Vadesi',    'default' => true],
                ['key' => 'status',             'label' => 'Durum',           'default' => true],
            ],
            'products' => [
                ['key' => 'sku',            'label' => 'SKU',           'default' => true],
                ['key' => 'name',           'label' => 'Ad',            'default' => true],
                ['key' => 'category',       'label' => 'Kategori',      'default' => true],
                ['key' => 'unit',           'label' => 'Birim',         'default' => true],
                ['key' => 'purchase_price', 'label' => 'Alış Fiyatı',  'default' => true],
                ['key' => 'sale_price',     'label' => 'Satış Fiyatı', 'default' => true],
                ['key' => 'tax_rate',       'label' => 'KDV %',        'default' => false],
                ['key' => 'reorder_point',  'label' => 'Reorder Noktası', 'default' => false],
                ['key' => 'is_active',      'label' => 'Durum',         'default' => true],
            ],
            'employees' => [
                ['key' => 'employee_number',  'label' => 'Sicil No',      'default' => true],
                ['key' => 'first_name',       'label' => 'Ad',            'default' => true],
                ['key' => 'last_name',        'label' => 'Soyad',         'default' => true],
                ['key' => 'email',            'label' => 'E-posta',       'default' => true],
                ['key' => 'department',       'label' => 'Departman',     'default' => true],
                ['key' => 'position',         'label' => 'Pozisyon',      'default' => true],
                ['key' => 'hire_date',        'label' => 'İşe Giriş',    'default' => true],
                ['key' => 'employment_type',  'label' => 'Çalışma Tipi', 'default' => false],
                ['key' => 'status',           'label' => 'Durum',         'default' => true],
            ],
            'assets' => [
                ['key' => 'code',             'label' => 'Kod',              'default' => true],
                ['key' => 'name',             'label' => 'Ad',               'default' => true],
                ['key' => 'category',         'label' => 'Kategori',         'default' => true],
                ['key' => 'purchase_date',    'label' => 'Satın Alma Tarihi','default' => true],
                ['key' => 'purchase_value',   'label' => 'Alış Değeri',      'default' => true],
                ['key' => 'current_value',    'label' => 'Güncel Değer',     'default' => true],
                ['key' => 'status',           'label' => 'Durum',            'default' => true],
            ],
            'expenses' => [
                ['key' => 'title',       'label' => 'Başlık',     'default' => true],
                ['key' => 'category',    'label' => 'Kategori',   'default' => true],
                ['key' => 'amount',      'label' => 'Tutar',      'default' => true],
                ['key' => 'expense_date','label' => 'Tarih',      'default' => true],
                ['key' => 'employee',    'label' => 'Çalışan',    'default' => true],
                ['key' => 'status',      'label' => 'Durum',      'default' => true],
            ],
            'sales-orders' => [
                ['key' => 'order_number', 'label' => 'Sipariş No',  'default' => true],
                ['key' => 'customer',     'label' => 'Müşteri',     'default' => true],
                ['key' => 'order_date',   'label' => 'Tarih',       'default' => true],
                ['key' => 'total_amount', 'label' => 'Toplam',      'default' => true],
                ['key' => 'status',       'label' => 'Durum',       'default' => true],
            ],
            'purchase-orders' => [
                ['key' => 'po_number',    'label' => 'Sipariş No',  'default' => true],
                ['key' => 'supplier',     'label' => 'Tedarikçi',   'default' => true],
                ['key' => 'order_date',   'label' => 'Tarih',       'default' => true],
                ['key' => 'total_amount', 'label' => 'Toplam',      'default' => true],
                ['key' => 'status',       'label' => 'Durum',       'default' => true],
            ],
            'invoices' => [
                ['key' => 'invoice_number', 'label' => 'Fatura No',  'default' => true],
                ['key' => 'customer',       'label' => 'Müşteri',    'default' => true],
                ['key' => 'invoice_date',   'label' => 'Tarih',      'default' => true],
                ['key' => 'total_amount',   'label' => 'Toplam',     'default' => true],
                ['key' => 'status',         'label' => 'Durum',      'default' => true],
            ],
            'projects' => [
                ['key' => 'name',       'label' => 'Proje Adı',  'default' => true],
                ['key' => 'customer',   'label' => 'Müşteri',    'default' => true],
                ['key' => 'start_date', 'label' => 'Başlangıç', 'default' => true],
                ['key' => 'end_date',   'label' => 'Bitiş',     'default' => true],
                ['key' => 'budget',     'label' => 'Bütçe',     'default' => true],
                ['key' => 'status',     'label' => 'Durum',      'default' => true],
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function formats(string $module): array
    {
        return ['excel'];
    }
}
