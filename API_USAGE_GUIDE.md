# Resbe API - Endpoint Reference

## 📖 Genel Bilgi

**Base URL:** `http://your-domain.com/api/v1`

**Authentication:** Tüm endpoint'ler (register/login hariç) Bearer token gerektirir.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Detaylı Kullanım Örnekleri:** [QUICK_START.md](./QUICK_START.md) dosyasına bakın.

---

## 🔐 Authentication

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| POST | `/auth/register` | ❌ | Yeni kullanıcı kaydı | `name`, `username`, `email`, `password`, `password_confirmation` |
| POST | `/auth/login` | ❌ | Giriş yap ve token al | `email`, `password` |
| POST | `/auth/logout` | ✅ | Çıkış yap (mevcut token'ı iptal et) | - |
| GET | `/auth/me` | ✅ | Kullanıcı bilgileri + istatistikler | - |
| POST | `/auth/refresh` | ✅ | Token yenile (tüm eski token'ları iptal eder) | - |
| POST | `/auth/revoke-all` | ✅ | Tüm token'ları iptal et | - |

---

## ⚙️ User Settings (Kullanıcı Ayarları)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/settings` | ✅ | Kullanıcı ayarlarını getir | - |
| PUT | `/settings` | ✅ | Ayarları güncelle | `preferred_language_id`, `theme`, `dark_mode`, `additional_settings` (hepsi opsiyonel) |
| PUT | `/settings/theme` | ✅ | Sadece temayı değiştir | `theme` (light, dark, system) |
| PUT | `/settings/language` | ✅ | Sadece dili değiştir | `language_id` (integer) |

---

## 🌍 Languages (Diller)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/languages` | ✅ | Aktif dilleri listele | - |
| GET | `/languages/{id}` | ✅ | Dil detayı | `id` (integer) |

---

## 🏪 Marketplace Management

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/marketplaces` | ✅ | Tüm marketplace'leri listele | - |
| GET | `/marketplaces/{id}` | ✅ | Marketplace detayı | `id` (integer) |
| POST | `/marketplaces/{id}/activate` | ✅ | Marketplace'i aktif et | `id` (integer) |

---

## 🔑 Marketplace Credentials

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/marketplace-credentials` | ✅ | Kullanıcının tüm credential'larını listele | - |
| POST | `/marketplace-credentials` | ✅ | Yeni credential ekle | `marketplace_id`, `api_key`, `api_secret`, `seller_id` |
| GET | `/marketplace-credentials/{id}` | ✅ | Credential detayı | `id` (integer) |
| PUT | `/marketplace-credentials/{id}` | ✅ | Credential güncelle | `id` (integer), `api_key`, `api_secret`, `seller_id` |
| DELETE | `/marketplace-credentials/{id}` | ✅ | Credential sil | `id` (integer) |
| POST | `/marketplace-credentials/{id}/test` | ✅ | Bağlantıyı test et | `id` (integer) |

---

## 📦 Products (Ürün Yönetimi)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/products` | ✅ | Ürünleri listele | Query: `search`, `page`, `per_page` |
| POST | `/products` | ✅ | Yeni ürün oluştur | `barcode`, `title`, `brand`, `category_id`, `sale_price`, `purchase_cost` |
| GET | `/products/{id}` | ✅ | Ürün detayı | `id` (integer) |
| PUT | `/products/{id}` | ✅ | Ürün güncelle | `id` (integer), ürün bilgileri |
| DELETE | `/products/{id}` | ✅ | Ürün sil (soft delete) | `id` (integer) |
| POST | `/products/bulk` | ✅ | Toplu ürün oluştur | `products` (array) |
| GET | `/products/{id}/marketplaces` | ✅ | Ürünün hangi marketplace'lerde olduğunu göster | `id` (integer) |

---

## 🔄 Marketplace Products (Senkronizasyon)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/marketplace-products` | ✅ | Marketplace'lerdeki ürünleri listele | Query: `marketplace_id`, `product_id` |
| POST | `/marketplace-products/push` | ✅ | Ürünü marketplace'e gönder | `product_id`, `marketplace_id` |
| POST | `/marketplace-products/pull` | ✅ | Marketplace'ten ürün çek | `marketplace_id`, Query: `barcode` |
| POST | `/marketplace-products/sync` | ✅ | Fiyat/stok senkronize et | `marketplace_product_id` |
| GET | `/marketplace-products/{id}` | ✅ | Marketplace ürün detayı | `id` (integer) |
| PUT | `/marketplace-products/{id}/price` | ✅ | Fiyat güncelle | `id` (integer), `price` |
| PUT | `/marketplace-products/{id}/stock` | ✅ | Stok güncelle | `id` (integer), `quantity` |

---

## 📋 Orders (Sipariş Yönetimi)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/marketplace-orders` | ✅ | Siparişleri listele | Query: `marketplace_id`, `status`, `start_date`, `end_date` |
| GET | `/marketplace-orders/{id}` | ✅ | Sipariş detayı | `id` (integer) |
| POST | `/marketplace-orders/fetch` | ✅ | Marketplace'ten sipariş çek | `marketplace_id`, `start_date`, `end_date` |
| PUT | `/marketplace-orders/{id}/status` | ✅ | Sipariş durumu güncelle | `id` (integer), `status` |
| PUT | `/marketplace-orders/{id}/tracking` | ✅ | Kargo takip no ekle | `id` (integer), `tracking_number` |
| POST | `/marketplace-orders/{id}/invoice` | ✅ | Fatura gönder | `id` (integer), `invoice_number`, `invoice_link` |

---

## 🔄 Claims (İade/İptal Yönetimi)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/marketplace-claims` | ✅ | İade/iptal taleplerini listele | Query: `marketplace_id`, `status`, `start_date`, `end_date` |
| GET | `/marketplace-claims/{id}` | ✅ | İade/iptal detayı | `id` (integer) |
| POST | `/marketplace-claims/fetch` | ✅ | Marketplace'ten iade/iptal çek | `marketplace_id`, `start_date`, `end_date` |
| POST | `/marketplace-claims/{id}/approve` | ✅ | İade/iptal onayla | `id` (integer) |
| POST | `/marketplace-claims/{id}/reject` | ✅ | İade/iptal reddet | `id` (integer), `reject_reason` |

---

## ❓ Questions (Soru-Cevap Yönetimi)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/marketplace-questions` | ✅ | Soruları listele | Query: `marketplace_id`, `answered` (boolean) |
| GET | `/marketplace-questions/{id}` | ✅ | Soru detayı | `id` (integer) |
| POST | `/marketplace-questions/fetch` | ✅ | Marketplace'ten soru çek | `marketplace_id` |
| POST | `/marketplace-questions/{id}/answer` | ✅ | Soruya cevap ver | `id` (integer), `answer` |

---

## 📂 Categories & Brands (Kategori ve Marka)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/categories` | ✅ | Kategorileri listele | Query: `marketplace_id`, `parent_id` |
| POST | `/categories/sync` | ✅ | Kategorileri senkronize et | `marketplace_id` |
| GET | `/categories/{id}/attributes` | ✅ | Kategori özelliklerini getir | `id` (integer) |
| GET | `/brands` | ✅ | Markaları listele | Query: `marketplace_id`, `search` |
| POST | `/brands/sync` | ✅ | Markaları senkronize et | `marketplace_id` |

---

## 💰 Financial Reports (Finansal Raporlar)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/marketplace-financials` | ✅ | Tüm finansal verileri listele | Query: `marketplace_id`, `start_date`, `end_date` |
| GET | `/marketplace-financials/settlements` | ✅ | Satış/iade işlemlerini listele | Query: `marketplace_id`, `start_date`, `end_date` |
| GET | `/marketplace-financials/other` | ✅ | Diğer finansal işlemleri listele (kesintiler, cezalar, vb.) | Query: `marketplace_id`, `start_date`, `end_date` |
| GET | `/marketplace-financials/cargo` | ✅ | Kargo faturalarını listele | Query: `marketplace_id`, `start_date`, `end_date` |
| POST | `/marketplace-financials/sync` | ✅ | Finansal verileri senkronize et | `marketplace_id`, `start_date`, `end_date` |
| GET | `/marketplace-financials/dashboard` | ✅ | Finansal özet/dashboard | Query: `marketplace_id`, `start_date`, `end_date` |
| GET | `/marketplace-financials/profit/{order_id}` | ✅ | Belirli bir siparişin kar analizi | `order_id` (integer) |

---

## 💵 Profit Calculation (Kar Hesaplama)

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/products/{id}/profit` | ✅ | Ürün kar analizi | `id` (integer) |
| GET | `/marketplace-products/{id}/profit` | ✅ | Marketplace ürün kar analizi | `id` (integer) |
| POST | `/additional-expenses` | ✅ | Ek masraf ekle | `product_id`, `marketplace_product_id`, `expense_type`, `amount`, `description` |
| GET | `/additional-expenses` | ✅ | Ek masrafları listele | Query: `product_id`, `marketplace_product_id` |
| PUT | `/additional-expenses/{id}` | ✅ | Ek masraf güncelle | `id` (integer), masraf bilgileri |
| DELETE | `/additional-expenses/{id}` | ✅ | Ek masraf sil | `id` (integer) |
| GET | `/profit/summary` | ✅ | Kar özeti/raporu | Query: `marketplace_id`, `start_date`, `end_date` |

---

## 🧪 Testing & Health Check

| Method | Endpoint | Auth | Açıklama | Parametreler |
|--------|----------|------|----------|--------------|
| GET | `/ping` | ❌ | API durumu kontrolü | - |
| GET | `/test-lang` | ❌ | Çoklu dil desteği testi | - |

---

## 📊 Response Format

Tüm endpoint'ler aynı formatta JSON döner:

**Başarılı:**
```json
{
  "success": true,
  "message": "İşlem başarılı",
  "data": { ... }
}
```

**Hatalı:**
```json
{
  "success": false,
  "message": "Hata mesajı",
  "errors": { ... }
}
```

---

## 🔒 Security Notes

- Tüm token'lar 256 karakter uzunluğundadır
- Token'lar `personal_access_tokens` tablosunda saklanır
- Login yapıldığında eski token'lar otomatik iptal edilir (single session)
- Rate limiting aktiftir (varsayılan: 60 istek/dakika)

---

## 📚 Daha Fazla Bilgi

- **Detaylı Örnekler:** [QUICK_START.md](./QUICK_START.md)
- **Proje Yapısı:** [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md)
- **Başlangıç:** [README.md](./README.md)

---

**Son Güncelleme:** 9 Kasım 2025  
**Toplam Endpoint:** 71 (6 yeni: settings + languages)  
**API Versiyonu:** v1
