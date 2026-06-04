# SGK & Bordro Kılavuzu

## Yasal Parametreler (2026)

| Parametre | Değer |
|-----------|-------|
| Asgari Ücret (Brüt) | 22.104,97 ₺ |
| SGK İşçi Payı | %14 |
| SGK İşveren Payı | %15,5 |
| İşsizlik İşçi | %1 |
| İşsizlik İşveren | %2 |
| Damga Vergisi | %0,759 |
| AGİ (Bekar) | 500,00 ₺ |
| AGİ (Evli, eş çalışmıyor) | 750,00 ₺ |

## Hesaplama Sırası

```
Brüt Maaş
  - SGK İşçi Payı (%14)
  - İşsizlik İşçi (%1)
  = Gelir Vergisi Matrahı
  - Gelir Vergisi (dilimli)
  - Damga Vergisi (%0,759 × Brüt)
  + AGİ
  = NET ÖDEME
```

## Gelir Vergisi Dilimleri (2026)

| Dilim | Oran |
|-------|------|
| 0 – 110.000 ₺ | %15 |
| 110.001 – 230.000 ₺ | %20 |
| 230.001 – 580.000 ₺ | %27 |
| 580.001 – 3.000.000 ₺ | %35 |
| 3.000.001 ₺ ve üzeri | %40 |

> Gelir vergisi yıl başından biriken kümülatif matrah üzerinden hesaplanır (dilim geçişleri aylık güncellenir).

## Bordro Çalıştırma

1. `Bordro > Bordro Çalıştırma > Yeni` den ay/yıl seç
2. "Hesapla" butonuna tıkla → tüm aktif çalışanlar için bordo hesaplanır
3. "Onayla" → bordo onaylanır, ödeme kaydı oluşur
4. Çalışan başına PDF bordo indir

## SGK Bildirge Export

`Bordro Çalıştırma > Detay > SGK Bildirge İndir`

Çıktı: Her çalışan için TCKN, prime esas kazanç, gün sayısı, belge türü içeren CSV/XML.

## Parametre Güncelleme

Her yıl başında `Bordro Parametreleri` tablosuna yeni yıl için kayıt ekle:

```
Bordro > Ayarlar > Yeni Parametre Yılı
```

veya artisan:
```bash
make artisan CMD="erp:seed-payroll-parameters --year=2027"
```
