# 🚀 Resbe API - Quick Start Guide

## 📋 Hızlı Başlangıç

Bu rehber, frontend geliştiricilerin Resbe API'yi hızlıca entegre etmesi için adım adım yol haritası sunar.

---

## 1️⃣ Authentication (Kimlik Doğrulama)

### İlk Kullanıcı Kaydı

```javascript
// POST /api/v1/auth/register
const response = await fetch('http://api.example.com/api/v1/auth/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: 'Mehmet Yılmaz',
    username: 'mehmetyilmaz',
    email: 'mehmet@example.com',
    password: 'Güvenli123!',
    password_confirmation: 'Güvenli123!'
  })
});

const data = await response.json();
// Token'ı sakla
localStorage.setItem('token', data.data.token);
```

### Giriş Yapma

```javascript
// POST /api/v1/auth/login
const response = await fetch('http://api.example.com/api/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'mehmet@example.com',
    password: 'Güvenli123!'
  })
});

const data = await response.json();
localStorage.setItem('token', data.data.token);
```

### Her İstekte Token Kullanımı

```javascript
const token = localStorage.getItem('token');

const response = await fetch('http://api.example.com/api/v1/products', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

---

## 2️⃣ Marketplace Entegrasyonu

### Adım 1: Marketplace Listesini Getir

```javascript
// GET /api/v1/marketplaces
const marketplaces = await fetch('/api/v1/marketplaces', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

// Sonuç: Trendyol, Hepsiburada, N11, vb.
```

### Adım 2: API Bilgilerini Ekle

```javascript
// POST /api/v1/marketplace-credentials
await fetch('/api/v1/marketplace-credentials', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    marketplace_id: 1, // Trendyol
    supplier_id: '12345',
    api_key: 'your_api_key',
    api_secret: 'your_api_secret',
    store_name: 'Benim Mağazam'
  })
});
```

### Adım 3: Bağlantıyı Test Et

```javascript
// POST /api/v1/marketplace-credentials/{id}/test
const testResult = await fetch('/api/v1/marketplace-credentials/1/test', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

// testResult.data.status === 'connected' ise başarılı
```

---

## 3️⃣ Ürün Yönetimi

### Yeni Ürün Ekleme

```javascript
// POST /api/v1/products
await fetch('/api/v1/products', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Apple MacBook Pro 14"',
    sku: 'MBP-14-001',
    barcode: '1234567890123',
    category: 'Bilgisayar',
    brand: 'Apple',
    description: 'M3 Pro işlemci, 16GB RAM',
    purchase_price: 35000.00,
    currency: 'TRY',
    main_image: 'https://example.com/image.jpg'
  })
});
```

### Ürünleri Listeleme

```javascript
// GET /api/v1/products?page=1&per_page=20
const products = await fetch('/api/v1/products?page=1&per_page=20', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

products.data.forEach(product => {
  console.log(`${product.name} - ${product.sku}`);
});
```

---

## 4️⃣ Pazaryerine Ürün Ekleme

### Adım 1: Kategorileri Senkronize Et

```javascript
// POST /api/v1/marketplace-categories/sync
await fetch('/api/v1/marketplace-categories/sync', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    marketplace_id: 1,
    credential_id: 1
  })
});
```

### Adım 2: Kategori Ağacını Getir

```javascript
// GET /api/v1/marketplace-categories/tree?marketplace_id=1
const categoryTree = await fetch('/api/v1/marketplace-categories/tree?marketplace_id=1', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());
```

### Adım 3: Ürünü Pazaryerine Ekle

```javascript
// POST /api/v1/marketplace-products
await fetch('/api/v1/marketplace-products', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product_id: 1,
    marketplace_id: 1,
    credential_id: 1,
    marketplace_category_id: 'ELKT-123',
    sale_price: 42000.00,
    list_price: 45000.00,
    stock_quantity: 5,
    cargo_company: 'Aras Kargo'
  })
});
```

---

## 5️⃣ Sipariş Yönetimi

### Siparişleri Senkronize Et

```javascript
// POST /api/v1/marketplace-orders/sync
await fetch('/api/v1/marketplace-orders/sync', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    marketplace_id: 1,
    credential_id: 1,
    start_date: '2025-11-01',
    end_date: '2025-11-08'
  })
});
```

### Bekleyen Siparişleri Listele

```javascript
// GET /api/v1/marketplace-orders?status=awaiting_shipment
const pendingOrders = await fetch('/api/v1/marketplace-orders?status=awaiting_shipment', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());
```

### Sipariş Gönder

```javascript
// POST /api/v1/marketplace-orders/{id}/ship
await fetch('/api/v1/marketplace-orders/1/ship', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    tracking_number: 'ARAS1234567890',
    cargo_company: 'Aras Kargo',
    invoice_number: 'INV-2025-001'
  })
});
```

---

## 6️⃣ Karlılık Analizi

### Tek Ürün Kar Hesaplama

```javascript
// POST /api/v1/profit/calculate
const profitData = await fetch('/api/v1/profit/calculate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product_id: 1,
    marketplace_id: 1
  })
}).then(r => r.json());

console.log(`Kar: ${profitData.data.net_profit} TL`);
console.log(`Kar Oranı: %${profitData.data.profit_rate}`);
```

### Toplu Kar Analizi

```javascript
// POST /api/v1/profit/bulk-calculate
const bulkProfit = await fetch('/api/v1/profit/bulk-calculate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product_ids: [1, 2, 3, 4, 5],
    marketplace_id: 1
  })
}).then(r => r.json());

console.log(`Toplam Kar: ${bulkProfit.data.summary.total_net_profit} TL`);
```

### Ekstra Gider Ekleme

```javascript
// POST /api/v1/profit/expenses
await fetch('/api/v1/profit/expenses', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    expense_type: 'packaging',
    title: 'Premium Ambalaj',
    description: 'Özel kutulu paketleme',
    amount: 25.00,
    currency: 'TRY',
    allocation_type: 'per_product',
    product_id: 1,
    expense_date: '2025-11-08'
  })
});
```

---

## 7️⃣ Müşteri Hizmetleri

### Soruları Senkronize Et ve Cevapla

```javascript
// 1. Sync questions
await fetch('/api/v1/marketplace-questions/sync', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    marketplace_id: 1,
    credential_id: 1
  })
});

// 2. List unanswered
const questions = await fetch('/api/v1/marketplace-questions?status=unanswered', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

// 3. Answer a question
await fetch('/api/v1/marketplace-questions/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    answer: 'Evet, Türkçe Q klavye seçeneği mevcuttur.'
  })
});
```

### İade/İptal Taleplerini Yönet

```javascript
// 1. Sync claims
await fetch('/api/v1/marketplace-claims/sync', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    marketplace_id: 1,
    credential_id: 1
  })
});

// 2. List pending claims
const claims = await fetch('/api/v1/marketplace-claims?status=pending', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

// 3. Approve a claim
await fetch('/api/v1/marketplace-claims/1/approve', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` }
});
```

---

## 8️⃣ Finansal Raporlar

### Ciro Özeti

```javascript
// GET /api/v1/marketplace-financials/summary
const financialSummary = await fetch(
  '/api/v1/marketplace-financials/summary?start_date=2025-11-01&end_date=2025-11-08',
  {
    headers: { 'Authorization': `Bearer ${token}` }
  }
).then(r => r.json());

console.log(`Toplam Satış: ${financialSummary.data.total_sales} TL`);
console.log(`Net Gelir: ${financialSummary.data.net_revenue} TL`);
```

### Ödeme Takvimleri (Settlements)

```javascript
// GET /api/v1/marketplace-financials/settlements
const settlements = await fetch('/api/v1/marketplace-financials/settlements', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

settlements.data.forEach(settlement => {
  console.log(`${settlement.settlement_id}: ${settlement.net_amount} TL`);
});
```

---

## 9️⃣ Kullanıcı Bilgileri ve İstatistikler

### Profil Bilgisi ve Özet İstatistikler

```javascript
// GET /api/v1/auth/me
const userInfo = await fetch('/api/v1/auth/me', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

console.log(`Hoşgeldin, ${userInfo.data.name}!`);
console.log(`Ürün Sayısı: ${userInfo.data.stats.products}`);
console.log(`Toplam Sipariş: ${userInfo.data.stats.orders}`);
console.log(`Bağlı Pazaryeri: ${userInfo.data.stats.marketplace_credentials}`);
```

---

## 🔟 Hata Yönetimi

### Standart Hata Yanıtı

```javascript
try {
  const response = await fetch('/api/v1/products', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  if (!response.ok) {
    const error = await response.json();
    
    if (response.status === 401) {
      // Token geçersiz, login'e yönlendir
      localStorage.removeItem('token');
      window.location.href = '/login';
    } else if (response.status === 422) {
      // Validation hatası
      console.error('Validation errors:', error.errors);
    } else {
      // Genel hata
      console.error('Error:', error.message);
    }
  }
} catch (err) {
  console.error('Network error:', err);
}
```

---

## 📊 Dashboard İçin Örnek Veri Akışı

```javascript
async function loadDashboard() {
  const token = localStorage.getItem('token');
  
  // 1. Kullanıcı bilgileri
  const user = await fetch('/api/v1/auth/me', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  // 2. Bekleyen siparişler
  const pendingOrders = await fetch('/api/v1/marketplace-orders?status=awaiting_shipment', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  // 3. Cevaplanmamış sorular
  const questions = await fetch('/api/v1/marketplace-questions?status=unanswered', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  // 4. Bu ayın kar özeti
  const profitSummary = await fetch('/api/v1/profit/summary?start_date=2025-11-01', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  // 5. Finansal özet
  const financial = await fetch('/api/v1/marketplace-financials/summary?start_date=2025-11-01', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  return {
    user: user.data,
    pendingOrdersCount: pendingOrders.meta.total,
    questionsCount: questions.meta.total,
    monthlyProfit: profitSummary.data.total_net_profit,
    monthlySales: financial.data.total_sales
  };
}
```

---

## 🎯 Önerilen İlk 10 Endpoint Sıralaması

Frontend geliştirmeye başlarken bu sırayla entegre edin:

1. **POST /auth/register** - Kullanıcı kaydı
2. **POST /auth/login** - Giriş yapma
3. **GET /auth/me** - Kullanıcı bilgileri
4. **GET /marketplaces** - Pazaryeri listesi
5. **POST /marketplace-credentials** - API bağlantısı
6. **POST /marketplace-credentials/{id}/test** - Bağlantı testi
7. **GET /products** - Ürün listesi
8. **POST /products** - Ürün ekleme
9. **GET /marketplace-orders** - Sipariş listesi
10. **POST /profit/calculate** - Kar hesaplama

---

## 🔒 Güvenlik Kontrol Listesi

- [x] Token'ı güvenli yerde sakla (localStorage veya httpOnly cookie)
- [x] Her istekte Authorization header'ı ekle
- [x] 401 hatalarında otomatik logout yap
- [x] Hassas verileri (password, api_secret) logla **ASLA**
- [x] HTTPS kullan (production'da)
- [x] Token süresini kontrol et, gerekirse refresh yap
- [x] Logout'ta token'ı sil

---

## 📚 Ek Kaynaklar

- **Tam API Dökümantasyonu:** `API_USAGE_GUIDE.md`
- **Implementation Plan:** `IMPLEMENTATION_PLAN.md`
- **Base URL:** `http://your-domain.com/api/v1`
- **Toplam Endpoint:** 65 adet
- **Authentication:** Laravel Sanctum (Bearer Token)

---

## 💡 Pratik İpuçları

1. **Token Yönetimi:** Token'ı her API çağrısında axios interceptor ile otomatik ekle
2. **Loading States:** API çağrıları sırasında loading göster
3. **Error Handling:** Merkezi bir error handler kullan
4. **Caching:** Sık değişmeyen verileri (kategoriler, markalar) cache'le
5. **Debounce:** Search inputlarında debounce kullan
6. **Pagination:** Büyük listelerde pagination kullan (page, per_page)
7. **Real-time:** Sipariş durumları için polling veya WebSocket kullan

---

**Kolay gelsin! 🚀**
