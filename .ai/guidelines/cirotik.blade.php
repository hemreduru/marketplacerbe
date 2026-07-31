# Cirotik — Kodlama Kuralları (Laravel Boost)

Cirotik: çok pazaryerli satıcının **SKU bazlı gerçek net kârını** doğrulanmış şekilde gösteren karlılık platformu. Laravel 12 / PHP 8.3. **1 kullanıcı = 1 hesap**, tüm sahiplik `user_id` üzerinden.

> Tam bağlayıcı spec: `/home/emre/Documents/CIROTIK_AGENT_SPEC.md` (Bölüm 0, 6, 16).

## Mimari (koda uygun — güncel)

Katmanlı servis mimarisi. Akış:

```
routes/web.php → App\Http\Controllers\Web\* (thin)
  → App\Services\* (tüm iş kuralı + API çağrıları)
    → Eloquent Model (sadece veri; ASLA API çağrısı yapmaz)
```

- Tüm uygulama route'ları session auth (`auth`/`guest` grupları). Özellik gating: `feature:*` middleware; admin `admin` prefix'i altında. `api.php` sadece bir stub'dır (`auth:sanctum GET /user`); public REST API Faz 5 hedefidir.
- Public POST webhook'lar auth bypass eder: `/webhooks/trendyol/{uuid}`, `/webhooks/hepsiburada/{uuid}`, `/webhooks/ses/*`, iyzico callback.
- View katmanı **server-rendered Blade**'dir (API-only DEĞİL).

### Pazaryeri servisleri (gerçek yapı)

Konum: **`app/Services/Marketplaces/<Name>/`**.

Servisler `new`'lenmez; **`App\Services\MarketplaceManager`** üzerinden erişilir:

```php
$manager->credentialFor($user, $slug);              // aktif UserMarketplaceCredential
$manager->productService($credential);              // vb. order/finance/question/claim
// make() credential'dan per-marketplace Client kurar ve concrete service'e inject eder
```

`MarketplaceManager::$services` map'inde **yalnızca**: trendyol, hepsiburada, n11, pazarama. Her biri `product/order/finance/question/claim` sunar. `make()` client match'i de sadece bu 4'ü kurar.

Zorunlu dosya seti (`app/Services/Marketplaces/<Name>/`): `Client.php`, `ProductService.php`, `OrderService.php`, `ClaimService.php`, `QuestionService.php`, `FinanceService.php`, `Mapper/`. **WebhookService yalnızca Trendyol'da** dosya olarak var; Hepsiburada webhook'u `HepsiburadaWebhookController` içinde. (Yani "WebhookService her pazaryeride zorunlu" kuralı gerçekte esnetilmiştir.)

**Amazon: scaffold-only.** `Client/OrderService/FinanceService/ReportsService` + `config/marketplaces/amazon.php` var ama `MarketplaceManager`'a **kayıtlı değil** ve ProductService/ClaimService/QuestionService yok — manager üzerinden instantiate edilemez. Yeni Amazon işi önce manager'a wiring gerektirir.

### Dönüş tipleri
- Pazaryeri/kargo/e-fatura servis metotları **`App\Support\ServiceResult` döner, throw ETMEZ.** Exception yalnızca programming bug içindir. `ServiceResult::ok($data)` / `ServiceResult::fail(code, message, raw)`.
- `App\Services\Calculations\*` value object (ör. `ProfitBreakdown`) veya scalar döner — **ServiceResult değil**.

### Servis kataloğu (mevcut)
- `Calculations/`: ProfitCalculator, VatCalculator, CommissionCalculator, ShippingCostCalculator, PackagingCostCalculator, ServiceFeeCalculator, ReturnCostEstimator, StopajCalculator, NetVatLiability, AdAllocator
- `Finance/`: ReconciliationService, SettlementReconciler, ProfitAggregator, DailyProfitAggregator, FeeResolver, ReturnCostResolver, ProfitContextFactory, AdSpendRepository
- `Inventory/`: MasterProductStockProjector, MasterProductPriceProjector, StockAlertService
- `Cargo/`: Manager facade + `Contracts/CargoProvider` + providers (Aras, Mng, Yurtici, Dhl, Ptt, Surat, Ups)
- `EFatura/`: EInvoiceManager + `Contracts/EInvoiceProvider` + providers (Parasut, BizimHesap, Gib)
- Ayrıca: Ads, Buybox, Repricer, Reports, Supplier, Auth/TwoFactorAuthService

## Bağlayıcı Kurallar

1. **ServiceResult zorunlu.** Pazaryeri/harici servis metodu `ServiceResult` döner, throw etmez. API/network/validation/rate-limit hataları `ok=false`.
2. **Para asla float değil.** `decimal(15,4)` veya cents-as-integer. Kâr hesabı yapan her sınıf için **TDD zorunlu** (önce Pest testi, sonra kod).
3. **İki katmanlı write-gating.** Yazma ancak `MARKETPLACE_WRITE_ENABLED=true` (config `marketplace.php` → `write_enabled`, default false) **VE** `user_marketplace_credentials.write_enabled=true` birlikte true iken atılır. Biri false ise write yok, UI disabled, log'a "write blocked".
4. **Idempotency zorunlu.** Webhook + sync job aynı external_id'yi 2 kez işlemez. `marketplace_events.event_uuid` PRIMARY KEY dedup; `stock_events` UNIQUE(source, source_reference, event_type) — duplicate insert exception fırlatmaz, yakalanır ve SKIP edilir.
5. **Stok = append-only event akışı, tek integer değil.** `current_stock = SUM(stock_events.quantity_delta)`. `master_products.current_stock/current_price` denormalize (salt-okuma), atomik `UPDATE ... SET current_stock = current_stock - 1 WHERE id=? AND current_stock >= 1`; 0 row affected = oversell sinyali. `price_events` aynı desende.
6. **Eloquent'ten asla direkt API çağrısı yapma** — daima Service katmanı.
7. **Optimistic lock.** `UPDATE master_products SET current_price=?, version=version+1 WHERE id=? AND version=?`; affected=0 → conflict → `sync_dispatch_queue`'ya güncel version ile yeniden. Conflict çözümü: satışlar absolute öncelikli (last-write-wins değil).
8. **Stock buffer.** `stock_buffer_strategy` enum(none|fixed|percent) + `stock_buffer_value`; push = `max(0, current_stock - buffer)`.
9. **Migration her zaman up() + down().** down()'suz bırakma; rollback ile test et.
10. **Hardcoded URL/limit yok.** Base URL/rate limit `config/marketplace.php` (merkezi) + `config/marketplaces/<name>.php`. Not: hem `marketplace.php` (merkezi) hem `marketplaces.php` (ince aggregator) mevcut. Test/prod credential karıştırma — `MarketplaceEnvironment` enum(stage|production).
11. **Capability-driven.** Her config'de capabilities manifest + limits + rate_limits; `MarketplaceCapability::supports()` ile UI aksiyonları gate edilir.
12. **CargoProvider** interface (`createShipment/cancelShipment/getLabel/track/listStatusUpdates/getServiceCode/getCapabilities` → hepsi ServiceResult); `CargoManager` facade ile erişilir.

## Konvansiyonlar

- **Yorum dili TÜRKÇE; kod (identifier/method/değişken) İNGİLİZCE.**
- Naming: Model PascalCase singular, tablo snake_case plural, servis `+Service`, job `+Job`, event `+Event`, enum değerleri snake_case string.
- PHP 8 modern stil: `final class`, `readonly` promoted constructor properties, constructor DI (`private readonly`), typed enum.
- Her public method için PHPDoc zorunlu; karmaşık iş kuralı için inline Türkçe yorum.
- Locale-bağımlı string karşılaştırma yasak: `strtolower` yerine `Str::lower` + tr locale.
- Para gösterimi **`@money` Blade directive** üzerinden.
- Yeni env → `.env.example`; yeni TR metin → `lang/tr/` **ve** eşi `lang/en/`.
- Commit: İngilizce conventional commits (`feat:`, `fix:`, `refactor:`, `test:`, `chore:`).
- Her PR öncesi: `vendor/bin/pint`, `vendor/bin/phpstan analyse` (level 6 temiz).

## Test Kuralları (Pest, PHPUnit değil)

- Para/kâr hesabı yapan her sınıf: **TDD zorunlu** (kırmızı → yeşil).
- Zorunlu sync edge-case'leri: aynı webhook 2× → stok bir kez azalır; API down → 5 deneme sonra failed + bildirim; manuel stok + eş zamanlı webhook → ikisi de event; master 3 pazaryerinde → manuel update 3'üne dispatch; iade webhook → stok +1; oversell → tüm pazaryerlerine 0 push + alarm; 15dk'da aynı SKU 2 fiyat update engellenir; event_uuid duplicate → UNIQUE ile yakalanır, exception yok.
- Stock projector: 100 ardışık event → projection doğru.
- Kâr doğrulama metric'i: tahmini net kâr ile `getSettlements` gerçeği farkı ≤ %2.
- Platform fee: paket başına 1 kez (item başına değil) — 5 item'lı paket kârı = SUM(items) − 4×service_fee.
- Migration up()+down() rollback ile test.
- Non-trivial mantık (branch/loop/parser/para/güvenlik) en az bir runnable test bırakır; trivial one-liner gerekmez.

## Do NOT

- try-catch ile exception yutma — hatayı `ServiceResult(ok=false)` ile yüzeye çıkar.
- down()'suz migration.
- Eloquent'ten direkt pazaryeri API çağrısı.
- Para hesabında float.
- Locale-bağımlı string karşılaştırma.
- Hardcoded URL/limit.
- **Team/Workspace/RBAC/tenant/multi-user scaffolding ekleme** — kaldırıldı; sahiplik `user_id`. spatie/laravel-permission kullanılmıyorsa kaldır.
- Native mobil app / push notification ekleme — kapsam dışı (e-posta + in-app + PWA yeterli).
- Stok'u tek integer olarak mutate etme — append-only ledger + projection.
- Servis metodundan exception fırlatma (bug hariç).
- PHPDoc'suz public method.
