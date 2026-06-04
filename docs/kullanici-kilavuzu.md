# ERP Kullanıcı Kılavuzu

## Modüller

### HR — İnsan Kaynakları
- **Çalışanlar:** Ekle, düzenle, görüntüle, soft-delete. Profil sayfasında belgeler ve maaş geçmişi.
- **Departmanlar:** Hiyerarşik departman yapısı.
- **Pozisyonlar:** Departman bazlı pozisyon tanımları.
- **İzin Talepleri:** Çalışan talep oluşturur, yönetici onaylar/reddeder. Bakiye otomatik güncellenir.
- **Devam Çizelgesi:** Giriş/çıkış kayıtları, aylık özet.

### Stok (Inventory)
- **Ürünler:** SKU bazlı ürün kataloğu. Stok takip edilebilir veya hizmet olarak işaretlenebilir.
- **Depolar:** Çoklu depo desteği. Depo bazlı stok seviyeleri.
- **Stok Hareketleri:** Giriş, çıkış, transfer, düzeltme. Her hareket izlenir.

### Satın Alma (Procurement)
- **Tedarikçiler:** Tedarikçi kartı, kredi limiti, ödeme vadesi.
- **Satın Alma Siparişleri:** Oluştur → Onayla → Teslim Al. Teslimde stok otomatik artar.

### Satış
- **Müşteriler:** CRM entegrasyonuna hazır müşteri kartı.
- **Satış Siparişleri:** SO oluştur → Onayla (stok rezerve edilir) → Teslim Et (stok düşer).

### Finans
- **Faturalar:** Satış faturası oluştur, ödeme kaydet, PDF indir.
- **Ödemeler:** Kısmi ödeme, tam ödeme, gecikmiş fatura takibi.
- **Giderler:** Kategori bazlı gider takibi, makbuz ekleme.

### Bordro (Payroll)
- Aylık bordro çalıştır. Türkiye yasal hesaplamaları: SGK, gelir vergisi, damga vergisi, AGİ.
- Çalışan başına bordo PDF indir.
- SGK bildirge CSV export.

### Muhasebe
- Çift taraflı muhasebe. Her fatura/ödeme/bordro otomatik yevmiye fişi oluşturur.
- Mizan, bilanço, gelir tablosu, KDV raporu.

### Projeler
- Proje oluştur, task yönet (kanban), zaman girişi yap.

### Sabit Kıymetler
- Varlık kaydı, aylık amortisman (scheduler ile otomatik).

### Kasa & Banka
- Banka hesabı yönetimi, para transferi, çek/senet takibi.

### e-Fatura
- GİB akredite entegratör üzerinden e-Fatura / e-Arşiv gönderimi.
- Mükellefiyet kontrolü otomatik (e-Fatura mı e-Arşiv mi).

### Raporlar
- Gelir/gider grafiği, stok değeri özeti, HR headcount, yaşlandırma raporu.
- Excel ve PDF export.

---

## Veri Aktarımı

`Yönetim > Veri İçe Aktar` sayfasından:
1. Şablonu indir (Excel formatı)
2. Verilerini doldur
3. Dosyayı yükle

Desteklenen modüller: Çalışanlar, Ürünler, Müşteriler, Tedarikçiler, Stok Seviyeleri.

---

## Kullanıcı Rolleri

| Rol | Yetkiler |
|-----|---------|
| `erp_admin` | Tüm modüller — tam yetki |
| `erp_hr` | Çalışanlar, İzinler, Bordro |
| `erp_finance` | Faturalar, Ödemeler, Muhasebe |
| `erp_inventory` | Ürünler, Depolar, Stok Hareketleri |
| `erp_sales` | Müşteriler, Satış Siparişleri |
| `erp_viewer` | Tüm modüller — sadece görüntüleme |

Özel roller `Yönetim > Rol Yönetimi` üzerinden oluşturulabilir.
