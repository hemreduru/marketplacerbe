# CLAUDE.md

Bu dosya, bu depoda çalışırken Claude Code'a (claude.ai/code) rehberlik eder. **Ayrıntılı kodlama kuralları aşağıdaki Boost guideline bloğundadır** (kaynağı `.ai/guidelines/cirotik.blade.php`; `php artisan boost:update` ile üretilir — bloğu elle düzenleme).

## Proje

**Cirotik** (eski adı "Resbe") — çok pazaryerli Türk e-ticaret satıcıları için **SKU bazlı gerçek net kâr** paneli. Laravel 12 / PHP 8.3, server-rendered Blade (Metronic 8), **1 kullanıcı = 1 hesap** (Team/Workspace/RBAC yok). Trendyol canlı; Hepsiburada/N11/Pazarama `MarketplaceManager`'da kayıtlı; Amazon scaffold-only.

- **Ürün hedefi:** GitHub issue #1 — https://github.com/hemreduru/marketplacerbe/issues/1
- **Bağlayıcı spec:** `/home/emre/Documents/CIROTIK_AGENT_SPEC.md` (Bölüm 0, 6, 16)
- **Operasyonel plan / PR sırası:** `docs/CIROTIK_IMPLEMENTATION_PLAN.md` (sıradaki iş = ilk `[ ]` PR)

## Komutlar

```bash
composer dev                              # server + queue listener + pail + vite (birincil dev döngüsü)
composer test                             # config cache temizler, sonra Pest/PHPUnit
php artisan test --compact                # testler
php artisan test --compact --filter=Name  # tek test
php artisan migrate
vendor/bin/pint --dirty --format agent    # PHP düzenledikten SONRA zorunlu
vendor/bin/phpstan analyse                # level 6 temiz olmalı
php artisan boost:update                  # boost guideline bloğunu tazele
```

## Ortam

- **App DB (lokal `.env`):** MySQL `marketplace` @ 127.0.0.1 (`emre`). Testler **in-memory sqlite** (`phpunit.xml`).
- Frontend değişikliği görünmüyorsa: `npm run build` / `npm run dev` / `composer dev`.

<laravel-boost-guidelines>
=== .ai/cirotik rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
