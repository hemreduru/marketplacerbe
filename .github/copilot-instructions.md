# Copilot Instructions for restbe

## Project Overview
**Resbe** (REST API for Marketplace Seller Integration) is a **Laravel 12** REST-only backend that helps Turkish marketplace sellers manage their products across multiple platforms (Trendyol, Hepsiburada, etc.) from a single centralized API.

**Purpose:**
- Centrally manage product pricing, stock, and performance data from multiple marketplaces
- Calculate product costs, profits, commissions, and expenses
- Fetch and normalize data from marketplace APIs
- Provide a unified REST API for future mobile and web applications

**Tech Stack:**
- Laravel 12
- PHP 8.2+
- MySQL (primary database)
- Pest PHP for testing
- Database-backed sessions/cache/queues
- Laravel Sanctum (planned for token-based authentication)
- Scheduler/Queue for automatic synchronizations

**Important:** This is a **REST API-only** backend. No Blade, Inertia, Livewire, or frontend framework usage. All endpoints return JSON responses.

## Architecture & Structure

### Layered Architecture
1. **Controllers** (`app/Http/Controllers/Api/V1/`) → Manage API endpoints
2. **Services** (`app/Services/`) → Handle marketplace integrations (Trendyol, Hepsiburada, etc.)
3. **Models** (`app/Models/`) → Database models
4. **Transformers/DTOs** → Normalize API responses
5. **Repositories** (optional) → Abstract database access

### Core Domain Models
| Model | Purpose |
|-------|---------|
| `Store` | Marketplace account credentials and settings |
| `Product` | Product core information |
| `ProductPrice` | Price history tracking |
| `ProductStock` | Stock level information |
| `CostDetail` | Cost, shipping, commission, and profit calculations |

### Routing
- **API versioning required**: All endpoints under `/api/v1/...`
- **No web routes**: This is REST API-only (configure `api.php` in `bootstrap/app.php`)
- Health check endpoint: `/up` (auto-configured in `bootstrap/app.php`)
- Example endpoints:
  - `GET /api/v1/stores` - List all stores
  - `GET /api/v1/products` - List products
  - `POST /api/v1/products/sync` - Fetch from marketplace APIs
  - `GET /api/v1/products/{id}/profit` - Get profit/cost calculation

### Marketplace Integration Strategy
- **Extensible service architecture**: Each marketplace has its own service class
- Service pattern: `app/Services/{MarketplaceName}Service.php`
- Examples: `TrendyolService.php`, `HepsiburadaService.php`
- New marketplaces can be added without modifying existing code
- All services should normalize data to a common format before storing

### Synchronization Flow
1. User defines `Store` with marketplace API credentials
2. System connects to marketplace service
3. Fetches product data and normalizes it
4. Stores in database (`Product`, `ProductStock`, `ProductPrice`)
5. Calculates profit and stores in `CostDetail`
6. **Phase 1**: One-way sync (fetch only)
7. **Future**: Two-way sync (push price/stock updates)

### Database
- **Shared database**: Uses `metronic8` database (shared with Metronic8 frontend panel)
- Connection: MySQL at `127.0.0.1:3306`
- **Existing tables** (do not recreate):
  - `users` - User authentication (with `username`, `avatar`, `current_role_id`, `settings_id`)
  - `roles`, `permissions` - Spatie Permission package (with `display_name`, multi-language descriptions)
  - `sessions`, `cache`, `jobs` - Laravel infrastructure tables
  - `languages` - Multi-language support
- **New tables to create**: `stores`, `products`, `product_prices`, `product_stocks`, `cost_details`
- Migrations use anonymous class syntax (Laravel 12 standard)
- When creating migrations, check for existing tables first using `Schema::hasTable()`

### Testing
- **Pest PHP** configured as test runner (via `composer.json`)
- PHPUnit XML config uses in-memory SQLite for tests
- Test structure: `tests/Feature/` and `tests/Unit/`
- Standard PHPUnit syntax used (not Pest DSL) in existing tests
- Run via: `composer test` (clears config cache first)

### Models
- Models use typed property promotion and modern Laravel 12 patterns
- Example `User.php`: uses `@use HasFactory<\Database\Factories\UserFactory>` annotations
- Casts defined via `casts()` method (not property)
- Uses `protected $fillable` for mass assignment

## Development Workflow

### Essential Commands
```bash
# Initial setup (runs install, env copy, key generate, migrate, npm install/build)
composer setup

# Start full dev environment (server, queue, logs, vite - all concurrent)
composer dev

# Run tests (with config clear)
composer test

# Individual services
php artisan serve          # Web server only
php artisan queue:listen   # Queue worker
php artisan pail           # Log viewer
npm run dev                # Vite dev server
```

### Key Scripts
- **`composer dev`**: Launches 4 concurrent services via `npx concurrently`:
  1. Laravel dev server (`:8000`)
  2. Queue listener (with `--tries=1`)
  3. Pail log viewer (with `--timeout=0`)
  4. Vite dev server (HMR)
- All processes killed together when stopped

### Environment
- Copy `.env.example` to `.env` and run `php artisan key:generate`
- Database: `metronic8` (MySQL) - **shared with Metronic8 panel**
  - `DB_DATABASE=metronic8`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=password123`
- Default queue/cache use database connection

## Coding Conventions

### PHP Standards
- **PSR-4 autoloading**: `App\` namespace for `app/` directory
- **PSR-12 compliant**: Code style and formatting standards
- **4 spaces** for indentation (see `.editorconfig`)
- Return types required on all methods
- Use Laravel 12 routing fluent API in `bootstrap/app.php`

### Design Principles
- **No over-engineering**: Avoid unnecessary abstractions, interfaces, events, or observers
- **Keep it simple**: Use only HasMany, BelongsTo relationships (no complex associations)
- **REST API only**: No Blade, Inertia, Livewire, or React usage
- **Manual testing preferred**: Unit tests not mandatory, but provide manual test endpoints
- **Extensible services**: Each marketplace service in separate file under `app/Services/`

### API Response Standard
All endpoints return JSON in this format:
```json
{
  "success": true,
  "message": "Products fetched successfully",
  "data": { ... }
}
```

### Profit Calculation Logic
```php
net_profit = sale_price - (purchase_cost + commission + shipping_cost)
profit_rate = (net_profit / sale_price) * 100
```

### Configuration
- Environment-based config via `.env` (never commit `.env`)
- Locale: `en` (configurable via `APP_LOCALE`)
- Timezone: `UTC` (in `config/app.php`)
- Maintenance mode: file driver

### Service Providers
- Register in `bootstrap/providers.php` (Laravel 12 pattern)
- `AppServiceProvider` is default location for app-level bootstrapping

## Testing Patterns
- Feature tests extend `Tests\TestCase` (includes Laravel integration)
- Unit tests extend `PHPUnit\Framework\TestCase` (pure PHPUnit)
- Use `RefreshDatabase` trait for database tests (commented in examples)
- Assertions: `$this->get('/')->assertStatus(200)`

## Error Handling & Logging
- All operations wrapped in try-catch blocks
- Logs written to `storage/logs/resbe.log`
- Marketplace API errors logged as: `ERROR: [TrendyolService] message`
- Use Laravel's logging facade for consistency

## Important Notes
- **No API routes configured**: Add `api: __DIR__.'/../routes/api.php'` to `bootstrap/app.php` when building API endpoints
- **Shared database**: Uses `metronic8` database with existing `users`, `roles`, `permissions` tables (Spatie Permission package)
- **REST API only**: This backend serves JSON; frontend integration will be via Bearer Token or Sanctum
- **Composer scripts**: Prefer `composer dev/test/setup` over manual commands
- **Extensible design**: New marketplaces can be added by creating new service classes without modifying existing code

## File Generation Patterns
- **Migrations**: `php artisan make:migration create_table_name` (use anonymous classes)
- **Models**: `php artisan make:model ModelName -mf` (with migration + factory)
- **Controllers**: `php artisan make:controller ControllerName`
- **Tests**: `php artisan make:test TestName` (Feature) or `--unit` flag
