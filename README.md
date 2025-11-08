# 🎯 Resbe Multi-Marketplace Integration Plan

## 📋 Project Overview
**Resbe** is a multi-marketplace integration platform that allows sellers to manage products, orders, claims, and customer interactions across multiple marketplaces (Trendyol, Hepsiburada, n11, Amazon, etc.) from a single unified API.

### Key Principles
- **Multi-user support**: Each user has their own marketplace credentials
- **Multi-marketplace ready**: Extensible architecture for unlimited marketplace integrations
- **Unified product system**: Single source of truth for products
- **REST API only**: JSON responses, no Blade/frontend in this project

---

## Phase 1: Foundation & Core Database Setup ✅
**Goal:** Prepare Laravel environment and establish multi-marketplace database structure

**Tasks:**
- [x] Update `.env` with `metronic8` database credentials
- [x] Configure API routing in `bootstrap/app.php`
- [x] Create `routes/api.php` with versioning structure (`/api/v1/*`)
- [x] Create `config/marketplace.php` configuration file
- [x] Test database connection

**Database Tables:**
1. [x] `marketplaces` - Master marketplace data (Trendyol, Hepsiburada, etc.)
2. [x] `user_marketplace_credentials` - User API keys per marketplace
3. [x] `products` - Unified product table
4. [x] `marketplace_products` - Pivot table with marketplace-specific data
5. [x] `marketplace_sync_logs` - Sync history and error tracking

**Deliverables:**
- ✅ API routing configured
- ✅ Database connection verified
- ✅ Core 5 tables migrated
- ✅ `MarketplaceSeeder` created (Trendyol active, others passive)
- ✅ Config file ready

---

## Phase 2: Core Models & Relationships ✅
**Goal:** Build foundational models with proper relationships

**Tasks:**
- [x] Create `Marketplace` model
- [x] Create `UserMarketplaceCredential` model
- [x] Create `Product` model (with soft deletes)
- [x] Create `MarketplaceProduct` model (pivot)
- [x] Create `MarketplaceSyncLog` model
- [x] Define all model relationships
- [x] Add fillable, casts, and validation rules
- [ ] Create factories for testing

**Model Relationships:**
```php
User hasMany UserMarketplaceCredential
User hasMany Product
UserMarketplaceCredential belongsTo User, Marketplace
Product belongsTo User
Product belongsToMany Marketplace through MarketplaceProduct
MarketplaceProduct belongsTo Product, Marketplace
```

**Deliverables:**
- ✅ 5 models with relationships
- ✅ Model factories
- ✅ Soft delete on products
- ✅ JSON casts for metadata fields

---

## Phase 3: Marketplace Service Architecture ✅
**Goal:** Build extensible service layer for marketplace integrations

**Tasks:**
- [x] Create `MarketplaceServiceInterface` contract
- [x] Create `BaseMarketplaceService` abstract class (hybrid approach)
- [x] Create `MarketplaceServiceFactory` for service instantiation
- [x] Create `TrendyolService` (implements interface)
- [x] Setup HTTP client with Basic Auth
- [x] Implement product operations (getProducts, createProduct, updateProduct)
- [x] Implement stock/price operations
- [x] Add error handling and logging
- [x] Implement automatic sync logging to database

**Service Interface:**
```php
interface MarketplaceServiceInterface {
    // Product Operations
    public function getProducts(array $filters = []): array;
    public function createProduct(Product $product): array;
    public function updateProduct(Product $product): array;
    public function updateStock(string $barcode, int $quantity): array;
    public function updatePrice(string $barcode, float $price): array;
    
    // Category & Brand
    public function getCategories(): array;
    public function getBrands(): array;
}
```

**Deliverables:**
- ✅ Interface-based service architecture
- ✅ TrendyolService fully implemented
- ✅ Service factory pattern
- ✅ Error handling with try-catch
- ✅ Logging to `marketplace_sync_logs`

---

## Phase 4: API Controllers & Routes
**Goal:** Create REST API endpoints for frontend integration

**Tasks:**
- [ ] Create `MarketplaceController` (list marketplaces)
- [ ] Create `UserMarketplaceCredentialController` (CRUD credentials)
- [ ] Create `ProductController` (unified product CRUD)
- [ ] Create `MarketplaceProductController` (push/pull/sync)
- [ ] Create base API response trait (`ApiResponseTrait`)
- [ ] Create Form Request classes for validation
- [ ] Add Policy classes for authorization

**API Endpoints:**
```
# Marketplaces
GET    /api/v1/marketplaces
GET    /api/v1/marketplaces/{id}

# Credentials
GET    /api/v1/marketplace-credentials
POST   /api/v1/marketplace-credentials
PUT    /api/v1/marketplace-credentials/{id}
DELETE /api/v1/marketplace-credentials/{id}
POST   /api/v1/marketplace-credentials/{id}/test

# Products
GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/products/{id}
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}

# Marketplace Products
GET    /api/v1/marketplace-products
POST   /api/v1/marketplace-products/push      # Push to marketplace
POST   /api/v1/marketplace-products/pull      # Pull from marketplace
POST   /api/v1/marketplace-products/sync      # Sync stock/price
```

**Deliverables:**
- ✅ All controllers with CRUD operations
- ✅ Standardized JSON responses
- ✅ Form Request validation
- ✅ Policy-based authorization
- ✅ Manual testing via Postman

---

## Phase 5: Product Sync & Normalization
**Goal:** Implement bi-directional product synchronization

**Tasks:**
- [ ] Create product push logic (Laravel → Marketplace)
- [ ] Create product pull logic (Marketplace → Laravel)
- [ ] Implement stock/price sync (bi-directional)
- [ ] Handle duplicate detection (barcode/SKU matching)
- [ ] Normalize marketplace responses to unified format
- [ ] Track sync history in `marketplace_sync_logs`
- [ ] Implement batch processing for bulk operations
- [ ] Add retry mechanism for failed syncs

**Sync Flow:**
```
PUSH: User creates product → Format for marketplace → 
      Send to marketplace API → Store marketplace_product record → Log

PULL: Fetch from marketplace → Normalize data → 
      Match existing products → Create/update → Log

SYNC: Check local vs marketplace → Sync differences → Log
```

**Deliverables:**
- ✅ Product push to Trendyol working
- ✅ Product pull from Trendyol working
- ✅ Stock/price sync working
- ✅ Duplicate detection implemented
- ✅ Comprehensive sync logs

---

## Phase 6: Order Management System ✅
**Goal:** Implement marketplace order tracking and management

**Database Tables:**
- [x] `marketplace_orders` - Order header information
- [x] `marketplace_order_items` - Order line items

**Tasks:**
- [x] Create `MarketplaceOrder` and `MarketplaceOrderItem` models
- [x] Implement order fetch from marketplace
- [x] Create order status update functionality
- [x] Add tracking number update
- [x] Implement invoice link submission
- [x] Create order management endpoints

**Service Methods:**
```php
public function getOrders(array $filters = []): array;
public function updateOrderStatus(string $packageId, string $status): array;
public function updateTrackingNumber(string $packageId, string $trackingNumber): array;
public function sendInvoice(string $packageId, string $invoiceNumber, string $invoiceLink): array;
```

**Deliverables:**
- ✅ 2 tables, 2 models, 6 endpoints
- ✅ Order fetch, status update, tracking, invoice
- ✅ Product-order item auto-linking

---

## Phase 7: Claims Management ✅
**Goal:** Handle product returns and refunds

**Completed:** November 8, 2025

**Database Tables:**
- ✅ `marketplace_claims` - Return/claim header (39 columns)
- ✅ `marketplace_claim_items` - Returned items (24 columns)

**Models:**
- ✅ `MarketplaceClaim` - with 4 relationships (user, marketplace, order, items)
- ✅ `MarketplaceClaimItem` - with 4 relationships (claim, product, marketplaceProduct, orderItem)

**API Endpoints:**
```
GET    /api/v1/marketplace-claims               # List claims with filters
GET    /api/v1/marketplace-claims/{id}          # Get claim details
POST   /api/v1/marketplace-claims/fetch         # Fetch from marketplace
POST   /api/v1/marketplace-claims/{id}/approve  # Approve claim
POST   /api/v1/marketplace-claims/{id}/reject   # Reject claim
```

**Deliverables:**
- ✅ Claim fetch working
- ✅ Approve/reject claims
- ✅ Claim management API endpoints
- ✅ Product-claim item auto-linking
- ✅ Order-claim linking
- ✅ 7 log points (Turkish)

---

## Phase 8: Q&A Management
**Goal:** Manage customer questions and answers

**Database Tables:**
- [ ] `marketplace_questions` - Customer questions

**Tasks:**
- [ ] Create `MarketplaceQuestion` model
- [ ] Implement question fetch from marketplace
- [ ] Add answer submission functionality
- [ ] Create Q&A management endpoints

**Deliverables:**
- ✅ Question fetch working
- ✅ Answer submission working
- ✅ Q&A API endpoints

---

## Phase 9: Category & Brand Cache
**Goal:** Cache marketplace categories and brands for faster access

**Database Tables:**
- [ ] `marketplace_categories` - Hierarchical category structure
- [ ] `marketplace_brands` - Brand list

**Tasks:**
- [ ] Create `MarketplaceCategory` and `MarketplaceBrand` models
- [ ] Implement category fetch and caching
- [ ] Implement brand fetch and caching
- [ ] Create artisan commands for sync
- [ ] Add category attribute support

**Artisan Commands:**
```bash
php artisan marketplace:sync-categories {marketplace}
php artisan marketplace:sync-brands {marketplace}
```

**Deliverables:**
- ✅ Categories cached locally
- ✅ Brands cached locally
- ✅ Artisan commands working
- ✅ Hierarchical category support

---

## Phase 10: Financial Reports & CHE API Integration
**Goal:** Integrate Trendyol CHE (Cari Hesap Ekstresi) Finance API for detailed financial tracking and profit analysis

**Database Tables:**
- [ ] `marketplace_settlements` - Sales transactions (22+ transaction types)
- [ ] `marketplace_other_financials` - Deductions, invoices, penalties
- [ ] `marketplace_cargo_invoices` - Cargo invoice headers
- [ ] `marketplace_cargo_invoice_items` - Detailed cargo costs per order

**Tasks:**
- [ ] Create financial models with proper relationships
- [ ] Extend TrendyolService with CHE API methods
- [ ] Implement 15-day chunking logic for date ranges
- [ ] Create MarketplaceFinancialController with 7 endpoints
- [ ] Add transaction classification system (sale, return, discount, penalty, etc.)
- [ ] Implement cargo cost mapping to orders
- [ ] Create financial dashboard calculations
- [ ] Setup queue jobs for automated sync
- [ ] Configure scheduler for daily/weekly updates

**CHE API Endpoints:**
```php
GET /integration/finance/che/sellers/{sellerId}/settlements
GET /integration/finance/che/sellers/{sellerId}/otherfinancials
GET /integration/finance/che/sellers/{sellerId}/cargo-invoice/{invoiceId}/items
```

**API Endpoints:**
```php
GET    /api/v1/marketplace-financials              // List all financial data
GET    /api/v1/marketplace-financials/settlements  // Get settlements only
GET    /api/v1/marketplace-financials/other        // Get other financials
GET    /api/v1/marketplace-financials/cargo        // Get cargo invoices
POST   /api/v1/marketplace-financials/sync         // Manual sync
GET    /api/v1/marketplace-financials/dashboard    // Financial dashboard
GET    /api/v1/marketplace-financials/profit/{orderId} // Order profit breakdown
```

**Scheduler Config:**
```php
// Daily settlements sync (yesterday's data)
$schedule->job(new FetchDailySettlementsJob)->dailyAt('01:00');

// Weekly comprehensive financial sync
$schedule->job(new FetchWeeklyFinancialsJob)->weekly()->sundays()->at('02:00');
```

**Key Features:**
- ✅ 15-day chunking for large date ranges
- ✅ Transaction classification (22+ types)
- ✅ Cargo cost mapping to orders
- ✅ Platform fee categorization
- ✅ Net profit calculation per order
- ✅ Financial dashboard summaries

**Deliverables:**
- ✅ CHE API integration working
- ✅ 4 financial tables created
- ✅ 7 API endpoints functional
- ✅ Automated daily/weekly sync
- ✅ Financial dashboard with summaries
- ✅ Order-level profit breakdown

---

## Phase 11: Profit Calculation
**Goal:** Calculate product profitability

**Tasks:**
- [ ] Add profit fields to `marketplace_products` table
- [ ] Create `ProfitCalculationService`
- [ ] Implement profit formula
- [ ] Add commission rates per marketplace
- [ ] Create profit calculation endpoint

**Profit Formula:**
```php
net_profit = sale_price - (purchase_cost + commission + shipping_cost + platform_fee)
profit_rate = (net_profit / sale_price) * 100
margin_rate = (net_profit / purchase_cost) * 100
```

**Deliverables:**
- ✅ Profit calculation working
- ✅ Per-marketplace commission rates
- ✅ Profit API endpoint
- ✅ Profit reports

---

## Phase 12: Authentication & Security
**Goal:** Secure API with token-based authentication

**Tasks:**
- [ ] Install Laravel Sanctum
- [ ] Configure token authentication
- [ ] Add auth middleware to routes
- [ ] Implement rate limiting
- [ ] Add Policy-based authorization
- [ ] Test with Metronic8 panel

**Deliverables:**
- ✅ Sanctum working
- ✅ Protected routes
- ✅ Rate limiting active
- ✅ Authorization policies
- ✅ Ready for frontend integration

---

## 📋 Development Principles (All Phases)

1. **Keep it Simple**: No over-engineering, no unnecessary abstractions
2. **Multi-marketplace First**: Design with extensibility in mind
3. **Interface-based Services**: Easy to add new marketplaces
4. **Manual Testing**: Test each endpoint manually before moving forward
5. **Incremental Progress**: Complete each phase fully before moving to next
6. **Comprehensive Logging**: Log all marketplace API calls and errors
7. **Error Handling**: Wrap all external API calls in try-catch
8. **Code Standards**: Follow PSR-12, use type hints, return types
9. **Authorization**: Policy-based access control (users see only their data)
10. **Soft Deletes**: Use soft deletes on critical tables

---

## 📊 Progress Tracking

| Phase | Focus Area | Status | Est. Time |
|-------|-----------|--------|-----------|  
| Phase 1 | Foundation & Core DB | ✅ Completed | 1-2 days |
| Phase 2 | Models & Relationships | ✅ Completed | 1 day |
| Phase 3 | Service Architecture | ✅ Completed | 2-3 days |
| Phase 4 | API Controllers | ⏳ In Progress | 1-2 days |
| Phase 5 | Product Sync | ⏳ Pending | 2 days |
| Phase 6 | Order Management | ✅ Completed | 2 days |
| Phase 7 | Claims Management | ⏳ Pending | 1 day |
| Phase 8 | Q&A Management | ⏳ Pending | 1 day |
| Phase 9 | Category/Brand Cache | ⏳ Pending | 1 day |
| Phase 10 | Queue & Scheduler | ⏳ Pending | 1 day |
| Phase 11 | Profit Calculation | ⏳ Pending | 1 day |
| Phase 12 | Auth & Security | ⏳ Pending | 1 day |

**Total Estimated Time:** 14-18 days

---

## 🗄️ Database Schema Summary

### Core Tables (Phase 1-2)
1. `marketplaces` - Marketplace registry (Trendyol, Hepsiburada, etc.)
2. `user_marketplace_credentials` - User API keys per marketplace
3. `products` - Unified product table (with soft deletes)
4. `marketplace_products` - Pivot with marketplace-specific data
5. `marketplace_sync_logs` - Comprehensive sync logging

### Order Tables (Phase 6)
6. `marketplace_orders` - Order header
7. `marketplace_order_items` - Order line items

### Claim Tables (Phase 7)
8. `marketplace_claims` - Return/refund header
9. `marketplace_claim_items` - Returned items

### Support Tables (Phase 8-9)
10. `marketplace_questions` - Customer Q&A
11. `marketplace_categories` - Cached categories (hierarchical)
12. `marketplace_brands` - Cached brands

**Total: 12 tables**

---

## 🔧 Service Architecture

### Interface (Contract)
```php
interface MarketplaceServiceInterface {
    // Product Operations
    public function getProducts(array $filters = []): array;
    public function createProduct(Product $product): array;
    public function updateProduct(Product $product): array;
    public function updateStock(string $barcode, int $quantity): array;
    public function updatePrice(string $barcode, float $price): array;
    
    // Order Operations
    public function getOrders(array $filters = []): array;
    public function updateOrderStatus(string $packageId, string $status): array;
    public function updateTrackingNumber(string $packageId, string $trackingNumber): array;
    public function sendInvoice(string $packageId, string $invoiceNumber, string $invoiceLink): array;
    
    // Claim Operations
    public function getClaims(array $filters = []): array;
    public function approveClaim(string $claimId): array;
    public function rejectClaim(string $claimId, string $reason): array;
    
    // Q&A Operations
    public function getQuestions(array $filters = []): array;
    public function answerQuestion(string $questionId, string $answer): array;
    
    // Data Operations
    public function getCategories(): array;
    public function getBrands(): array;
    public function getCategoryAttributes(int $categoryId): array;
}
```

### Factory Pattern
```php
class MarketplaceServiceFactory {
    public static function make(
        Marketplace $marketplace, 
        UserMarketplaceCredential $credential
    ): MarketplaceServiceInterface {
        return match($marketplace->code) {
            'TRENDYOL' => new TrendyolService($credential),
            'HEPSIBURADA' => new HepsiburadaService($credential),
            'N11' => new N11Service($credential),
            'AMAZON' => new AmazonService($credential),
            default => throw new \Exception("Marketplace not supported: {$marketplace->code}")
        };
    }
}
```

---

## 🛣️ API Endpoint Structure

### Marketplace Management
```
GET    /api/v1/marketplaces                    # List all marketplaces
GET    /api/v1/marketplaces/{id}                # Get marketplace details
```

### Credential Management
```
GET    /api/v1/marketplace-credentials          # User's credentials
POST   /api/v1/marketplace-credentials          # Add credential
PUT    /api/v1/marketplace-credentials/{id}     # Update credential
DELETE /api/v1/marketplace-credentials/{id}     # Delete credential
POST   /api/v1/marketplace-credentials/{id}/test # Test connection
```

### Product Management
```
GET    /api/v1/products                         # List products
POST   /api/v1/products                         # Create product
GET    /api/v1/products/{id}                    # Get product
PUT    /api/v1/products/{id}                    # Update product
DELETE /api/v1/products/{id}                    # Delete product (soft)
POST   /api/v1/products/bulk                    # Bulk create
```

### Marketplace Product Operations
```
GET    /api/v1/marketplace-products             # List synced products
POST   /api/v1/marketplace-products/push        # Push to marketplace
POST   /api/v1/marketplace-products/pull        # Pull from marketplace
POST   /api/v1/marketplace-products/sync        # Sync stock/price
GET    /api/v1/marketplace-products/{id}        # Get sync status
```

### Order Management
```
GET    /api/v1/marketplace-orders               # List orders
GET    /api/v1/marketplace-orders/{id}          # Get order details
POST   /api/v1/marketplace-orders/fetch         # Fetch from marketplace
PUT    /api/v1/marketplace-orders/{id}/status   # Update status
PUT    /api/v1/marketplace-orders/{id}/tracking # Update tracking
POST   /api/v1/marketplace-orders/{id}/invoice  # Send invoice
```

### Claim Management
```
GET    /api/v1/marketplace-claims               # List claims
GET    /api/v1/marketplace-claims/{id}          # Get claim details
POST   /api/v1/marketplace-claims/fetch         # Fetch from marketplace
POST   /api/v1/marketplace-claims/{id}/approve  # Approve claim
POST   /api/v1/marketplace-claims/{id}/reject   # Reject claim
```

### Q&A Management
```
GET    /api/v1/marketplace-questions            # List questions
GET    /api/v1/marketplace-questions/{id}       # Get question details
POST   /api/v1/marketplace-questions/fetch      # Fetch from marketplace
POST   /api/v1/marketplace-questions/{id}/answer # Submit answer
```

### Data Sync
```
GET    /api/v1/marketplace-sync-logs            # View sync history
POST   /api/v1/sync/categories                  # Sync categories
POST   /api/v1/sync/brands                      # Sync brands
```

---

## 📦 Configuration Structure

### `config/marketplace.php`
```php
return [
    'default_currency' => 'TRY',
    'default_vat_rate' => 18,
    
    'sync' => [
        'enabled' => env('MARKETPLACE_SYNC_ENABLED', true),
        'batch_size' => 50,
        'retry_attempts' => 3,
        'retry_delay' => 60, // seconds
    ],
    
    'marketplaces' => [
        'trendyol' => [
            'name' => 'Trendyol',
            'code' => 'TRENDYOL',
            'api_base_url' => env('TRENDYOL_API_URL'),
            'stage_api_url' => env('TRENDYOL_STAGE_API_URL'),
            'timeout' => 30,
        ],
        // Other marketplaces...
    ],
    
    'order_statuses' => [
        'created', 'picking', 'invoiced', 'shipped', 'delivered', 'cancelled'
    ],
    
    'claim_statuses' => [
        'created', 'approved', 'rejected', 'refunded'
    ],
];
```

---

## 🚀 Current Status

**Active Phase:** Phase 4 - API Controllers & Routes

**Completed Phases:**
- ✅ Phase 1: Foundation & Core Database Setup
- ✅ Phase 2: Core Models & Relationships
- ✅ Phase 3: Marketplace Service Architecture (Hybrid: BaseMarketplaceService + TrendyolService)

**Phase 3 Highlights:**
- ✅ Interface-based architecture with `MarketplaceServiceInterface`
- ✅ `BaseMarketplaceService` abstract class for shared logic (HTTP, logging, error handling)
- ✅ `TrendyolService` fully implemented (products, orders, claims, questions, categories, brands)
- ✅ `MarketplaceServiceFactory` with multiple instantiation methods
- ✅ Automatic sync logging to `marketplace_sync_logs` table
- ✅ Config-driven API URLs and timeouts
- ✅ Comprehensive error handling and validation

**Next Steps (Phase 4):**
1. Create `ApiResponseTrait` for standardized JSON responses
2. Create `MarketplaceController` (list marketplaces)
3. Create `MarketplaceCredentialController` (CRUD credentials)
4. Create `ProductController` (unified product CRUD)
5. Create `MarketplaceProductController` (push/pull/sync)
6. Add Form Request validation classes
7. Test all endpoints manually

---

## 📝 Important Notes

### Database
- **Shared database**: `metronic8` (with Metronic8 panel)
- **Existing tables**: users, roles, permissions, sessions, cache, jobs, languages
- **Check before creating**: Use `Schema::hasTable()` in migrations
- **Soft deletes**: Enabled on products table

### Multi-user Support
- Every table has `user_id` foreign key
- Policy-based authorization (users see only their data)
- Exception: Admin/Software role can see all

### Marketplace Extensibility
- Interface-based design for easy extension
- Add new marketplace: Create service class implementing interface
- Factory pattern handles service instantiation
- No code changes needed in controllers

### API Response Format
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 100
  }
}
```

### Error Handling
- All marketplace API calls wrapped in try-catch
- Errors logged to `marketplace_sync_logs` table
- User-friendly error messages returned
- Retry mechanism via queue jobs

### Testing Strategy
- Manual testing via Postman/Insomnia
- Test with real Trendyol API (stage environment)
- Verify each phase before moving to next
- Integration testing with Metronic8 panel

---

## 🎯 Success Criteria

- ✅ Multi-user marketplace system working
- ✅ Trendyol fully integrated (products, orders, claims, Q&A)
- ✅ Architecture ready for additional marketplaces
- ✅ Automated syncs via queue/scheduler
- ✅ Comprehensive logging and error handling
- ✅ Secure API with Sanctum authentication
- ✅ Clean, maintainable, PSR-12 compliant code
- ✅ Ready for Metronic8 frontend integration
