# Laravel SaaS Starter Kit

> **Warning:** This project is under active development and not yet production-ready. APIs, database schemas, and configuration formats may change without notice. Use at your own risk.

A Laravel 12 + React 19 + Inertia v2 starter kit with multi-tenant organizations, role-based permissions, and Stripe billing -- all configurable from a single file.

## Features

- **Organizations** -- Jetstream-like teams with personal orgs on signup, multi-org membership, and org switching
- **Roles & Permissions** -- Configurable Owner/Admin/Member roles with wildcard permission support
- **Invitations** -- UUID-based secure links with expiry, resend cooldown, accept/reject flow
- **Stripe Billing** -- Cashier-powered subscriptions with free/paid/metered plans, Stripe Checkout, and Billing Portal
- **Plan Limits & Feature Gating** -- Backend middleware + helpers and React hooks for usage tracking
- **Caching** -- Built-in caching for orgs, permissions, plans, and billing data with automatic invalidation
- **Action Pattern** -- Thin controllers, all business logic in `app/Actions/`
- **Two-Factor Auth** -- via Laravel Fortify

## Tech Stack

Laravel 12, React 19, Inertia.js v2, TypeScript, Tailwind CSS, shadcn/ui, Laravel Cashier (Stripe), Pest 4

## Quick Start

```bash
git clone <repo> && cd saas-starter
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Configuration

Everything lives in `config/saas.php`:

- **Roles & permissions** -- Add/remove roles, assign permissions with wildcard support (`member:*`)
- **Plans & pricing** -- Define free, paid, and metered plans with limits and features
- **Invitations** -- Toggle on/off, set expiry days
- **Cache** -- Enable/disable, set TTL

## Stripe Setup

1. Add your Stripe keys to `.env`:

```
STRIPE_KEY=pk_...
STRIPE_SECRET=sk_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

2. Sync plans to Stripe:

```bash
php artisan saas:sync
```

This creates Stripe Products, Prices, and Meters from your `config/saas.php` billing config and stores the IDs in the database.

### Local Webhook Development

Stripe can't reach `localhost` directly. Use the [Stripe CLI](https://docs.stripe.com/stripe-cli) to forward events:

```bash
# Install (macOS)
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward events to your local app
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

Copy the `whsec_...` signing secret it prints into your `.env` as `STRIPE_WEBHOOK_SECRET`.

Cashier registers the `POST /stripe/webhook` route automatically. The included `StripeWebhookListener` handles subscription events and clears billing caches.

To test manually:

```bash
stripe trigger customer.subscription.created
```

## Usage

### Backend

```php
use App\Enums\BillingFeature;
use App\Enums\BillingMeterKey;
use App\Enums\BillingPlan;

// Check plan limits (enums provide autocomplete)
$org->withinLimit(BillingFeature::Items, $count);
$org->exceedsLimit(BillingFeature::AiTokens, $count);

// Get current plan
$org->currentPlan();
$org->onFreePlan();
$org->onPlan(BillingPlan::Pro);

// Report metered usage
app(ReportUsage::class)->handle($org, BillingMeterKey::AiTokens, 5);

// Global helpers
within_limit(BillingFeature::Items, $count);
report_usage(BillingMeterKey::AiTokens, 10);

// Middleware
Route::middleware('subscribed');           // require active subscription
Route::middleware('plan.limit:items,count'); // enforce plan limit
```

### Frontend

```tsx
const { plan, isSubscribed, isFree } = useSubscription();
const { limit, isWithin } = usePlanLimit('items', count);
const canCreate = useCanFeature('items', count);
```

## Running Tests

```bash
php artisan test --compact
```

## Roadmap

### Completed

- [x] Multi-tenant organizations with personal orgs on signup
- [x] Configurable roles & permissions with wildcard support
- [x] Organization switching and member management
- [x] UUID-based invitations with expiry, resend cooldown, accept/reject
- [x] Stripe Cashier integration (subscriptions, checkout, billing portal)
- [x] Config-driven plans with `saas:sync` command
- [x] Free, fixed, metered, and combination billing models
- [x] Plan limits & feature gating (middleware, helpers, React hooks)
- [x] Caching layer for orgs, permissions, plans, and billing
- [x] Webhook listener with automatic cache invalidation
- [x] Type-safe billing enums (`BillingPlan`, `BillingFeature`, `BillingMeterKey`)
- [x] Comprehensive logging & error handling
- [x] Two-factor authentication (Fortify)
- [x] 117 feature tests (Pest 4)

### Planned

- [ ] Admin dashboard for managing organizations and users
- [ ] Ownership transfer between members
- [ ] API token management (Sanctum)
- [ ] Usage analytics and billing insights dashboard
- [ ] Multi-currency support
- [ ] Webhook retry and failure monitoring
- [ ] Email templates customization
- [ ] Audit log for organization actions
- [ ] Notification preferences per organization
- [ ] Impersonation for admin support

## Development

```bash
composer run dev
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

MIT -- see [LICENSE](LICENSE) for details.
