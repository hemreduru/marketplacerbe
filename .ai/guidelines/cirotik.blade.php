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
