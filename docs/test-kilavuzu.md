# ERP Manuel Test Kılavuzu

> Her adım sırayla yapılmalı — bazı modüller önceki modüllerin verilerini kullanır.
> Yeşil ✓ = geçti, kırmızı ✗ = not al, sonra düzelt.

---

## 0. Hazırlık

```bash
make fresh          # DB sıfırla + seed uygula
make up             # Docker çalışıyor mu kontrol
```

Tarayıcı: `http://localhost:8082/admin/login`

---

## BÖLÜM A — Admin Kullanıcısıyla Tam Akış Testi

Giriş: `admin@erp.test` / `password`

---

### A1 — Dashboard

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/dashboard` aç | Sayfa hata vermeden açılıyor |
| 2 | Widget'ları gözlemle | Gelir, aktif çalışan, açık PO, düşük stok sayıları görünüyor |

---

### A2 — HR: Departman → Pozisyon → Çalışan

**Departman**
| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/departments` → Liste açılıyor | Mevcut departmanlar görünüyor |
| 2 | `Yeni Departman` → "Yazılım" → kaydet | Listede görünüyor |
| 3 | Düzenle → isim değiştir → kaydet | Değişiklik yansıdı |

**Pozisyon**
| # | Adım | Beklenen |
|---|------|---------|
| 4 | `/admin/erp/positions` → `Yeni Pozisyon` | Form açılıyor |
| 5 | İsim: "Backend Developer", departman: "Yazılım", seviye: "mid" → kaydet | Listede görünüyor |

**Çalışan**
| # | Adım | Beklenen |
|---|------|---------|
| 6 | `/admin/erp/employees` → `Yeni Çalışan` | Form açılıyor |
| 7 | Ad: "Test", Soyad: "Kullanıcı", email: `test@erp.test`, işe giriş: bugün, tür: tam zamanlı → kaydet | `EMP-` prefix'li numara atandı, listede görünüyor |
| 8 | Çalışan detayına gir → `Maaş Ekle` → 30000 → kaydet | Maaş geçmişinde görünüyor |
| 9 | Çalışanı düzenle → kaydet | Değişiklik yansıdı |
| 10 | Başka bir çalışana git → Sil → soft delete | Listeden kalktı, `?trashed=1` ile görünüyor |

---

### A3 — HR: İzin Talepleri & Devam

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/leave-requests` → Liste açılıyor | |
| 2 | `Yeni Talep` → Çalışan seç, izin türü seç, tarih aralığı seç → kaydet | "Beklemede" statüsünde görünüyor |
| 3 | Talebe gir → `Onayla` | Durum "Onaylandı" oldu |
| 4 | Yeni talep oluştur → `Reddet` | Durum "Reddedildi" oldu |
| 5 | `/admin/erp/attendance` → `Giriş Kaydet` → bir çalışan seç, tarih gir | Kayıt listede görünüyor |

---

### A4 — Stok: Ürün → Depo → Stok Hareketi

**Ürün**
| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/products` → `Yeni Ürün` | Form açılıyor |
| 2 | SKU: `TEST-001`, ad: "Test Ürünü", birim: pcs, alış fiyatı: 100, satış: 150, KDV: 20 → kaydet | Listede görünüyor, stok 0 |
| 3 | Ürün detayına gir | Depo bazlı stok seviyeleri görünüyor |

**Depo**
| # | Adım | Beklenen |
|---|------|---------|
| 4 | `/admin/erp/warehouses` → `Yeni Depo` → kod: "TEST", isim: "Test Depo" → kaydet | Listede görünüyor |

**Stok Hareketi**
| # | Adım | Beklenen |
|---|------|---------|
| 5 | `/admin/erp/stock-movements` → `Yeni Hareket` | Form açılıyor |
| 6 | Tür: **Giriş**, ürün: TEST-001, depo: Test Depo, miktar: 50 → kaydet | Hareket listede görünüyor |
| 7 | TEST-001 ürün detayına dön | Stok seviyesi 50 gösteriyor |
| 8 | Yeni hareket → Tür: **Çıkış**, miktar: 10 → kaydet | Stok 40'a düştü |
| 9 | Yeni hareket → Tür: **Çıkış**, miktar: 9999 → kaydet | **Hata** vermeli (yetersiz stok) veya negatife düşmemeli |

---

### A5 — Satın Alma: Tedarikçi → PO → Teslim

**Tedarikçi**
| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/suppliers` → `Yeni Tedarikçi` → "ABC Tedarik" → kaydet | Listede görünüyor |

**Satın Alma Siparişi**
| # | Adım | Beklenen |
|---|------|---------|
| 2 | `/admin/erp/purchase-orders` → `Yeni PO` | Form açılıyor |
| 3 | Tedarikçi: ABC Tedarik, depo: seç, ürün: TEST-001, miktar: 20, fiyat: 100 → kaydet | `PO-` numarası atandı, durum: taslak |
| 4 | PO detayına gir → `Onayla` | Durum "Gönderildi" oldu |
| 5 | `Teslim Al` butonuna bas | Teslim formu açılıyor |
| 6 | Alınan miktar: 20 → kaydet | Stok TEST-001 için 20 arttı (önceki 40 + 20 = 60) |

---

### A6 — Satış: Müşteri → Satış Siparişi → Akış

**Müşteri**
| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/customers` → `Yeni Müşteri` → "XYZ Ltd." → kaydet | Listede görünüyor |

**Satış Siparişi (tam akış)**
| # | Adım | Beklenen |
|---|------|---------|
| 2 | `/admin/erp/sales-orders` → `Yeni SO` | Form açılıyor |
| 3 | Müşteri: XYZ Ltd., depo: seç, ürün: TEST-001, miktar: 5, fiyat: 150 → kaydet | `SO-` numarası atandı, durum: taslak |
| 4 | SO detayı → `Onayla` | Durum "Onaylandı", TEST-001 reserved_quantity 5 arttı |
| 5 | `Teslim Et` | Durum "Teslim Edildi", stok 5 düştü |
| 6 | `Fatura Oluştur` | Yeni fatura oluştu, fatura sayfasına yönlendirildi |

---

### A7 — Finans: Fatura → Ödeme → PDF

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/invoices` → A6'da oluşan fatura listede görünüyor | |
| 2 | `Yeni Fatura` → müşteri: XYZ, ürün ekle, satır toplamı hesaplandı → kaydet | Durum: taslak |
| 3 | Fatura detayı → `Onayla/Gönder` | Durum "Gönderildi", **otomatik yevmiye fişi oluştu** |
| 4 | `Ödeme Ekle` → tutar: fatura tutarının yarısı, yöntem: banka → kaydet | Durum "Kısmi Ödendi", paid_amount güncellendi |
| 5 | Tekrar ödeme ekle → kalan tutar → kaydet | Durum "Ödendi" |
| 6 | `PDF İndir` | PDF tarayıcıda açılıyor, içerik doğru |
| 7 | `/admin/erp/expenses` → `Yeni Gider` → başlık, kategori, tutar → kaydet | Listede görünüyor |

---

### A8 — Muhasebe

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/accounts` | Hesap planı listesi açılıyor (100, 102, 120... kodlar görünüyor) |
| 2 | Bir hesaba tıkla (ör. 120 Alıcılar) | Hesap defteri açılıyor, A7'deki fatura hareketi görünüyor |
| 3 | `/admin/erp/journal-entries` | Fiş listesi, A7'de otomatik oluşan fişler görünüyor |
| 4 | `Yeni Manuel Fiş` → 2 satır ekle (102 borç 1000, 120 alacak 1000) → kaydet | Kaydedildi |
| 5 | Dengesiz fiş dene → 102 borç 1000, 120 alacak 900 → kaydet | **Hata** vermeli (borç ≠ alacak) |
| 6 | `/admin/erp/reports/trial-balance` | Mizan yüklüyor, borç = alacak eşit |
| 7 | `/admin/erp/reports/balance-sheet` | Bilanço yüklüyor |
| 8 | `/admin/erp/reports/income-statement` | Gelir tablosu yüklüyor |
| 9 | `/admin/erp/reports/tax-report` | KDV raporu yüklüyor |

---

### A9 — Bordro

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/payroll-runs` → `Yeni Bordro` | Form açılıyor |
| 2 | Yıl: 2026, Ay: Haziran → `Hesapla` | Tüm aktif çalışanlar için bordro hesaplandı, durum: taslak |
| 3 | Bordro detayı → çalışan listesi | Her çalışan için brüt, kesintiler, net görünüyor |
| 4 | `Onayla` | Durum "Onaylandı" |
| 5 | Bir çalışanın borcosuna tıkla | Bordo detayı açılıyor (SGK, vergi dilimleri dahil) |
| 6 | `PDF İndir` | Bordo PDF iniyor |
| 7 | `SGK Bildirge İndir` | CSV/XML dosyası iniyor |
| 8 | `/admin/erp/export/payroll/{id}` | Excel dosyası iniyor |

---

### A10 — Kasa & Banka

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/bank-accounts` → `Yeni Hesap` → "İş Bankası TL", banka: "İş Bankası", bakiye: 50000 → kaydet | Listede görünüyor |
| 2 | Hesap detayı → `İşlem Ekle` → tür: Yatırma, tutar: 10000 → kaydet | Bakiye 60000'e yükseldi |
| 3 | İkinci banka hesabı oluştur | |
| 4 | İlk hesap → `Transfer` → hedef: 2. hesap, tutar: 5000 → kaydet | İlk hesap 5000 düştü, 2. hesap 5000 arttı |
| 5 | `/admin/erp/checks` → `Yeni Çek` → tür: Alınan, tutar: 10000, vade: 30 gün sonra → kaydet | Portföyde görünüyor |
| 6 | Çek → durum güncelle → "Tahsil Edildi" | Durum değişti |

---

### A11 — Projeler

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/projects` → `Yeni Proje` → kod: "PRJ-01", müşteri: XYZ, bütçe: 100000 → kaydet | Listede görünüyor |
| 2 | Proje detayı → kanban açılıyor | Sütunlar: Yapılacak, Devam Eden, İnceleme, Tamamlandı |
| 3 | `Yeni Task` → isim: "API geliştirme", öncelik: yüksek → kaydet | Kanban'da görünüyor |
| 4 | Task sürükle → "Devam Eden" sütununa bırak | Durum güncellendi |
| 5 | `Zaman Girişi` → çalışan: seç, saat: 3, açıklama → kaydet | Proje toplam saati güncellendi |

---

### A12 — Sabit Kıymetler

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/assets` → `Yeni Varlık` | Form açılıyor |
| 2 | İsim: "Sunucu #1", kategori: seç, alış tarihi: 1 yıl önce, alış fiyatı: 50000 → kaydet | Listede görünüyor |
| 3 | Varlık detayı → `Amortisman Hesapla` | Bu aya ait amortisman kaydı oluştu, defter değeri düştü |

---

### A13 — Üretim (BOM & İş Emri)

| # | Adım | Beklenen |
|---|------|---------|
| 1 | İlk: "Mamul" adında yeni ürün oluştur (stok takip: evet) | |
| 2 | İkinci: "Hammadde" adında ürün oluştur, stok hareketi ile 100 adet gir | |
| 3 | `/admin/erp/boms` → `Yeni BOM` → mamul ürünü seç, bileşen: Hammadde, miktar: 3 → kaydet | BOM oluştu |
| 4 | `/admin/erp/work-orders` → `Yeni İş Emri` → BOM seç, miktar: 5 → kaydet | `WO-` numarası atandı, durum: taslak |
| 5 | `Serbest Bırak` | Durum "Serbest", Hammadde rezervasyonu 15 arttı (3×5) |
| 6 | `Tamamla` → üretilen miktar: 5 | Hammadde stoku 15 düştü, Mamul stoku 5 arttı |

---

### A14 — Raporlar & Export

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/reports` | Genel rapor sayfası açılıyor |
| 2 | `/admin/erp/reports/revenue` | Gelir/gider grafiği yüklüyor |
| 3 | `/admin/erp/reports/inventory` | Stok değeri ve düşük stok listesi |
| 4 | `/admin/erp/reports/hr` | Departman headcount |
| 5 | `/admin/erp/reports/aging` | Alacak yaşlandırma tablosu |
| 6 | `/admin/erp/export/employees` | Excel dosyası iniyor, içerik doğru |
| 7 | `/admin/erp/export/invoices` | Excel iniyor |
| 8 | `/admin/erp/export/products` | Excel iniyor |
| 9 | `/admin/erp/export/trial-balance` | Mizan Excel iniyor |

---

### A15 — Veri Aktarım (Import)

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/import` | Import sayfası açılıyor |
| 2 | "Çalışanlar" → `Şablonu İndir` | `calisanlar-sablon.xlsx` iniyor |
| 3 | Şablona 2 satır doldur (farklı email), yükle | "2 kayıt içe aktarıldı" mesajı |
| 4 | "Ürünler" → şablon indir, doldur, yükle | Ürünler içe aktarıldı |
| 5 | "Stok Seviyeleri" → şablon indir, doldur (mevcut SKU + depo kodu), yükle | Stok seviyeleri güncellendi |
| 6 | Hatalı dosya yükle (eksik alan) → | Hangi satırda hata var gösteriyor |

---

### A16 — Çoklu Para Birimi

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/currencies` | USD, EUR, GBP listede görünüyor |
| 2 | `Manuel Kur Ekle` → USD/TRY: 32.50, bugün | Kayıt oluştu |
| 3 | USD cinsinden fatura oluştur (invoice currency: USD) | Kur gösteriyor |
| 4 | `TCMB'den Güncelle` (internet bağlantısı gerekir) | Başarılı mesajı veya başarısız olursa uyarı |

---

### A17 — API Token

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/api-tokens` → `Yeni Token` → isim: "Test Token" → oluştur | Token bir kez gösteriliyor (not al) |
| 2 | Terminalde: `curl -H "Authorization: Bearer {token}" http://localhost:8082/api/erp/employees` | JSON yanıt geliyor |
| 3 | Geçersiz token ile dene: `Authorization: Bearer yanlis123` | `{"message":"Unauthenticated."}` — 401 |
| 4 | Token sil | Listeden kalktı, eski token ile istek 401 |

---

### A18 — Kurulum Sihirbazı

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/setup` | Adım 1 formu açılıyor |
| 2 | Şirket adı, email, telefon gir → `Devam Et` | Adım 2'ye geçti |
| 3 | Para birimi: TRY, KDV: 20, fatura prefix: INV → `Kurulumu Tamamla` | Dashboard'a yönlendirildi |
| 4 | `/admin/erp/setup` tekrar aç | Dashboard'a yönlendiriyor (setup tamamlandı) |

---

## BÖLÜM B — Rol Bazlı Erişim Testi

### B1 — Viewer Hesabı Oluştur

1. `/admin/erp/roles-users` → `Kullanıcıya Rol Ata`
2. Yeni kullanıcı oluşturmak için: Admin panelin `/admin/users` sayfasını kullan
3. Oluşturulan kullanıcıya `erp_viewer` rolü ver
4. O hesapla giriş yap

### B2 — Viewer Kısıtları (erp_viewer)

Viewer ile giriş yapıldığında:

| # | URL | Beklenen |
|---|-----|---------|
| 1 | `/admin/erp/dashboard` | ✓ Açılıyor |
| 2 | `/admin/erp/employees` | ✓ Liste görünüyor |
| 3 | `/admin/erp/employees/create` | ✗ **403** |
| 4 | `/admin/erp/invoices` | ✓ Liste görünüyor |
| 5 | `/admin/erp/invoices/create` | ✗ **403** |
| 6 | `/admin/erp/products/create` | ✗ **403** |
| 7 | `/admin/erp/stock-movements/create` | ✗ **403** |
| 8 | `/admin/erp/purchase-orders/create` | ✗ **403** |
| 9 | `/admin/erp/roles` | ✗ **403** |
| 10 | `/admin/erp/payroll-runs/create` | ✗ **403** |
| 11 | POST ile doğrudan form submit | ✗ **403** |

### B3 — HR Rolü (erp_hr)

`erp_hr` rolü ver, aynı kısıt testini uygula:

| # | URL | Beklenen |
|---|-----|---------|
| 1 | `/admin/erp/employees/create` | ✓ Açılıyor |
| 2 | `/admin/erp/invoices/create` | ✗ **403** |
| 3 | `/admin/erp/purchase-orders/create` | ✗ **403** |
| 4 | `/admin/erp/payroll-runs` | ✓ Açılıyor |

### B4 — Finance Rolü (erp_finance)

| # | URL | Beklenen |
|---|-----|---------|
| 1 | `/admin/erp/invoices/create` | ✓ Açılıyor |
| 2 | `/admin/erp/employees/create` | ✗ **403** |
| 3 | `/admin/erp/payroll-runs/create` | ✗ **403** |
| 4 | `/admin/erp/accounts` | ✓ Açılıyor |

### B5 — Özel Rol Oluşturma

| # | Adım | Beklenen |
|---|------|---------|
| 1 | `/admin/erp/roles` → `Yeni Rol` → isim: "erp_satis" | Form açılıyor |
| 2 | Sadece satış izinlerini seç (customers.*, sales_orders.*) → kaydet | Rol oluştu |
| 3 | Bir kullanıcıya bu rolü ver | |
| 4 | O kullanıcıyla giriş yap → `/admin/erp/customers` | ✓ Açılıyor |
| 5 | `/admin/erp/employees` | ✗ **403** |

---

## BÖLÜM C — Kritik İş Akışı Entegrasyon Testleri

Bu testler birden fazla modülün birlikte çalışmasını doğrular.

### C1 — PO → Stok Entegrasyonu

1. Ürün stok seviyesini not et (ör. 40)
2. Bu ürün için PO oluştur, onayla, teslim al (20 adet)
3. Ürün stok seviyesi **60** olmalı

### C2 — SO → Stok → Fatura Zinciri

1. Stok seviyesini not et (ör. 60)
2. SO oluştur (5 adet) → **onayla** → reserved_quantity 5 arttı, kullanılabilir stok 55
3. SO **teslim et** → stok 55'e düştü, reserved 0
4. SO'dan **fatura oluştur** → fatura mevcut müşteriyle oluştu
5. Faturayı **onayla** → yevmiye fişi oluştu (Muhasebe'de doğrula)
6. **Ödeme ekle** → ödeme fişi oluştu

### C3 — Bordro → Muhasebe Entegrasyonu

1. Bordro çalıştır ve onayla (A9)
2. `/admin/erp/journal-entries` → bordro için otomatik yevmiye fişi var mı?
3. Fiş: "770 Genel Yönetim Giderleri (Borç) / 335 Personele Borçlar (Alacak)"

### C4 — Amortisman → Muhasebe Entegrasyonu

1. Sabit kıymet oluştur (A12)
2. `Amortisman Hesapla` tetikle
3. Yevmiye fişleri listesinde "Amortisman" fişi görünüyor

### C5 — Düşük Stok Bildirimi

1. Bir ürünün `reorder_point`: 100 yap
2. Stok seviyesini 50'ye düşür (çıkış hareketi)
3. `make artisan CMD="schedule:run"` çalıştır
4. Bildirimler ikonunda veya logda düşük stok uyarısı

---

## BÖLÜM D — Doğrulama (Validation) Testleri

Formların hatalı girişi reddettiğini doğrula:

| # | Senaryo | Beklenen |
|---|---------|---------|
| 1 | Çalışan oluştur → email boş bırak | "Email zorunlu" hatası |
| 2 | Çalışan oluştur → aynı email tekrar gir | "Email zaten kayıtlı" hatası |
| 3 | Fatura oluştur → kalem eklemeden kaydet | Hata |
| 4 | Stok çıkışı → miktarı negatif gir | Hata |
| 5 | Manuel yevmiye fişi → borç ≠ alacak | "Denge bozuk" hatası |
| 6 | PO oluştur → ürün seçmeden kaydet | Hata |
| 7 | Import → 5 MB üzerinde dosya yükle | "Dosya çok büyük" hatası |
| 8 | Import → Excel yerine .txt yükle | "Geçersiz format" hatası |

---

## BÖLÜM E — Hız & Görsel Kontrol

| # | Kontrol | Beklenen |
|---|---------|---------|
| 1 | Tüm liste sayfaları | Sayfalama çalışıyor (20'den fazla kayıtta) |
| 2 | Sidebar navigasyonu | Tüm modül linkleri görünüyor, aktif sayfa vurgulanıyor |
| 3 | Mobil genişlikte (F12 → mobil mod) | Tablo ve formlar düzgün görünüyor |
| 4 | Filtre kullan → URL'e bak | Query string'de filtre parametreleri var |
| 5 | Filtrele → sayfa 2'ye git | Filtre kaybolmuyor |
| 6 | Log hataları | `docker compose logs app --tail=100` → kırmızı hata yok |

---

## Hata Bulunca Ne Yapmalı

```bash
# Laravel log
docker compose exec app tail -100 storage/logs/laravel.log

# PHP hataları
docker compose logs app --tail=50

# DB sorguları (test sırasında)
docker compose exec app php artisan tinker
# >>> DB::listen(fn($q) => dump($q->sql))
```

Not al: **URL + ne yaptın + ne oldu + hata mesajı**. Bu 4 bilgi olmadan hata ayıklamak zor.
