# API Kullanım Rehberi

## Genel Bilgiler

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
Tüm API istekleri için Bearer token gereklidir:
```
Authorization: Bearer {token}
```

### Dil Seçimi
İsteklerde dil belirtmek için:
```
Accept-Language: tr (veya en)
```

---

## API Controller'ları ve Endpoint'leri

### 1. AuthController
**Kimlik doğrulama işlemleri**

- `POST /auth/register` - Yeni kullanıcı kaydı
- `POST /auth/login` - Giriş yapma ve token alma
- `POST /auth/logout` - Çıkış yapma
- `GET /auth/me` - Oturum açmış kullanıcı bilgilerini getir
- `POST /auth/refresh` - Token yenileme

---

### 2. ProfileController
**Kullanıcı profil yönetimi**

- `GET /profile` - Profil bilgilerini getir
- `PUT /profile` - Profil bilgilerini güncelle
- `PUT /profile/password` - Şifre değiştir

---

### 3. UserSettingController
**Kullanıcı ayarları**

- `GET /settings` - Kullanıcı ayarlarını getir
- `PUT /settings` - Kullanıcı ayarlarını güncelle
- `PUT /settings/language` - Dil tercihini değiştir

---

### 4. LanguageController
**Sistem dilleri**

- `GET /languages` - Aktif dilleri listele

---

### 5. MarketplaceController
**Pazaryeri platformları**

- `GET /marketplaces` - Tüm pazaryerlerini listele
- `GET /marketplaces/{id}` - Belirli bir pazaryeri detayı

---

### 6. MarketplaceCredentialController
**Pazaryeri API bilgileri yönetimi**

- `GET /marketplace-credentials` - Kullanıcının pazaryeri bağlantılarını listele
- `POST /marketplace-credentials` - Yeni pazaryeri bağlantısı ekle
- `GET /marketplace-credentials/{id}` - Belirli bir bağlantı detayı
- `PUT /marketplace-credentials/{id}` - Bağlantı bilgilerini güncelle
- `DELETE /marketplace-credentials/{id}` - Bağlantıyı sil
- `POST /marketplace-credentials/{id}/test` - Bağlantıyı test et

---

### 7. MarketplaceDataController
**Pazaryeri veri senkronizasyonu**

- `POST /marketplace-data/sync-categories` - Kategorileri senkronize et
- `POST /marketplace-data/sync-brands` - Markaları senkronize et

---

### 8. ProductController
**Ürün yönetimi (birleşik)**

- `GET /products` - Tüm ürünleri listele
- `POST /products` - Yeni ürün oluştur
- `GET /products/{id}` - Ürün detayı
- `PUT /products/{id}` - Ürün güncelle
- `DELETE /products/{id}` - Ürün sil
- `POST /products/bulk` - Toplu ürün oluştur

---

### 9. MarketplaceProductController
**Pazaryeri ürün işlemleri**

- `GET /marketplace-products` - Pazaryerine özel ürünleri listele
- `POST /marketplace-products/push` - Ürünü pazaryerine gönder
- `POST /marketplace-products/pull` - Ürünü pazaryerinden çek
- `POST /marketplace-products/sync` - Ürünleri senkronize et
- `PUT /marketplace-products/{id}` - Pazaryeri ürün bilgilerini güncelle
- `DELETE /marketplace-products/{id}` - Pazaryerindeki ürünü sil (unpublish)
- `POST /marketplace-products/{id}/update-stock` - Stok güncelle
- `POST /marketplace-products/{id}/update-price` - Fiyat güncelle

---

### 10. MarketplaceOrderController
**Sipariş yönetimi**

- `GET /marketplace-orders` - Siparişleri listele
- `POST /marketplace-orders/fetch` - Pazaryerinden siparişleri çek
- `GET /marketplace-orders/{id}` - Sipariş detayı
- `POST /marketplace-orders/{id}/update-status` - Sipariş durumunu güncelle
- `POST /marketplace-orders/{id}/cargo-info` - Kargo bilgilerini güncelle

---

### 11. MarketplaceClaimController
**İade/İptal yönetimi**

- `GET /marketplace-claims` - İade/iptal taleplerini listele
- `POST /marketplace-claims/fetch` - Pazaryerinden talepleri çek
- `GET /marketplace-claims/{id}` - Talep detayı
- `POST /marketplace-claims/{id}/approve` - Talebi onayla
- `POST /marketplace-claims/{id}/reject` - Talebi reddet
- `POST /marketplace-claims/{id}/complete` - Talebi tamamla

---

### 12. MarketplaceQuestionController
**Soru & Cevap yönetimi**

- `GET /marketplace-questions` - Soruları listele
- `POST /marketplace-questions/fetch` - Pazaryerinden soruları çek
- `GET /marketplace-questions/{id}` - Soru detayı
- `POST /marketplace-questions/{id}/answer` - Soruyu cevapla

---

### 13. MarketplaceFinancialController
**Finansal raporlar**

- `GET /marketplace-financials/settlements` - Ödemeleri listele
- `POST /marketplace-financials/fetch-settlements` - Ödemeleri çek
- `GET /marketplace-financials/invoices` - Kargo faturalarını listele
- `POST /marketplace-financials/fetch-invoices` - Kargo faturalarını çek
- `GET /marketplace-financials/other` - Diğer finansal işlemleri listele
- `POST /marketplace-financials/fetch-other` - Diğer işlemleri çek

---

### 14. ProfitController
**Kâr analizi**

- `GET /profit/analysis` - Genel kâr analizi
- `GET /profit/by-product` - Ürün bazında kâr
- `GET /profit/by-marketplace` - Pazaryeri bazında kâr
- `GET /profit/by-period` - Dönem bazında kâr
- `POST /profit/expenses` - Ek gider ekle/güncelle

---

## Genel Response Formatı

### Başarılı Response
```json
{
    "success": true,
    "message": "İşlem başarılı",
    "data": { /* veri */ }
}
```

### Hata Response
```json
{
    "success": false,
    "message": "Hata mesajı",
    "errors": { /* validation hataları */ }
}
```

---

## Filtreler ve Parametreler

### Listeleme Endpoint'lerinde
- `page` - Sayfa numarası
- `per_page` - Sayfa başına kayıt (max: 100)
- `marketplace_id` - Pazaryerine göre filtre
- `status` - Duruma göre filtre
- `start_date` - Başlangıç tarihi
- `end_date` - Bitiş tarihi
- `search` - Arama terimi

### Örnek
```
GET /api/v1/marketplace-orders?marketplace_id=1&status=approved&page=1&per_page=50
```

---

## Hata Kodları

- `200` - Başarılı
- `201` - Oluşturuldu
- `400` - Hatalı istek
- `401` - Yetkisiz erişim
- `403` - Yasak
- `404` - Bulunamadı
- `422` - Validation hatası
- `500` - Sunucu hatası
