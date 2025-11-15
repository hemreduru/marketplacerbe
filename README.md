# Resbe - Multi-Marketplace Management Platform

## 📋 Proje Hakkında

**Resbe**, Türkiye'deki e-ticaret satıcılarının birden fazla pazaryerindeki (Trendyol, Hepsiburada, n11, Amazon, vb.) ürün, sipariş, iade ve finansal işlemlerini tek bir platformdan yönetmelerini sağlayan kapsamlı bir yönetim sistemidir.

### Temel Özellikler

- ✅ **Multi-User**: Çoklu kullanıcı desteği
- ✅ **Multi-Marketplace**: Birden fazla pazaryeri entegrasyonu
- ✅ **Ürün Yönetimi**: Birleşik ürün yönetimi ve senkronizasyon
- ✅ **Sipariş Takibi**: Tüm pazaryerlerinden sipariş yönetimi
- ✅ **Finansal Raporlar**: Detaylı gelir-gider analizi ve kâr hesaplama
- ✅ **İade/İptal**: Claim yönetimi
- ✅ **Soru & Cevap**: Müşteri sorularını cevaplama
- ✅ **REST API**: Tam REST API desteği
- ✅ **Web Panel**: Modern ve kullanıcı dostu arayüz
- ✅ **Multi-Language**: Türkçe ve İngilizce dil desteği
- ✅ **Dark Mode**: Açık/Koyu tema desteği

---

## 🛠️ Teknolojiler

### Backend
- **Laravel 11.x** - PHP Framework
- **Laravel Sanctum** - API & Session Authentication
- **MySQL 8.x** - Veritabanı
- **Guzzle HTTP** - API istekleri için

### Frontend
- **Laravel Blade** - Template engine
- **Metronic 8** - Admin teması
- **Axios** - AJAX istekleri
- **Bootstrap 5** - UI framework

### Pazaryeri Entegrasyonları
- Trendyol API
- Hepsiburada API
- n11 API
- Amazon API (yakında)
- Çiçeksepeti API (yakında)

---

## 📦 Kurulum

### Gereksinimler

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Node.js & NPM (opsiyonel, asset build için)

### Adımlar

1. **Projeyi klonlayın**
```bash
cd /var/www/martketplace-apps/backend
```

2. **Bağımlılıkları yükleyin**
```bash
composer install
```

3. **Environment dosyasını ayarlayın**
```bash
cp .env.example .env
```

`.env` dosyasında aşağıdaki ayarları yapın:
```env
APP_NAME="Resbe"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=metronic8
DB_USERNAME=root
DB_PASSWORD=your_password

SESSION_DRIVER=database
```

4. **Application key oluşturun**
```bash
php artisan key:generate
```

5. **Veritabanı migration'larını çalıştırın**
```bash
php artisan migrate
```

6. **Seed verilerini yükleyin (opsiyonel)**
```bash
php artisan db:seed
```

7. **Development server'ı başlatın**
```bash
php artisan serve --port=8000
```

8. **Tarayıcıda açın**
```
http://localhost:8000
```

---

## 🚀 Kullanım

### Web Panel

1. **Giriş Yapma**
   - URL: `http://localhost:8000/login`
   - Kayıt olmak için: `http://localhost:8000/register`

2. **Dashboard**
   - Genel görünüm ve istatistikler

3. **Ayarlar**
   - Profil bilgilerini güncelleme
   - Dil ve tema tercihleri

### API Kullanımı

API endpoint'lerini kullanmak için [API_GUIDE.md](./API_GUIDE.md) dosyasına bakın.

#### Örnek: Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept-Language: tr" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

Response:
```json
{
  "success": true,
  "message": "Giriş başarılı",
  "data": {
    "token": "1|abcd...",
    "user": { /* user data */ }
  }
}
```

---

## 📁 Proje Yapısı

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/          # API controllers
│   │   │   └── Web/             # Web controllers
│   │   ├── Middleware/          # Custom middleware
│   │   └── Requests/            # Form validation
│   ├── Models/                  # Eloquent models
│   └── Services/                # Business logic
│       └── Marketplace/         # Marketplace integrations
├── database/
│   ├── migrations/              # Database migrations
│   └── seeders/                 # Database seeders
├── resources/
│   ├── views/                   # Blade templates
│   └── lang/                    # Translations (tr, en)
├── routes/
│   ├── api.php                  # API routes
│   └── web.php                  # Web routes
└── public/
    └── assets/                  # Frontend assets
```

---

## 🗄️ Veritabanı

### Temel Tablolar

- `users` - Kullanıcılar
- `user_settings` - Kullanıcı ayarları
- `marketplaces` - Pazaryeri platformları
- `user_marketplace_credentials` - API bağlantı bilgileri
- `products` - Birleşik ürün tablosu
- `marketplace_products` - Pazaryeri özel ürün bilgileri
- `marketplace_orders` - Siparişler
- `marketplace_claims` - İade/İptal talepleri
- `marketplace_questions` - Müşteri soruları
- `marketplace_settlements` - Ödemeler
- `marketplace_cargo_invoices` - Kargo faturaları
- `marketplace_sync_logs` - Senkronizasyon logları

---

## 🔐 Authentication

### Web Authentication
- Session-based authentication
- Laravel'in built-in Auth sistemi
- `auth` middleware

### API Authentication
- Token-based authentication (Laravel Sanctum)
- Bearer token
- `auth:sanctum` middleware

---

## 🌍 Çoklu Dil Desteği

### Desteklenen Diller
- Türkçe (tr)
- İngilizce (en)

### Kullanım

#### Web'de
- Ayarlar sayfasından dil değiştirilebilir
- Otomatik olarak session'a kaydedilir

#### API'de
- Request header'da belirtilir:
```
Accept-Language: tr
```

---

## 🎨 Tema Yönetimi

### Özellikler
- Light Mode (Açık tema)
- Dark Mode (Koyu tema)
- Kullanıcı tercihine göre otomatik kayıt

### Değiştirme
- Header'daki tema butonuna tıklayın
- Veya `/settings/theme` endpoint'ini kullanın

---

## 🧪 Testing

### Unit Tests
```bash
php artisan test
```

### Postman Collection
API endpoint'lerini test etmek için Postman collection kullanabilirsiniz.

---

## 📝 Cache Yönetimi

Cache temizleme:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

Tüm cache'leri bir seferde temizle:
```bash
php artisan optimize:clear
```

---

## 🐛 Sorun Giderme

### "Page Expired" Hatası
```bash
# Session tablosunu kontrol edin
php artisan migrate

# Cache'leri temizleyin
php artisan config:clear
php artisan view:clear
```

### API 401 Unauthorized
- Token'ın doğru olduğundan emin olun
- `Authorization: Bearer {token}` header'ının eklendiğini kontrol edin

### Database Connection Error
- `.env` dosyasındaki database ayarlarını kontrol edin
- MySQL servisinin çalıştığından emin olun

---

## 📚 Dökümantasyon

- **[API_GUIDE.md](./API_GUIDE.md)** - API kullanım rehberi ve endpoint dökümantasyonu

---

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

---

## 📄 Lisans

Bu proje özel bir projedir.

---

## 👨‍💻 Geliştirici

**Emre**

---

## 📞 İletişim

Sorularınız için issue açabilirsiniz.

---

## 🔄 Güncellemeler

### Versiyon 1.0.0 (Kasım 2024)
- ✅ İlk stabil versiyon
- ✅ Trendyol entegrasyonu
- ✅ Web panel
- ✅ API sistemi
- ✅ Çoklu dil desteği
- ✅ Tema yönetimi

---

## 🎯 Roadmap

- [ ] Hepsiburada entegrasyonu
- [ ] n11 entegrasyonu
- [ ] Amazon entegrasyonu
- [ ] Mobile app
- [ ] Gelişmiş raporlama
- [ ] Dashboard widget'ları
- [ ] Otomatik stok yönetimi
- [ ] Fiyat otomasyonu

---

**🚀 Happy Coding!**
