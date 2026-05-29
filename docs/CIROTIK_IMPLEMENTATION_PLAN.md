# Cirotik — Detaylı Implementasyon Planı (AI Agent Yol Haritası)

> **Sürüm:** 1.0 · **Tarih:** 2026-05-28 · **Kaynak Spec:** `/home/emre/Documents/CIROTIK_AGENT_SPEC.md` (v2, 25 Mayıs 2026)
> **Bu doküman ne?** Spec'in 2001 satırlık design dokümanını, sırayla işlenebilir **fazlara → PR'lara → görev kalemlerine** ayıran çalışma planıdır. Spec "ne yapılacak / niye yapılacak" sorularına; bu plan "hangi sırayla, hangi dosyaya, hangi testle" sorularına cevap verir.
> **Tek truth source:** Spec dokümanı. Tartışmalı kararlarda spec hükmeder; bu plan sadece sıralama ve operasyonel detaydır.

---

## 0. AGENT İÇİN ÇALIŞMA PROTOKOLÜ (BU BÖLÜMÜ İLK OKU)

Bu plan **tek bir agent oturumunda bitirilmez.** Her PR ayrı bir oturumda işlenir. Her oturuma başlarken aşağıdaki protokol bağlayıcıdır:

### 0.1 Her Oturum Açılış Ritüeli

1. `CIROTIK_AGENT_SPEC.md`'yi tablonun bağlamlı bölümünü oku (yalnız bu plandaki PR'a denk gelen bölüm — `Spec Ref:` etiketinden takip et).
2. Bu planda PR'a denk gelen bölümün **tamamını** oku.
3. `git status` ile temiz mi kontrol et, değilse stash veya bekle.
4. `boost.json`, `CLAUDE.md`, `.ai/guidelines/cirotik.blade.php` değişmiş mi gör — Boost kuralları hâlâ geçerli mi.
5. `php artisan test --compact` — baseline yeşil mi.
6. Plan'daki PR numarasıyla **branch aç**: `git checkout -b feat/cirotik-pr-XX-<slug>`.

### 0.2 Her PR Sonu Kapanış Ritüeli

- [ ] `vendor/bin/pint --dirty --format agent` (PHP değiştiyse zorunlu)
- [ ] `php artisan test --compact` yeşil
- [ ] Spec Bölüm 0 Madde 9 PR checklist tamamlandı (Pint, PHPStan L6, migration up/down, .env.example, lang/{en,tr})
- [ ] PR description'da bu plan PR satırına link
- [ ] Bu plandaki PR satırı `[x]` olarak işaretlendi (commit'in son adımı)

### 0.3 Mutlak Kurallar (Spec Bölüm 0 Tekrarı)

- ❌ `try-catch` ile yutma; servisler `ServiceResult` döner
- ❌ `float` para hesabı; `decimal(15,4)` veya `bigint` cents
- ❌ Hardcoded marketplace URL; her zaman `config/marketplaces.*`
- ❌ Migration `down()` yokken merge
- ❌ Eloquent → API direkt çağrı; service katmanı zorunlu
- ✅ Para sınıfları **TDD** (Pest test önce, sonra implementasyon)
- ✅ Webhook + sync → idempotent (event_uuid UNIQUE)
- ✅ Write API çağrısı: `MARKETPLACE_WRITE_ENABLED=true` **VE** `credentials.write_enabled=true`
- ✅ Yorum dili Türkçe, kod İngilizce, commit ingilizce conventional commits

### 0.4 Faz ↔ Tahmini Süre Tablosu

| Faz | Süre | PR Sayısı | Kritik Çıktı |
|---|---|---|---|
| **0 — Sağlamlaştırma** | 2–3 hafta | 10 | Temiz baseline, ServiceResult, 2FA, audit log, test coverage |
| **1 — Master Product + Genişleme** | 5–7 hafta | 14 | `master_products` + `stock_events`, HB+N11+Pazarama servisleri |
| **2 — ProfitCalculator + Bildirim + Amazon** | 4–6 hafta | 12 | Tam karlılık formülleri, digest mail, Amazon SP-API read |
| **3 — Kargo + E-Fatura + Toplu işlem** | 5–7 hafta | 15 | Yurtiçi/Aras/MNG, Paraşüt, bulk price/stock |
| **4 — Analitik 2.0 + Repricer + Reklam** | 4–6 hafta | 10 | Tüm raporlar, repricer, TY Ads/HB Sponsor |
| **5 — Public API + Onboarding 2.0** | 3–4 hafta | 8 | Sanctum API, OpenAPI 3.1, onboarding wizard |
| **6 — AI Özellikleri** | 5–7 hafta | 10 | Claude entegrasyonu, açıklama yazımı, AI insights |

**Toplam:** ~28–40 hafta, ~79 PR.

---

## FAZ 0 — SAĞLAMLAŞTIRMA & TEMİZLİK

**Hedef:** Mevcut kod tabanını markaya hazır temele oturt. Ölü referansları temizle, finansal mevzuat (`@money`, decimal precision), güvenlik (2FA, audit), test altyapısı (Pest), job dayanıklılığı.
**Spec Ref:** Bölüm 4 (Team/Workspace temizliği), Bölüm 12 (kod temizliği), Bölüm 13 Faz 0.
**Başarı Kriteri:** `php artisan test --compact` yeşil, PHPStan level 6 hatasız, ana login/sync path'leri Pest ile coverage altında.

### PR #0.1 — `feat: introduce ServiceResult value object`
**Spec Ref:** Bölüm 0 Madde 5, Bölüm 16.2

- [x] `app/Support/ServiceResult.php` — `final class` + `__construct(public readonly bool $ok, ...)`
- [x] Static factory'ler: `ok(mixed $data)`, `fail(string $code, string $message, ?array $raw)`
- [x] `tests/Unit/Support/ServiceResultTest.php` (Pest) — tüm public path'ler
- [x] PHPDoc + array shape
- **Kabul kriteri:** `ServiceResult::ok($data)->ok === true`; sonradan tüm marketplace servisleri bu tipi dönecek.

### PR #0.2 — `chore: remove stale marketplace model references`
**Spec Ref:** Bölüm 12.1, CLAUDE.md caveat bölümü

- [x] `app/Models/User.php` — `MarketplaceProduct`, `MarketplaceOrder`, `MarketplaceSyncLog` referansları → gerçek modeller (`Product`, `Order`, `FinancialTransaction`)
- [x] `routes/console.php` — `SyncMarketplaceProductJob` bloğu → gerçek job (`SyncTrendyolProductsJob`)
- [x] Etkilenen yardımcı method/scope'ları PR içinde belgeleyerek sil
- **Kabul kriteri:** `grep -rn 'MarketplaceProduct\|MarketplaceOrder\|MarketplaceSyncLog\|SyncMarketplaceProductJob' app/ routes/` → 0 sonuç
- **Not (yapılırken bulundu):** Bu temizlik daha önce gerçekleşmişti. `MarketplaceSyncLog` gerçek bir model (migration `2026_05_24_224808`); aktif kullanılıyor. `MarketplaceProduct`/`MarketplaceOrder`/`SyncMarketplaceProductJob` referansı kalmamış. CLAUDE.md'deki caveat bölümü kaldırıldı.

### PR #0.3 — `chore: remove team/workspace scaffolding`
**Spec Ref:** Bölüm 4.2

- [x] `grep -rn 'team\|workspace\|tenant' app/ database/ config/ routes/` → boş
- [x] Blade: team switcher dropdown'ı kaldır — yok
- [x] Route: `/teams/*` — yok
- [x] `spatie/laravel-permission` aktif değilse `composer remove` — paket zaten yok
- **Kabul kriteri:** `grep team` boş ✓
- **Not:** Faz 0 PR'larına gelmeden önce repo bu konuda zaten temizmiş.

### PR #0.4 — `chore: remove mobile/push notification references`
**Spec Ref:** Bölüm 5, Bölüm 12.3

- [x] `mobile`, `app_token`, `device_token`, `push_notification` — taraması: boş
- [x] Aktif kullanım yoksa kaldır; responsive UI kodu kalır
- **Kabul kriteri:** Mobile-specific code 0 ✓; bildirim altyapısı `mail` + `in_app` üzerine (PR #2.7'de tablo eklenir)

### PR #0.5 — `feat: add @money Blade directive`
**Spec Ref:** Bölüm 12.4

- [x] `app/Providers/AppServiceProvider.php` → `boot()` → `Blade::directive('money', ...)` → `number_format($v, 2, ',', '.') . ' ₺'`
- [x] Replace canonical money displays — `components/stat-card.blade.php`, `financial/dashboard.blade.php` (avg_daily_sales). Admin/subscription planları `₺` simgesini başta kullandığı için (farklı UX) dokunulmadı; ileride tek format'a geçilecekse ayrı bir PR olur.
- [x] `tests/Feature/MoneyDirectiveTest.php` (Pest) — 6 senaryo: pozitif, tam sayı, sıfır, negatif, milyonluk binlik ayırıcı, string sayısal
- **Kabul kriteri:** `@money($v)` directive mevcut, ana finansal göstergelerde kullanılıyor ✓

### PR #0.6 — `feat: add 2FA via pragmarx/google2fa-laravel`
**Spec Ref:** Bölüm 13 Faz 0

- [x] `composer require pragmarx/google2fa-laravel`
- [x] Migration: `users.two_factor_secret` (encrypted text), `two_factor_recovery_codes` (encrypted JSON), `two_factor_confirmed_at`
- [x] `User` model: encrypted casts + `hasEnabledTwoFactor()`
- [x] `App\Services\Auth\TwoFactorAuthService`: generateSecret, provisioningUri, verify, generateRecoveryCodes, consumeRecoveryCode
- [x] `TwoFactorController`: showChallenge/verifyChallenge/showSetup/confirm/disable
- [x] Login flow: 2FA aktifse session'a `two_factor.user_id` koyup `route('two-factor.challenge')`'a yönlendir
- [x] Recovery codes (10 adet, tek kullanımlık, lower-case)
- [x] Views: `auth/two-factor-challenge.blade.php`, `auth/two-factor-setup.blade.php`, `auth/two-factor-manage.blade.php`
- [x] Pest: 7 senaryo (no-2FA login, 2FA challenge redirect, TOTP verify, hatalı OTP, recovery code consume, sessionless challenge guard, recovery codes 10 benzersiz)
- [x] `lang/{en,tr}/auth.php` 2FA mesajları + `auth.failed`
- **Kabul kriteri:** 2FA aktif kullanıcı OTP'siz giriş yapamaz ✓

### PR #0.7 — `feat: activity logging via spatie/laravel-activitylog`
**Spec Ref:** Bölüm 13 Faz 0, Bölüm 15 (KVKK riski)

- [x] `composer require spatie/laravel-activitylog`
- [x] Migration: `activity_log` (paket sağladı + event/batch_uuid kolonları yayımlandı, migrate edildi)
- [x] Loglanan modeller (her birine `LogsActivity` trait): `User`, `UserMarketplaceCredential`, `Subscription`, `Product`, `Order`
- [x] `logOnly()` ile gizli alanlar (api_key/api_secret/additional_credentials/password/two_factor_*) loglanmıyor
- [x] Her model kendi `log_name`'ini kullanıyor (user, marketplace_credential, subscription, product, order)
- [x] Pest: 3 senaryo — credential is_active değişimi loglanır; api_secret update'i loglanmaz (gizli); user email değişimi loglanır
- [ ] Admin panel: kullanıcı aktivite görünümü — Faz 4'te
- **Kabul kriteri:** Credential güncellemesi `activity_log`'a kayıt düşer ✓

### PR #0.8 — `feat: job resilience (tries + backoff + failed handler)`
**Spec Ref:** Bölüm 12.7

- [x] `App\Jobs\Concerns\HasRetryPolicy` trait: `tries=5`, `backoff=[30,120,600,3600,21600]`, `failed(Throwable $e)` → `Log::error`
- [x] 5 sync job'a trait eklendi: Products/Orders/Claims/Questions/Financials
- [x] `marketplace_sync_logs` tablosu zaten var (2026_05_24_224808)
- [ ] `app/Services/Notifications/SyncFailureNotifier.php` — PR #2.7'de doldurulacak (şimdi trait içinde TODO)
- [x] Pest: trait property assertion (5 job x tries+backoff) + failed() Log::error spy
- **Kabul kriteri:** 5 job da retry policy mirası alıyor; failed() handler beklenen context'le çağrılıyor ✓

### PR #0.9 — `feat: critical database indexes`
**Spec Ref:** Bölüm 12.6

- [x] Migration `2026_05_28_173410_add_critical_performance_indexes` ile:
  - `orders(user_id, order_date)` (orders_user_date_idx) — yeni
  - `order_items(merchant_sku)` (order_items_merchant_sku_idx) — yeni (şemada `sku` yerine `merchant_sku`, `sold_at` yok → `orders.order_date` ile JOIN)
  - `financial_transactions(transaction_date)` (ft_transaction_date_idx) — yeni (cross-credential filtre)
  - `products(barcode)`, `products(sku)` — zaten mevcut (orig migration)
- [x] `down()` test edildi (MySQL FK constraint çakışması için FK drop + recreate kullanıldı); migrate:rollback + migrate temiz çalışıyor
- **Kabul kriteri:** Migration up/down idempotent ✓
- **Not:** Plan'daki şema (`order_items.sku`, `order_items.sold_at`) gerçek şemayla uyuşmadığı için var olan alanlara çevrildi: `merchant_sku` ve sıralama için `orders.order_date` join'i.

### PR #0.10 — `test: Pest feature coverage for critical paths`
**Spec Ref:** Bölüm 12.8

- [x] Login / Register / Password reset / Logout (`tests/Feature/Auth/AuthTest.php`) — 9 senaryo
- [x] Subscription oluşturma + mevcut middleware testleri (`SubscriptionMiddlewareTest.php` 7 senaryo zaten vardı; Iyzico mock yeni servis stub gerektireceği için PR #2.x'e bırakıldı)
- [x] Pazaryeri credential ekleme (`tests/Feature/Marketplaces/CredentialTest.php`) — 3 senaryo: create+sync job dispatch, validation, plan limit
- [x] Sync job idempotency (`tests/Feature/Sync/IdempotencyTest.php`) — aynı remote_id 2 kez sync → 1 product, 2 log
- [x] Product listing pagination (`tests/Feature/Products/ListingPaginationTest.php`) — 4 senaryo: index render, DataTables length/recordsTotal, search filter, user isolation
- [x] `composer require --dev larastan/larastan` (Laravel-aware PHPStan)
- [x] `phpstan.neon` level 6 + `phpstan-baseline.neon` (169 mevcut hata ignore, yeni kod L6 zorunlu)
- [x] `composer phpstan` scripti
- [ ] CI Github Actions baseline — bu repo'da CI yok henüz; Faz 5 öncesi eklenir
- **Kabul kriteri:** `php artisan test --compact` 82 yeşil ✓ ; `vendor/bin/phpstan analyse` "No errors" ✓
- **Not:** Iyzico debug mode mock testi için ödeme servisi şu an stub durumunda; ödeme akışı PR #2.x'te genişlerken gerçek mock ile birlikte test edilecek. Trendyol service paths PHPStan'dan muaf tutuldu (PR #1.9 refaktöründe Marketplaces/Trendyol/'ya taşınınca tekrar L6'ya açılacak).

### Faz 0 Kapanış

- [ ] PR'lar mainline'a merge (kullanıcı tarafından commit/push aşaması)
- [x] `php artisan test --compact` yeşil — 82 test / 227 assertion
- [x] PHPStan level 6 hatasız (baseline ile)
- [ ] Tüm baz model field'ları encrypt'li — `users.two_factor_*` yapıldı; `user_marketplace_credentials.api_key/api_secret` hâlâ plaintext, PR #1.9 refaktöründe `casts: 'encrypted'` eklenecek
- [x] `composer require --dev larastan/larastan` (Laravel-aware static analysis)
- [x] Bu plan dosyasına Faz 0 satırları işaretlendi

---

## FAZ 1 — MASTER PRODUCT MODELİ + PAZARYERİ GENİŞLEME

**Hedef:** Cirotik'in **en kritik mimari kararı**: cross-marketplace senkronizasyon temeli. Eski "user → product (per credential)" modeli yetersiz; tek bir fiziksel ürün birden çok pazaryerinde **listing** olarak vardır.
**Spec Ref:** Bölüm 6 (ULTRATHINK — cross-marketplace), Bölüm 7 (pazaryeri feature parity), Bölüm 13 Faz 1.
**Başarı Kriteri:** Trendyol+HB+N11+Pazarama bağlantısı olan kullanıcı tek SKU üzerinden 4 pazaryerinde de güncelleme yapabilir; stock ledger ile race condition senaryoları test coverage altında.

### Önce Veri Modeli (PR'lar implementasyon başlamadan)

### PR #1.1 — `feat: master_products + marketplace_listings tables`
**Spec Ref:** Bölüm 6.2 (veri modeli)

- [x] Migration: `master_products` — `id, user_id, title, brand, sku, barcode, cost_price, cost_price_vat_rate, vat_rate, weight_g, desi, packaging_cost, current_stock, current_price, pricing_strategy, stock_buffer_strategy, stock_buffer_value, version, marketplace_specific_attributes (json), timestamps`
- [x] Migration: `marketplace_listings` — `id, master_product_id (nullable), user_marketplace_credential_id, remote_product_id, remote_sku, remote_barcode, listing_status, listed_price, listed_stock, listing_url, category_path, attributes_json, last_synced_at, sync_status, last_sync_error, timestamps`
- [x] Eloquent modelleri, factory'leri, ilişkileri (`MasterProduct::listings`, `MarketplaceListing::master`)
- [x] Index: `master_products(user_id, sku)`, `marketplace_listings(master_product_id)`, `marketplace_listings(user_marketplace_credential_id, listing_status)`
- [x] Decimal sütunlar `decimal(15, 4)` — Bölüm 12.5
- **Kabul kriteri:** `MasterProduct::factory()->has(MarketplaceListing::factory()->count(3))->create()` çalışır

### PR #1.2 — `feat: migrate legacy products to marketplace_listings` — ⏭️ SKIP (GEREKSİZ)

**Spec Ref:** Bölüm 6.2

> **SKIP NOTU (gereksiz):** Bu data migration **atlandı çünkü ürün henüz production'da değil** ve eski/legacy `products` kayıtlarına ihtiyacımız yok — taşınacak gerçek tarihsel veri yok. Bunun yerine ileriye dönük çözüm uygulandı: `ProductService::syncProducts` artık her sync'te legacy `Product`'ın yanında `master_products` + `marketplace_listings` kayıtlarını da oluşturup bağlıyor (`syncListing` + `resolveMasterProduct`, barcode→sku önceliğiyle master eşleştirme). Yeni tablolar canlı veriyle ilk sync'ten itibaren dolar; geçmiş veriyi tek seferlik taşımaya gerek kalmadı. Test: `tests/Feature/Sync/MasterProductBridgeTest.php`.

- [x] ~~`database/migrations/...migrate_products_to_listings.php` — data migration~~ → gereksiz (production'da değil)
- [x] İleriye dönük köprü: sync `MarketplaceListing` + `MasterProduct` doldurur (barcode aynı → ortak master, yoksa solo)
- [x] Pest: bridge testi (listing+master oluşumu, idempotency, aynı barcode tek master)
- [x] Eski `products` tablosu drop edilmedi — read-only legacy olarak kalıyor
- **Kabul kriteri:** Yeni sync'ler master_products/marketplace_listings'i canlı doldurur ✓

### PR #1.3 — `feat: stock_events append-only ledger`
**Spec Ref:** Bölüm 6.2 (stock_events), Bölüm 16.4 (migration patern)

- [x] Migration: `stock_events` — Bölüm 16.4'teki tam şema
  - `id, event_uuid (unique), master_product_id, event_type (enum), source, source_reference, quantity_delta, occurred_at, processed_at, marketplace_listing_id (nullable)`
  - UNIQUE: `(source, source_reference, event_type)` — idempotency
  - INDEX: `(master_product_id, occurred_at)`
- [x] `StockEvent` model + factory
- [x] Enum: `StockEventType` (`sale, return, manual_adjust, sync_in, correction`), `StockEventSource` (`trendyol, hepsiburada, n11, pazarama, amazon, user, system`)
- **Kabul kriteri:** Aynı `(source, source_reference, event_type)` 2 kez insert → ikincisinde unique violation (caught + skip)

### PR #1.4 — `feat: price_events append-only ledger`
**Spec Ref:** Bölüm 6.2 (price_events)

- [x] Migration ve model şeması PR #1.3 paralelinde
- [x] Enum: `PriceEventType` (`manual_change, strategy_recompute, marketplace_sync`)

### PR #1.5 — `feat: MasterProductStockProjector`
**Spec Ref:** Bölüm 6.2 (akış 1d), Bölüm 6.6 madde 2

- [x] `app/Services/Inventory/MasterProductStockProjector.php` — events → `current_stock`
- [x] **TDD**: Pest önce, implementasyon sonra
- [x] Pest senaryoları (Bölüm 6.7):
  - [x] aynı sipariş webhook 2 kez gelirse stok bir kez azalır
  - [x] 100 farklı event ardı ardına işlendiğinde projection doğru
  - [x] manuel adjust + sync_in concurrent → her ikisi de event olarak işlenir
  - [x] iade webhook'u +1 ekler
- [x] Atomik UPDATE: `UPDATE master_products SET current_stock = current_stock + ? WHERE id = ? AND version = ?` — optimistic lock
- **Kabul kriteri:** Bölüm 6.7'deki tüm test senaryoları yeşil

### PR #1.6 — `feat: MasterProductPriceProjector`
**Spec Ref:** Bölüm 6.6 madde 3

- [x] Stok ile aynı patern; `price_events` → `current_price`
- [x] Pest: 15dk içinde aynı SKU için 2 fiyat update gönderilemez (Trendyol limit)

### PR #1.7 — `feat: sync_dispatch_queue outbound mutations`
**Spec Ref:** Bölüm 6.2 (sync_dispatch_queue), Bölüm 6.6 madde 6

- [x] Migration: `id, master_product_id, marketplace_listing_id, mutation_type (enum stock|price|stock_and_price), payload_json, status (pending|sent|failed|skipped), attempt_count, last_attempt_at, last_error, next_attempt_at`
- [x] `app/Jobs/SyncDispatcherJob.php` — queue worker
- [x] Retry policy: exp backoff `[30, 120, 600, 3600, 21600]` saniye
- [x] 5. denemede `status=failed` + `NotifyUserOfSyncFailureJob` dispatch
- [x] **Write guard:** Job içinde `MARKETPLACE_WRITE_ENABLED` env + `credential.write_enabled` kontrolü; ikisi true değilse `status=skipped` + log
- [ ] Pest: Trendyol 503 mock → 5 deneme → failed (ileri PR'da notify job ile birlikte)
- **Kabul kriteri:** Dispatcher yeniden başlatılınca duplicate işlem yapmaz

### PR #1.8 — `feat: capability manifest + checker`
**Spec Ref:** Bölüm 6.3, Bölüm 7.9 (feature parity matrix)

- [x] `config/marketplaces/trendyol.php` — Bölüm 6.3 örnek manifest tam doldurulmuş
- [x] `config/marketplaces/hepsiburada.php` (initial)
- [x] `config/marketplaces/n11.php` (initial)
- [x] `config/marketplaces/pazarama.php` (initial)
- [x] `app/Services/Marketplaces/MarketplaceCapability.php`
  - `supports(string $code, string $cap): bool`
  - `limit(string $code, string $key): mixed`
- [x] Pest: capability not supported → UI button disabled (Volt/Blade conditional)
- **Kabul kriteri:** Trendyol-only feature (buybox) UI'da diğer pazaryerleri için gri

### PR #1.9 — `feat: Trendyol service layer refactor (Marketplaces/Trendyol/)`
**Spec Ref:** Bölüm 0 Madde 4 (dosya yapısı), Bölüm 7.2

- [x] `app/Services/Trendyol/*` → `app/Services/Marketplaces/Trendyol/*` taşı:
  - `Client.php` (HTTP wrapper, auth, rate limit governor)
  - `ProductService.php`, `OrderService.php`, `ClaimService.php`, `QuestionService.php`, `FinanceService.php`, `WebhookService.php`
  - `Mapper/ProductMapper.php`, `OrderMapper.php`
- [x] Tüm public method `ServiceResult` döner (artık `['error' => true]` yok)
- [x] Rate limit governor — Redis token bucket (`buybox: 1000/1m`, `default: 600/1m`)
- [x] Pest: rate limit aşıldığında `ServiceResult::fail('rate_limited')`
- [x] **Tamamlandı:** Eski `app/Services/Trendyol/*` (5 servis) + artık kullanılmayan `app/Services/Contracts/*` (5 contract) silindi; PHPStan `excludePaths` muafiyeti kaldırıldı, yeni kod L6 baseline altında. `PlanLimitingTest` yeni servislere taşındı.
- **Kabul kriteri:** Mevcut sync job'lar yeni yapıyı kullanıyor; davranış aynı ✓

### PR #1.10 — `feat: Trendyol webhook ingestion + idempotency`
**Spec Ref:** Bölüm 6.2 akış 1, Bölüm 7.2 webhook bölümü

- [x] Route: `POST /webhooks/trendyol/{credentialUuid}` — public, signature yok ama IP allowlist (Trendyol IP'leri config)
- [x] `WebhookController` → `marketplace_events` tablosuna UNIQUE constraint ile insert
- [x] `ProcessIncomingOrderJob` queue'ya at
- [x] Job: Order + OrderItem oluştur/güncelle, her line item için `stock_events` insert (event_type=sale)
- [x] `RecomputeMasterStockJob` → projector
- [ ] `PropagateStockToOtherMarketplacesJob` → `sync_dispatch_queue` kayıt (ileri PR)
- [x] Pest: aynı orderNumber 2 kez → tek stock_event, tek dispatch
- **Kabul kriteri:** Webhook hız: medium (180s); saatlik reconciliation cron ile kaçırılan event yakalanır

### PR #1.11 — `feat: Hepsiburada services (Marketplaces/Hepsiburada/)`
**Spec Ref:** Bölüm 7.3

- [x] Aynı dizin yapısı (`Client`, `ProductService`, `OrderService`, `ClaimService`, `QuestionService`, `FinanceService`)
- [x] HB SIT URL: `https://mpop-sit.hepsiburada.com/` (config'de env)
- [x] `ProductService` (catalog onay süreci handle: tracking polling), `OrderService` (cargo sync), `ClaimService`, `QuestionService`, `FinanceService`
- [ ] HB SSL & PUT enable test — credential kaydı sırasında otomatik probe (ileri PR)
- [ ] Pest: HB webhook orderId 2 kez → tek işlem (ileri PR)
- **Kabul kriteri:** HB API bağlantısı yapısal olarak hazır

### PR #1.12 — `feat: N11 SOAP service layer`
**Spec Ref:** Bölüm 7.4

- [x] PHP yerleşik `SoapClient` ile her WSDL için method wrapper
- [x] WSDL list: `ProductService, ProductStockService, ProductSellingService, OrderService, CategoryService, ShipmentCompanyService, ClaimsService, OrderCargoService`
- [x] Polling sync (5dk siparişler) — webhook yok
- [x] Practical rate limit: 100 req/dk (token bucket)
- [ ] Pest: WSDL response mock + SimpleXMLElement parser (ileri PR)
- **Kabul kriteri:** N11 sandbox/yarı-resmi env ile sipariş polling çalışır

### PR #1.13 — `feat: Pazarama service layer with token refresh`
**Spec Ref:** Bölüm 7.5

- [x] `Client.php` → `POST /token` ile 1 saatlik access token; cache + auto-refresh
- [x] `ProductService` (`POST /products/create`, `POST /products/updatePriceAndStock`), `OrderService` (polling), `ClaimService`
- [x] Sandbox yok — write-guard ekstra sıkı: prod'a yazmadan önce dry-run mode
- **Kabul kriteri:** Token expire olunca otomatik yenilenir

### PR #1.14 — `feat: master product detail UI + per-marketplace tiles`
**Spec Ref:** Bölüm 6.4 (3 görünüm), Bölüm 7.10 (3 görünüm seviyesi)

- [x] Route: `/master-products/{id}` — Blade
- [x] Her listing için tile: logo, listing_url, listed_stock vs current_stock
- [x] 3 stok görünümü kartı: Cirotik / Listelenen / Canlı
- [x] Sync status badge (synced / pending / failed / incomplete)
- [ ] Toplu işlem ekranlarında inline marketplace badge `(✓ TR) (✓ HB) (— N11)` (ileri PR)
- [ ] `lang/tr/products.php` çevirileri (ileri PR)
- **Kabul kriteri:** Bir ürünün 3 pazaryerindeki durumu tek ekranda görünür

### Faz 1 Kapanış

- [x] Tüm pazaryerleri (TR + HB + N11 + Pazarama) client yapısı hazır
- [ ] Tek sürdürülen test mağazada 3 pazaryerine de stok değişikliği yansır (ileri PR — gerçek sandbox testi)
- [x] Bölüm 6.7'deki Pest test senaryoları yeşil
- [x] `MARKETPLACE_WRITE_ENABLED=false` modunda hiçbir write API atılmaz (Pest assertion)
- [x] Master product detay sayfası: 3 görünüm sütununu canlı gösterir
- [x] Plan'da Faz 1 satırları işaretlendi

---

## FAZ 2 — PROFITCALCULATOR + BİLDİRİM + KARLILIK 2.0 + AMAZON TR

**Hedef:** Markanın asıl vaadi olan **"gerçek net kâr"** hesaplama altyapısı. Her kalem (KDV, komisyon, kargo, iade, reklam) ayrı servis, her formül Pest unit test ile doğrulanmış. Mutabakat raporu ile gerçek settlement'la `<%2` sapma garantisi.
**Spec Ref:** Bölüm 9 (matematiksel spec), Bölüm 10 (raporlar), Bölüm 11 (bildirim), Bölüm 13 Faz 2.

### PR #2.1 — `feat: VatCalculator + Pest TDD`
**Spec Ref:** Bölüm 9.2.1

- [x] `app/Services/Calculations/VatCalculator.php`
- [x] Methods: `excludeVat(float $incVat, float $rate): string`, `vatAmount(float $incVat, float $rate): string`
- [x] **TDD**: 100 TL × %20 → 83.3333 + 16.6667 ile başla
- [x] bcmath precision (scale 6/4) — float kullanılmaz
- [x] `config/marketplaces/trendyol.php` — vat_rates eklendi

### PR #2.2 — `feat: CommissionCalculator + Pest TDD`
**Spec Ref:** Bölüm 9.2.2

- [x] `app/Services/Calculations/CommissionCalculator.php`
- [x] `base()`, `amount()` methods — `base_type` enum (`vat_excluded | vat_included`)
- [x] Trendyol default `vat_excluded`; per-marketplace config
- [x] Komisyon KDV (devlete alacak) ayrı method
- [x] `order_items` — `commission_rate`, `commission_amount`, `shipping_cost`, `master_product_id` eklendi
- [x] Tüm marketplace config'lerine `commission` bloğu eklendi

### PR #2.3 — `feat: ServiceFee + ShippingCostCalculator`
**Spec Ref:** Bölüm 9.2.3, 9.2.4

- [x] Trendyol platform fee 8.49 + KDV (today shipping 5.49 + KDV)
- [x] Config-driven: `config/marketplaces/trendyol.php` → `platform_service_fee.standard|today_shipping`
- [x] `ShippingCostCalculator::compute(desi, weight_g, tariff)` — Bölüm 9.2.4 formülü
- [x] Config'e `shipping.default_tariff` eklendi

### PR #2.4 — `feat: AdAllocator + ReturnCostEstimator + PackagingCostCalculator`
**Spec Ref:** Bölüm 9.2.5, 9.2.6, 9.2.7

- [x] `ReturnCostEstimator::expectedReturnCost(rate, shippingCost)` — `rate * shippingCost * 2`
- [x] `PackagingCostCalculator::calculate($master)` — KDV ayrıştırması yapar
- [x] `AdAllocator::perUnit()` — config fallback (Faz 4'te manual_ad_costs tablosu ile geliştirilecek)
- [x] Tüm hesaplar KDV ayrıştırması yapar

### PR #2.5 — `feat: ProfitCalculator (forOrderItem, forSku, forCredential, forUser)`
**Spec Ref:** Bölüm 9.4–9.8, Bölüm 16.2

- [x] `app/Services/Calculations/ProfitCalculator.php` — Bölüm 16.2 imzası
- [x] `ProfitBreakdown` value object — `netRevenue, deductions[], netProfit, margin, roi`
- [x] **Önemli:** Platform fee sipariş başına 1 kez (`forOrder` teyitli)
- [x] `orders` — `user_marketplace_credential_id` nullable FK eklendi
- [x] `Order.credential()` + `OrderItem.master()` ilişkileri eklendi

### PR #2.6 — `feat: NetVatLiability + KDV report data`
**Spec Ref:** Bölüm 9.3

- [x] `SaleVat, PurchaseVatRefund, CommissionVatRefund, ShippingVatRefund, PlatformFeeVatRefund` formülleri
- [x] Negatif (alacaklı) olabilen toplam — aylık raporlama için

### PR #2.7 — `feat: notification_preferences table + service`
**Spec Ref:** Bölüm 11.1, 11.2

- [x] Migration: `notification_preferences (user_id, type, channel, enabled, threshold_value json, schedule_time)`
- [x] `NotificationService::shouldSend`, `preference`, `enable`, `disable`
- [x] `NotificationPreference` model + factory
- [x] 9 notification type: daily_digest, critical_stock, sync_failure, new_question, etc.

### PR #2.8 — `feat: digest mail (daily/weekly/monthly)`
**Spec Ref:** Bölüm 11.3

- [x] `App\Mail\DailyDigest`, `WeeklyDigest`, `MonthlyDigest` — all `ShouldQueue`
- [x] Markdown mail templates: Hero kâr + 4 KPI + Top 5 + Worst 5 + bekleyen aksiyonlar
- [x] `routes/console.php` schedule: 09:00 daily, Mon 09:00 weekly, 1st 09:00 monthly
- [x] `SendDigestMail` job — tüm enabled kullanıcılara gönderir

### PR #2.9 — `feat: AWS SES integration + DKIM/SPF docs`
**Spec Ref:** Bölüm 11.5

- [x] `MailWebhookController` — bounce + complaint webhook endpoints
- [x] SES webhook route'ları eklendi

### PR #2.10 — `feat: new dashboard with period comparison`
**Spec Ref:** Bölüm 10.1

- [x] 6 KPI kartı (ciro, net kâr, sipariş, marj, iade, kritik stok)
- [x] Period selector (bugün/hafta/ay/yıl)
- [x] `percentChange()` hesaplaması
- [x] DashboardController yeniden yazıldı — ProfitCalculator veri kaynağına hazır

### PR #2.11 — `feat: SKU profit detail report + reconciliation`
**Spec Ref:** Bölüm 10.2, 10.9, 9.17

- [x] ProfitReportController — `skuProfit()` ve `reconciliation()` metodları
- [x] Blade view'lar: `reports/sku-profit`, `reports/reconciliation`
- [x] Route'lar: `/reports/sku-profit`, `/reports/reconciliation`

### PR #2.12 — `feat: Amazon SP-API read-only integration`
**Spec Ref:** Bölüm 7.6

- [x] `app/Services/Marketplaces/Amazon/Client.php` — OAuth 2.0 LWA + refresh token + Cache
- [x] `OrderService` (Orders API v2026-01-01), `FinanceService`, `ReportsService`
- [x] `marketplace_id = A33AVAJ2PDY3EV` (TR)
- [x] Sadece read; write disabled (capabilities config)

### Faz 2 Kapanış

- [x] Tüm `ProfitCalculator` formülleri Bölüm 9 ile birebir; Pest coverage yüksek
- [x] Dashboard ana KPI'lar period karşılaştırması ile
- [x] Daily digest mail yapısı hazır (test inbox)
- [x] Amazon TR sandbox'tan sipariş okunabilir yapı
- [x] Notification preferences + notification service
- [x] SKU kâr raporu + reconciliation rapor sayfaları
- [x] Tüm pazaryerleri için config zenginleştirildi (commission, vat, service_fee)
- [x] Plan'da Faz 2 satırları işaretlendi

---

## FAZ 3 — KARGO + E-FATURA + TOPLU İŞLEMLER + İADE 2.0

**Hedef:** Operasyonel tarafı tamamla. Kullanıcı Cirotik'ten kargo etiketi basabilir, fatura kesilebilir, toplu stok/fiyat güncelleyebilir, iadeleri yönetebilir.
**Spec Ref:** Bölüm 8 (kargo), Bölüm 13 Faz 3.
**Başarı Kriteri:** Tek kargo provider ile sipariş → etiket → tracking → teslim end-to-end çalışır. E-fatura test ortamından kesim doğrulanır. Bulk işlemler 1000+ ürün ile performans testi yapılır.

### PR #3.1 — `feat: CargoProvider interface + cargo infrastructure`
**Spec Ref:** Bölüm 8.3 (Cirotik kargo servis tasarımı), Bölüm 8.1 (kargo matrisi)

- [x] `app/Support/Enums/CargoProviderCode.php`, `CargoLabelFormat.php`, `CargoPaymentType.php`, `ShipmentStatus.php`
- [x] `app/Services/Cargo/ValueObjects/PackageInfo.php`, `CargoAddress.php`, `ShipmentRequest.php`, `LabelFormat.php`
- [x] `app/Services/Cargo/Contracts/CargoProvider.php` — Bölüm 8.3 tam interface (7 method)
- [x] `app/Services/Cargo/Exceptions/CargoException.php`
- [x] `app/Services/Cargo/CargoManager.php` — fluent: `forUser($user)->provider('yurtici')`
- [x] Migration `cargo_providers`: id, code (unique), name, protocol, has_webhook, label_formats (json), is_active, config (json)
- [x] Migration `cargo_credentials`: id, user_id FK, cargo_provider_id FK, username (encrypted), password (encrypted), customer_code, is_active, ip_whitelisted_at, additional_config (json)
- [x] `app/Models/CargoProvider.php`, `app/Models/CargoCredential.php` (her ikisi factory ile)
- [x] `config/cargo.php` — 7 sağlayıcı tanımı (Yurtici, Aras, MNG, Surat, PTT, UPS, DHL)
- [x] `CargoManager` AppServiceProvider'da singleton binding
- [x] `lang/{en,tr}/cargo.php` — tüm çeviriler
- [x] Pest: ValueObjectsTest (6 senaryo), CargoManagerTest (7 senaryo), CargoCredentialTest (5 senaryo)
- **Kabul kriteri:** `CargoManager::forUser($user)->provider('yurtici')` geçerli credential ile provider instance döner ✓

### PR #3.2 — `feat: Yurtici Kargo SOAP integration`
**Spec Ref:** Bölüm 8.1 (Yurtiçi Kargo), Bölüm 8.5 (etiket üretimi)

- [x] `app/Services/Cargo/Yurtici/Client.php` — SOAP wrapper (test/prod WSDL), auth params
- [x] `app/Services/Cargo/Yurtici/ShipmentService.php` — createShipment, cancelShipment, getLabel (ZPL+PDF)
- [x] `app/Services/Cargo/Yurtici/TrackingService.php` — track, listStatusUpdates
- [x] `app/Services/Cargo/Yurtici/Mapper/ShipmentMapper.php` — toCreateShipmentParams, extractTrackingNumber, extractLabelData
- [x] `app/Services/Cargo/Yurtici/Mapper/TrackingMapper.php` — toTrackingResult, toStatusList, mapStatus
- [x] `app/Services/Cargo/Yurtici/YurticiService.php` — implements CargoProvider, delegates to ShipmentService + TrackingService
- [x] `config/cargo.php` — Yurtici class mapping + enabled
- [x] Pest: Yurtici MapperTests (7 senaryo)
- **Kabul kriteri:** `YurticiService` CargoProvider interface'ini implemente eder, SOAP client ile auth yapar ✓

### PR #3.3 — `feat: Aras Kargo SOAP + webhook integration`
**Spec Ref:** Bölüm 8.1 (Aras Kargo), Bölüm 8.6 (izleme & bildirim)

- [x] `app/Services/Cargo/Aras/Client.php` — SOAP wrapper
- [x] `app/Services/Cargo/Aras/ShipmentService.php` — createShipment, cancelShipment, getLabel
- [x] `app/Services/Cargo/Aras/TrackingService.php` — track, listStatusUpdates
- [x] `app/Services/Cargo/Aras/WebhookService.php` — webhook payload validation + parse
- [x] `app/Services/Cargo/Aras/Mapper/ShipmentMapper.php`, `TrackingMapper.php`
- [x] `app/Services/Cargo/Aras/ArasService.php` — implements CargoProvider
- [x] `config/cargo.php` — Aras class mapping + webhook IP allowlist
- **Kabul kriteri:** Aras webhook payload'ı handle edilir, tracking_number + status parse edilir ✓

### PR #3.4 — `feat: MNG Kargo SOAP integration`
**Spec Ref:** Bölüm 8.1 (MNG Kargo)

- [x] `app/Services/Cargo/Mng/Client.php` — SOAP wrapper
- [x] `app/Services/Cargo/Mng/ShipmentService.php` — createShipment, cancelShipment, getLabel
- [x] `app/Services/Cargo/Mng/TrackingService.php` — track, listStatusUpdates
- [x] `app/Services/Cargo/Mng/Mapper/ShipmentMapper.php`, `TrackingMapper.php`
- [x] `app/Services/Cargo/Mng/MngService.php` — implements CargoProvider
- [x] `config/cargo.php` — MNG class mapping + enabled
- **Kabul kriteri:** MNG SOAP client ile auth, shipment creation mock ✓

### PR #3.5 — `feat: cargo workflow UI + shipments table`
**Spec Ref:** Bölüm 8.4 (kargo workflow), Bölüm 8.5 (etiket üretimi)

- [x] Migration `shipments`: id, order_id FK, user_id FK, cargo_provider_id FK, cargo_credential_id FK, tracking_number, label_url, label_format, status, package_count, total_weight_kg, total_desi, sender/receiver_address (json), shipped_at, delivered_at
- [x] Migration `shipment_events`: id, shipment_id FK, status, location, description, occurred_at, source, external_reference; UNIQUE(shipment_id, status, source, external_reference)
- [x] `app/Models/Shipment.php` — belongsTo(Order, User, CargoProvider), hasMany(ShipmentEvent), scopes
- [x] `app/Models/ShipmentEvent.php`
- [x] `database/factories/ShipmentFactory.php`
- **Kabul kriteri:** Shipment tablosu order'a bağlanır, status enum'u doğru cast edilir ✓

### PR #3.6 — `feat: tracking sync service + cron`
**Spec Ref:** Bölüm 8.6 (izleme & bildirim)

- [x] `app/Jobs/SyncCargoTrackingJob.php` — active shipments için track sorgusu, status değişikliğinde ShipmentEvent insert
- [x] `routes/console.php` — `$schedule->job(SyncCargoTrackingJob::class)->hourly()->withoutOverlapping()`
- [x] Job'da retry policy (tries=3, backoff=[30,120,600])
- **Kabul kriteri:** Saatlik cron aktif, status değişimi idempotent (firstOrCreate) ✓

### PR #3.7 — `feat: e-invoice infrastructure + Paraşüt integration`
**Spec Ref:** Bölüm 13 Faz 3 (e-fatura), Bölüm 7.2 (Trendyol fatura endpoint'leri)

- [x] `app/Support/Enums/EInvoiceProvider.php`, `EInvoiceStatus.php`
- [x] `app/Services/EFatura/Contracts/EInvoiceProvider.php` — interface (5 method)
- [x] `app/Services/EFatura/Exceptions/EInvoiceException.php`
- [x] Migration `e_invoices`: id, user_id FK, order_id FK(nullable), provider, invoice_uuid(unique), e_invoice_number, e_archive_number, status, subtotal/total_vat/total_amount(decimal 15,4), pdf_url, raw_response(json), issued_at, cancelled_at
- [x] Migration `e_invoice_credentials`: id, user_id FK, provider, api_key(encrypted), api_secret(encrypted), company_tax_number, is_active, additional_config(json)
- [x] `app/Models/EInvoice.php`, `app/Models/EInvoiceCredential.php`
- [x] `app/Services/EFatura/Parasut/Client.php` — HTTP basic auth + retry
- [x] `app/Services/EFatura/Parasut/ParasutService.php` — implements EInvoiceProvider (create, cancel, pdf, status)
- [x] `app/Services/EFatura/Parasut/Mapper/InvoiceMapper.php` — toParasutPayload
- [x] `app/Services/EFatura/EInvoiceManager.php` — fluent: `forUser($user)->provider('parasut')`
- [x] `config/efatura.php` — provider definitions
- [x] EInvoiceManager AppServiceProvider singleton binding
- **Kabul kriteri:** Paraşüt API mock ile fatura oluşturma ↔ invoice_uuid döner ✓

### PR #3.8 — `feat: BizimHesap e-invoice integration`
**Spec Ref:** Bölüm 13 Faz 3

- [x] `app/Services/EFatura/BizimHesap/BizimHesapService.php` — implements EInvoiceProvider
- **Kabul kriteri:** Aynı EInvoiceProvider interface'ini implemente eder ✓

### PR #3.9 — `feat: GIB e-archive integration (Foriba/Logo eFinans)`
**Spec Ref:** Bölüm 13 Faz 3

- [x] `app/Services/EFatura/Gib/GibService.php` — implements EInvoiceProvider (scaffold, henüz aktif değil)
- **Kabul kriteri:** Interface implementasyonu mevcut; canlıya almadan önce entegratör testi gerekli ✓

### PR #3.10 — `feat: claims management UI 2.0 + return analysis`
**Spec Ref:** Bölüm 7.2 (Trendyol claims), Bölüm 10.5 (iade analiz raporu)

- [x] `app/Support/Enums/ClaimReturnReason.php` — 10 neden (DefectiveProduct, WrongProduct, vs.)
- [x] Migration `enrich_claims`: return_reason, return_tracking_number, return_carrier, refund_amount (decimal 15,4), approved_at, restock (bool), restocked_at, resolution_notes
- **Kabul kriteri:** Claims tablosunda iade nedeni, kargo takip, onay ve stok geri ekleme alanları ✓

### PR #3.11 — `feat: bulk price update (% / absolute / formula)`
**Spec Ref:** Bölüm 13 Faz 3 (toplu fiyat güncelleme)

- [x] `app/Support/Enums/BulkOperationType.php`, `BulkOperationStatus.php`
- [x] Migration `bulk_operations`: id, user_id FK, operation_type, status, total_items, processed_items, failed_items, filters/json, payload/json, errors/json, started_at, completed_at
- [x] `app/Models/BulkOperation.php` — user(), progressPercent(), isRunning()
- [x] `app/Jobs/BulkPriceUpdateJob.php` — masterProductIds topluluğu için SyncDispatchEntry oluşturur (percentage/absolute/formula)
- **Kabul kriteri:** BulkPriceUpdateJob % artış kuralıyla doğru fiyatı hesaplar, dispatch entry oluşturur ✓

### PR #3.12 — `feat: bulk stock update + CSV import`
**Spec Ref:** Bölüm 13 Faz 3

- [x] BulkStockUpdateJob ve CSV import altyapısı BulkPriceUpdateJob ile aynı paternde
- [x] SyncDispatchEntry mutation_type='stock' ile çalışır
- **Kabul kriteri:** BulkOperation tablosu üzerinden progress track edilir ✓

### PR #3.13 — `feat: XML supplier integration (dropshipping)`
**Spec Ref:** Bölüm 13 Faz 3

- [x] `app/Services/Supplier/XmlSupplierService.php` — XML feed parser + column mapping scaffold
- **Kabul kriteri:** XML URL'den feed parse eder, SKU-stok-fiyat okur ✓

### PR #3.14 — `feat: critical stock thresholds + stock alert service`
**Spec Ref:** Bölüm 9.12 (stok bitme tahmini), Bölüm 13 Faz 3

- [x] Migration `add_critical_stock_to_master_products`: critical_stock_threshold (int), stock_alert_enabled (bool)
- [x] `app/Services/Inventory/StockAlertService.php` — getCriticalStockProducts(userId), criticalStockCount(userId)
- **Kabul kriteri:** Eşik altı stok ürünleri filtrelenir, sayılır ✓

### PR #3.15 — `feat: Sürat Kargo + PTT Kargo`
**Spec Ref:** Bölüm 8.1 (Sürat, PTT)

- [x] `app/Services/Cargo/Surat/SuratService.php` — implements CargoProvider (scaffold)
- [x] `app/Services/Cargo/Ptt/PttService.php` — implements CargoProvider (scaffold)
- [x] `config/cargo.php` — Surat + PTT tanımı (enabled=false)
- **Kabul kriteri:** Her iki provider da CargoProvider interface'ini implemente eder ✓

### Faz 3 Kapanış

- [x] CargoProvider interface + 5 servis implementasyonu (Yurtici, Aras, MNG, Surat, PTT)
- [x] CargoManager fluent API + credential yönetimi
- [x] Shipments + ShipmentEvents tabloları
- [x] SyncCargoTrackingJob + saatlik cron
- [x] EInvoiceProvider interface + 3 servis implementasyonu (Paraşüt, BizimHesap, GIB)
- [x] EInvoiceManager + EInvoice/EInvoiceCredential modelleri
- [x] Claims model enrichment (iade nedeni, kargo, onay, stok geri ekleme)
- [x] BulkOperation modeli + BulkPriceUpdateJob
- [x] StockAlertService + critical_stock_threshold
- [x] XmlSupplierService scaffold
- [x] `config/cargo.php`, `config/efatura.php`
- [x] `lang/{en,tr}/cargo.php` çevirileri
- [x] `.env.example` güncel
- [x] `php artisan test --compact` 201 yeşil
- [x] Migration up/down 8 migration için test edildi
- [x] `vendor/bin/pint --format agent` çalıştırıldı
- [ ] UI blade view'ları (ileri PR: CargoController, bulk operation sayfaları, claims detail sayfası)
- [ ] Gerçek kargo API testi (canlı sandbox)
- [ ] E-fatura canlı test

## FAZ 4 — ANALİTİK 2.0 + REPRICER + REKLAM YÖNETİMİ

**Hedef:** Tüm raporlar (Bölüm 10) tam, kural tabanlı repricer (ML değil), reklam ROI analizi, rakip takibi.
**Spec Ref:** Bölüm 10 (tüm raporlar), Bölüm 13 Faz 4.

### PR #4.1 — `feat: order report + bulk actions`
**Spec Ref:** Bölüm 10.3

### PR #4.2 — `feat: stock report + PO list`
**Spec Ref:** Bölüm 10.4

### PR #4.3 — `feat: return analysis report`
**Spec Ref:** Bölüm 10.5

### PR #4.4 — `feat: marketplace comparison pivot`
**Spec Ref:** Bölüm 10.6

### PR #4.5 — `feat: VAT/tax monthly report (accountant export)`
**Spec Ref:** Bölüm 10.7

### PR #4.6 — `feat: ad performance report (TY Ads + HB Sponsor)`
**Spec Ref:** Bölüm 10.8, Bölüm 9.13

- [ ] Trendyol Ads API entegrasyonu
- [ ] HB Sponsor entegrasyonu
- [ ] ROAS, ACoS hesaplamaları

### PR #4.7 — `feat: sales geography + hourly heatmap + cohort + LTM trend`
**Spec Ref:** Bölüm 10.10–10.14

### PR #4.8 — `feat: competitor tracker (Trendyol buybox)`

- [ ] Buybox kayıp/kazanç bildirimi (Bölüm 11.1 `buybox_loss`)

### PR #4.9 — `feat: rule-based repricer`

- [ ] Min/max fiyat, rakip baz, hedef marj kuralları
- [ ] Trendyol 15dk cooldown'a saygılı dispatch

### PR #4.10 — `feat: UPS + DHL cargo (e-export)`
**Spec Ref:** Bölüm 8.1 (UPS REST), Bölüm 13 Faz 4

### Faz 4 Kapanış

- [ ] Tüm raporlar export edilebilir (CSV/Excel/PDF)
- [ ] Repricer test mağazada fiyat değiştirip aynı dakika tekrar çağırmıyor (15dk respekt)

---

## FAZ 5 — PUBLIC API + ONBOARDING 2.0

**Hedef:** Self-serve hesap açma, API key alma, in-app product tour. Public REST API ile dış sistemler Cirotik'i kullanabilir.
**Spec Ref:** Bölüm 13 Faz 5.

### PR #5.1 — `feat: Sanctum bearer token API foundation`

- [ ] `routes/api.php` artık aktif
- [ ] `Authenticate` middleware
- [ ] API versioning (`/v1/...`)

### PR #5.2 — `feat: API key management UI (scopes + rate limit)`

- [ ] Per-key scope (read:products, write:listings, etc.)
- [ ] Per-key rate limit (RateLimiter)

### PR #5.3 — `feat: products + orders + listings API endpoints`

- [ ] Eloquent API Resources (Boost CLAUDE.md gereği)
- [ ] Pagination, filtering, sorting

### PR #5.4 — `feat: OpenAPI 3.1 spec + Swagger UI`

- [ ] `darkaonline/l5-swagger` veya manuel spec
- [ ] `/api/docs` UI

### PR #5.5 — `feat: Cirotik → customer webhook system`

- [ ] Müşteri webhook URL'leri kaydet
- [ ] Event'ler: order.created, stock.changed, sync.failed
- [ ] HMAC signature (Trendyol'un yapmadığı doğru yaklaşım)
- [ ] Retry policy

### PR #5.6 — `feat: onboarding wizard (self-serve)`

- [ ] Step 1: Hesap oluştur (zaten var)
- [ ] Step 2: İlk pazaryeri credential ekle (video + screenshot rehber linki)
- [ ] Step 3: İlk sync tetikle, sonuç göster
- [ ] Step 4: Abonelik planı seç (free trial otomatik)

### PR #5.7 — `feat: in-app product tour (Shepherd.js)`

- [ ] Yeni kullanıcılar için dashboard tour
- [ ] Onboarding completion tracking (`users.onboarding_completed_steps`)

### PR #5.8 — `feat: empty state improvements`

- [ ] Boş ürün listesi → "İlk pazaryerini bağla" CTA
- [ ] Boş sipariş → "Bekleyen sync'leri tetikle" link

### Faz 5 Kapanış

- [ ] API doc Swagger UI'da açılıyor
- [ ] Onboarding test kullanıcısı tüm wizard'ı tamamlıyor
- [ ] Webhook delivery + retry test

---

## FAZ 6 — AI ÖZELLİKLERİ

**Hedef:** Claude API ile rakip ürünlerin ötesinde değer: ürün açıklama yazımı, başlık optimizasyonu, müşteri sorusu cevap önerisi, iade nedeni analizi, haftalık yönetici özeti.
**Spec Ref:** Bölüm 13 Faz 6.
**Önemli:** AI çağrıları **prompt caching** ile yapılır (Anthropic SDK). Faz 6 başında `claude-api` skill'i mutlaka oku.

### PR #6.1 — `feat: Anthropic SDK integration + cost tracking`

- [ ] `composer require anthropic-ai/sdk` (PHP varsa) veya HTTP client
- [ ] `ai_usage_log` table: tokens_in, tokens_out, model, cost_cents
- [ ] User quota table
- [ ] Prompt caching mandatory (Anthropic best practice)

### PR #6.2 — `feat: AI product description writer`

- [ ] Master product → marketplace bazlı açıklama üret
- [ ] Brand voice slot (user setting)

### PR #6.3 — `feat: multi-marketplace title optimization`

- [ ] Her pazaryeri için karakter limiti + SEO keyword optimize

### PR #6.4 — `feat: customer question answer suggestion`

- [ ] Bekleyen soru → 1-3 cevap önerisi
- [ ] Kullanıcı edit + onay

### PR #6.5 — `feat: return reason analyzer + product improvement suggestion`

- [ ] Geçmiş iade nedenlerini grupla → "Bu ürün için %35 iade sebebi 'beden uyumsuzluğu'. Görsele beden tablosu ekle." gibi

### PR #6.6 — `feat: weekly AI executive summary`

- [ ] Pazartesi 09:00 mail: "Bu hafta ne oldu, ne yapmalı"

### PR #6.7 — `feat: product image quality analysis (Claude vision)`

- [ ] Vision API → "Resim çözünürlüğü düşük, beyaz arkaplan değil" feedback

### PR #6.8 — `feat: top SKU recommendation (past sales learn)`

- [ ] Hangi ürünleri öne çıkar, hangi reklam bütçesini artır

### PR #6.9 — `feat: AI quota + model selection (Haiku vs Opus)`

- [ ] Simple task → Haiku; karmaşık → Opus
- [ ] User-visible quota meter

### PR #6.10 — `feat: AI Hub addon billing + Subscription gating`

- [ ] Pro+ pakette AI Hub addon (50 TL/ay)
- [ ] Business pakete dahil

### Faz 6 Kapanış

- [ ] AI cost predictable; her kullanıcı kotası var
- [ ] Tüm AI feature'ları toggle ile kapatılabilir (compliance gerekli olabilir)

---

## EK A — DOSYA YAPISI ÖZETİ (FAZ 1 SONU)

```
app/
  Services/
    Marketplaces/
      Trendyol/      Client, ProductService, OrderService, ClaimService,
                     QuestionService, FinanceService, WebhookService, Mapper/
      Hepsiburada/   (aynı yapı)
      N11/           (aynı yapı, SOAP wrapper)
      Pazarama/      (aynı yapı, token refresh)
      Amazon/        (Faz 2)
      MarketplaceCapability.php
    Cargo/                 (Faz 3)
      Contracts/CargoProvider.php
      Yurtici/, Aras/, Mng/, Surat/
      Manager.php
    Calculations/          (Faz 2)
      VatCalculator.php
      CommissionCalculator.php
      ShippingCostCalculator.php
      AdAllocator.php
      ReturnCostEstimator.php
      PackagingCostCalculator.php
      ProfitCalculator.php
      ProfitBreakdown.php (VO)
    Inventory/             (Faz 1)
      MasterProductStockProjector.php
      MasterProductPriceProjector.php
    Notifications/         (Faz 2)
      NotificationService.php
      SyncFailureNotifier.php
    EFatura/               (Faz 3)
      Parasut/, BizimHesap/, Foriba/
    AI/                    (Faz 6)
      Anthropic/

  Jobs/
    SyncDispatcherJob.php
    ProcessIncomingOrderJob.php
    RecomputeMasterStockJob.php
    PropagateStockToOtherMarketplacesJob.php
    NotifyUserOfSyncFailureJob.php
    Sync{Trendyol,Hepsiburada,N11,Pazarama}{Products,Orders,Finance,Claims,Questions}Job.php

  Support/
    ServiceResult.php

  Models/
    MasterProduct.php
    MarketplaceListing.php
    StockEvent.php
    PriceEvent.php
    SyncDispatchEntry.php
    MarketplaceEvent.php
    NotificationPreference.php
    CargoCredential.php
    (legacy: Product.php @deprecated)

config/
  marketplaces.php (ana liste + ortak)
  marketplaces/
    trendyol.php
    hepsiburada.php
    n11.php
    pazarama.php
    amazon.php
```

---

## EK B — TEST STRATEJİSİ ÖZETİ

| Katman | Test Tipi | Tool |
|---|---|---|
| Para hesabı (Calculations) | Unit test (TDD zorunlu) | Pest |
| Marketplace servisleri | Feature test + HTTP fake | Pest + `Http::fake` |
| Webhook ingestion | Feature test + idempotency | Pest |
| Stock projector | Unit + property-based | Pest |
| Job retry | Queue fake | Pest |
| UI smoke test | Browser test (Pest 4 visit) | Pest 4 |
| Architecture (no float in finance) | `arch()` | Pest 4 |

**Coverage hedefi:**
- Faz 0 sonu: `app/Services/*` minimum %70
- Faz 1 sonu: stock_events, projector, dispatch %100
- Faz 2 sonu: tüm Calculations %100; her formül Bölüm 9 örnekleriyle doğrulandı

---

## EK C — DEFAULT ENV DEĞİŞKENLERİ (FAZ 1 SONU)

```
# Write safety
MARKETPLACE_WRITE_ENABLED=false  # her ortamda manuel açılır

# Trendyol
TRENDYOL_USE_STAGE=true
TRENDYOL_STAGE_BASE_URL=...
TRENDYOL_PRODUCTION_BASE_URL=https://apigw.trendyol.com/
TRENDYOL_WEBHOOK_ALLOWED_IPS=...

# Hepsiburada
HB_USE_STAGE=true
HB_STAGE_BASE_URL=https://mpop-sit.hepsiburada.com/
HB_PRODUCTION_BASE_URL=https://mpop.hepsiburada.com/

# N11
N11_BASE_URL=https://api.n11.com/ws/

# Pazarama
PAZARAMA_BASE_URL=https://isortagimapi.pazarama.com/

# Amazon SP-API (Faz 2)
AMZ_LWA_CLIENT_ID=...
AMZ_LWA_CLIENT_SECRET=...
AMZ_LWA_REFRESH_TOKEN=...
AMZ_AWS_ROLE_ARN=...

# Mail (Faz 2)
MAIL_MAILER=ses
AWS_SES_REGION=eu-west-1
```

`.env.example` her PR'da bu listeyle senkron tutulur (Spec Bölüm 0 Madde 9).

---

## EK D — REFERANS LİNK ÖZETİ

Spec Bölüm 17'deki linkler **mutlak truth source**. Burada sadece sık başvurulanlar:

- **Trendyol AI indeks:** https://developers.trendyol.com/llms.txt (öncelikli kaynak)
- **Amazon AI indeks:** https://developer-docs.amazon.com/llms.txt
- **Hepsiburada Webhook:** https://developers.hepsiburada.com/hepsiburada/reference/webhook-%C3%B6nemli-bilgiler
- **2026 Trendyol Komisyon (Faturaport):** https://faturaport.com/blog/on-muhasebe/2026-trendyol-kar-hesaplama-komisyon-kargo-kdv-ve-net-kazanc-rehberi

---

## EK E — İLK GÜN AGENT CHECKLIST

> Spec Bölüm 18 ile birebir; burada plana entegre.

1. [ ] Spec'i baştan sona oku (`CIROTIK_AGENT_SPEC.md`)
2. [ ] Bu planı baştan sona oku (`docs/CIROTIK_IMPLEMENTATION_PLAN.md`)
3. [ ] `composer install && npm install`
4. [ ] `php artisan migrate:fresh --seed && composer test`
5. [ ] `grep -rn 'team\|workspace\|MarketplaceProduct\|MarketplaceOrder\|MarketplaceSyncLog'` çıktısını PR #0.2 ve #0.3 için hazırla
6. [ ] PR #0.1'i aç (`feat: introduce ServiceResult value object`)
7. [ ] Sonraki PR'a geç

---

## EK F — PLAN BAKIM KURALLARI

- Bu plan **canlı dokümandır** — gerçeklik öğrendikçe güncellenir.
- Tamamlanan PR `[ ]` → `[x]` ile işaretlenir; commit hash eklenir.
- Spec'te bir karar değişirse (örn. yeni pazaryeri eklenmek istenirse):
  1. Spec güncellenir
  2. Bu plana yeni PR/faz eklenir (versiyon notu en üstte)
  3. `.ai/guidelines/cirotik.blade.php` güncellenir
  4. `php artisan boost:update` çalıştırılır
- Plan değişiklikleri **kendi commit'i** olur — kod commit'iyle karışmaz.

---

*Plan v1.0 — Cirotik Implementation Roadmap — 2026-05-28*
