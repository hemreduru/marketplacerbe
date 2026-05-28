# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this project is

**Resbe** is a Laravel 12 web application that lets Turkish e-commerce sellers manage products, orders, customer questions, and finances across multiple marketplaces (Trendyol live; Hepsiburada, n11, Amazon scaffolded in config) from one panel.

It is a **server-rendered Blade app** using the Metronic 8 admin theme — NOT an API-only backend. (The `.github/copilot-instructions.md` describes an earlier API-only design that no longer matches the code; trust the actual `routes/web.php` and `app/Http/Controllers/Web/` over that doc.) Sanctum is installed but `routes/api.php` only has a stub `/user` route.

## Commands

```bash
composer dev      # Run server + queue listener + pail logs + vite concurrently (primary dev loop)
composer test     # Clears config cache then runs Pest/PHPUnit
composer setup    # First-time: install, env, key, migrate, npm install + build
php artisan test --compact                 # Run tests
php artisan test --compact --filter=Name   # Run a single test by name
php artisan migrate
npm run build / npm run dev                # Rebuild front-end assets (needed when UI changes don't appear)
vendor/bin/pint --dirty --format agent     # REQUIRED after editing PHP — matches project style
```

Tests run on in-memory SQLite (see `phpunit.xml`); the app itself uses MySQL (`metronic8` DB, shared with a separate Metronic panel). Test files use Pest (`tests/Feature`, `tests/Unit`).

## Architecture

**Request flow:** `routes/web.php` → `App\Http\Controllers\Web\*` → `App\Services\Trendyol\*` → Eloquent models. All web routes are session-auth-guarded (`auth` / `guest` middleware groups).

**Marketplace integration is the core domain.** Each marketplace concern has its own service class instantiated *manually* (not via the container) with per-user credentials:

```php
$service = new TrendyolProductService($credential->api_key, $credential->api_secret,
    $credential->additional_credentials['seller_id'] ?? '', $isStage = false);
$service->syncProducts($credential->id);
```

Services (`app/Services/Trendyol/`): `TrendyolProductService`, `TrendyolOrderService`, `TrendyolFinanceService`, `TrendyolQuestionService`. They use Laravel's `Http` facade with basic auth and log failures via `Log::error`, returning `['error' => true, ...]` on failure rather than throwing. New marketplaces should follow this pattern: a new `app/Services/{Marketplace}/` directory.

**Credentials:** `user_marketplace_credentials` stores per-user `api_key`/`api_secret` (hidden from serialization) plus an `additional_credentials` JSON column (e.g. `seller_id`). Look up via `Marketplace::where('slug', 'trendyol')` then the user's credential row. Products belong to a credential (`Product::credential()`), not directly to a user — scope user queries with `whereHas('credential', fn($q) => $q->where('user_id', $id))`.

**Sync is triggered three ways:** (1) manual button → controller `sync()` method, (2) queued `App\Jobs\SyncTrendyol*Job` dispatched on the `sync` queue, (3) `routes/console.php` scheduler (every 6h / 30min). All three construct the same service the same way.

**DataTables:** list pages (products, orders) use server-side DataTables. Controller `getData()` methods read DataTables request params (`search.value`, `order.0.column`, `start`, `length`) and return `{draw, recordsTotal, recordsFiltered, data}` where `data` rows are **pre-rendered HTML strings** built in PHP.

**Localization:** Turkish + English. `SetLocale` middleware on API routes, `SetLocaleFromSession` on web routes. Translation files in `lang/{en,tr}/*.php`; reference with `__('common.key')`. User-facing strings must go through translation keys, not hardcoded.

**Config:** `config/marketplace.php` is the central source for marketplace API URLs, rate limits, endpoints, commission rates, VAT, shipping, and the canonical status/sync/entity enum maps. Read it before hardcoding any marketplace constant.

**JSON responses:** `App\Http\Traits\ApiResponseTrait` provides `successResponse`/`errorResponse`/etc. returning `{success, message, data}`. Some controller JSON endpoints (the `sync`/`getData` actions) build responses inline instead — match the surrounding method.

**Profit model:** `net_profit = sale_price - (purchase_cost + commission + shipping_cost)`; toggles in `config/marketplace.php` `profit_calculation`.

### Caveat: stale references
`app/Models/User.php` and `routes/console.php` reference models/jobs that don't exist in the codebase (`MarketplaceProduct`, `MarketplaceSyncLog`, `MarketplaceOrder`, `SyncMarketplaceProductJob`). The real models are `Product`, `Order`, `OrderItem`, `FinancialTransaction`, `FinancialDailySummary`, `Marketplace`, `UserMarketplaceCredential`. Don't assume the User relationship methods or that scheduler block work as written.

## Project-specific skills

`.agents/skills/laravel-best-practices/` and `.agents/skills/pest-testing/` contain detailed rules (eloquent, migrations, validation, testing, etc.). The Laravel Boost guidelines below are auto-managed by `php artisan boost:update` — do not hand-edit that block.

<laravel-boost-guidelines>
=== .ai/cirotik rules ===

## Cirotik — Çalışan Implementasyon Planı

> **Marka adı:** Cirotik (Ciro + ✓ tik). Vaat: *"Cironuzu görmek değil, gerçekten ne kazandığınızı bilmek."*

Bu proje, Trendyol/Hepsiburada/N11/Pazarama/Amazon TR pazaryerlerine bağlı satıcılar için **gerçek SKU bazlı net kâr** veren bir Laravel 12 Blade panelidir. Kod tabanı şu an "Resbe" adıyla Trendyol-only çalışıyor; Cirotik rebrand + yeniden mimarileme aktif olarak yürürlükte.

### Bağlayıcı kaynaklar (sırasıyla bu sıra ile oku)

1. `/home/emre/Documents/CIROTIK_AGENT_SPEC.md` — **TEK truth source.** 18 bölüm, ~2000 satır design spec.
2. `docs/CIROTIK_IMPLEMENTATION_PLAN.md` — bu projede `docs/` altında. Spec'i 7 faz (0-6), ~79 PR'a böler. **Her PR için: hedef, dokunulan dosyalar, kabul kriteri, Pest gereksinimi.**
3. `CLAUDE.md` — proje teknik özet (auto-managed `<laravel-boost-guidelines>` blokunu elle düzenleme).

### Mutlak Kurallar (her oturumda hatırla)

- ✅ **`ServiceResult` döner**, exception fırlatma (programming bug hariç). Spec Bölüm 0 Madde 5.
- ✅ **Write API çağrısı iki katmanlı sigorta:** `MARKETPLACE_WRITE_ENABLED=true` env **VE** `user_marketplace_credentials.write_enabled=true`. Aksi takdirde dispatcher `status=skipped` log.
- ✅ **Para hesabı `decimal(15, 4)` veya cents-as-integer** — asla `float`. Pest `arch()` test bunu doğrular.
- ✅ **Calculations TDD zorunlu** — `VatCalculator`, `CommissionCalculator`, `ProfitCalculator` vb. önce Pest, sonra implementasyon.
- ✅ **Webhook + sync idempotent** — `marketplace_events.event_uuid` UNIQUE; `stock_events.(source, source_reference, event_type)` UNIQUE.
- ✅ **Marketplace dosya yapısı sabit:** `app/Services/Marketplaces/{Name}/{Client,ProductService,OrderService,ClaimService,QuestionService,FinanceService,WebhookService,Mapper/}`. Spec Bölüm 0 Madde 4.
- ✅ **Yorum dili Türkçe, kod İngilizce, commit conventional (`feat:`, `fix:`, `refactor:`, `test:`)**.
- ❌ Eloquent model'den direkt API çağrısı — her zaman Service katmanı.
- ❌ Hardcoded marketplace URL — her zaman `config/marketplaces/{name}.php`.
- ❌ Migration `down()` olmadan merge.
- ❌ Team/Workspace/multi-user — model **1 user = 1 hesap** (Spec Bölüm 4).
- ❌ Mobile/Push referansları — yok; bildirim sadece **mail** + **in-app** (Spec Bölüm 5).

### Mimari Tek Bakış

```
master_products (Cirotik kendi şeması, denormalized current_stock/price)
  ←→ marketplace_listings (per credential, per remote_product_id)
  ←  stock_events (append-only ledger; source=trendyol|hb|n11|pazarama|amazon|user|system)
  ←  price_events
  →  sync_dispatch_queue (outbound mutations, exp backoff retry)
```

**Stok = tek integer değil, event akışıdır.** `MasterProductStockProjector` ile projeksiyon. Atomik UPDATE + optimistic lock (`version` sütunu) ile race condition. Stock buffer stratejisi ile oversell önleme.

### Hangi Faz / Hangi PR Üzerinde Çalışıyorum?

`docs/CIROTIK_IMPLEMENTATION_PLAN.md` PR satırlarının başındaki `[ ]` / `[x]` durumlarına bak — sıradaki ilk `[ ]` PR senin görevin. Spec referansı her PR'ın altında `Spec Ref:` etiketiyle.

Faz başlangıç PR sıralaması (Spec Bölüm 16.6 ile uyumlu):

1. `feat: introduce ServiceResult value object`
2. `chore: remove stale marketplace model references` (User.php + console.php — gerçek modeller `Product`/`Order`/`FinancialTransaction`)
3. `chore: remove team/workspace scaffolding`
4. `feat: add @money Blade directive`
5. `feat: add 2FA (TOTP) via pragmarx/google2fa-laravel`
6. `feat: activity logging via spatie/laravel-activitylog`
7. `feat: job resilience (tries + backoff + failed handler)`

### Her Oturum Açılışı

1. Spec'in PR'a denk gelen bölümünü oku (PR satırının `Spec Ref:` etiketinden).
2. `docs/CIROTIK_IMPLEMENTATION_PLAN.md` ilgili PR satırının tamamını oku.
3. `git status` temiz mi? `php artisan test --compact` yeşil mi (baseline)?
4. `git checkout -b feat/cirotik-pr-XX-<slug>`.

### Her Oturum Kapanışı (PR sonu)

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact` yeşil
- [ ] Migration `up()` + `down()` test edildi (rollback ile)
- [ ] `.env.example` güncel
- [ ] `lang/{en,tr}/` çevirileri eşli
- [ ] Bu plan dosyasında PR satırı `[x]` ve commit hash eklendi
- [ ] PHPStan level 6 hatasız

### Kritik Endpoint Referansları

- **Trendyol AI indeks (öncelikli):** https://developers.trendyol.com/llms.txt
- **Amazon SP-API AI indeks:** https://developer-docs.amazon.com/llms.txt
- **Hepsiburada Webhook:** https://developers.hepsiburada.com/hepsiburada/reference/webhook-%C3%B6nemli-bilgiler
- **2026 Trendyol Komisyon:** https://faturaport.com/blog/on-muhasebe/2026-trendyol-kar-hesaplama-komisyon-kargo-kdv-ve-net-kazanc-rehberi

Spec Bölüm 17'de tüm referans listesi var.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
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
