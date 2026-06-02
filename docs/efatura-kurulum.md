# e-Fatura & e-Arşiv Kurulum Kılavuzu

## Entegratör Seçimi

Sistem sürücü tabanlı çalışır. Desteklenen entegratörler:
- `uyumsoft` — Uyumsoft API
- `logo` — Logo e-Fatura API
- `null` — Test modu (API çağrısı yapmaz)

## .env Yapılandırması

```env
ERP_EFATURA_ENABLED=true
ERP_EFATURA_DRIVER=uyumsoft
ERP_EFATURA_API_URL=https://api.entegrator.com
ERP_EFATURA_USERNAME=your-username
ERP_EFATURA_PASSWORD=your-password
ERP_EFATURA_VKN=1234567890   # Şirket VKN
ERP_EFATURA_TEST=true        # Test ortamında başla, canlıya geçince false yap
```

## Akış

1. Fatura oluşturulur → `status: draft`
2. "Onayla" → müşteri VKN sorgulanır
   - Mükelefse: `efatura_type = efatura`
   - Değilse: `efatura_type = earshiv`
3. `SendEFaturaJob` kuyruğa girer → entegratöre gönderilir
4. `CheckEFaturaStatusJob` her 5 dakikada GİB yanıtını kontrol eder
5. PDF entegratörden çekilir → storage'a kaydedilir

## Queue Worker Zorunluluğu

e-Fatura gönderimi kuyruk tabanlıdır. Worker çalışmıyorsa faturalar gönderilemez:

```bash
# Geliştirme
make artisan CMD="queue:work"

# Üretim (Supervisor ile)
php artisan queue:work redis --queue=default --sleep=3 --tries=3
```

## GİB Test Ortamı

Canlıya geçmeden önce GİB test ortamında en az 5 fatura gönder ve `accepted` durumunu doğrula. `ERP_EFATURA_TEST=true` iken test entegratör URL'si kullanılır.

## Sorun Giderme

- `efatura_status = rejected` → Fatura XML formatı hatalı, Log'ları kontrol et
- Job çalışmıyor → `php artisan queue:failed` listesine bak
- UUID boş → Entegratör bağlantısı yok, API URL ve credentials kontrol et
