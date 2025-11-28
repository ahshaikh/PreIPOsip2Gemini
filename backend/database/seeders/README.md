# Comprehensive Faker Database Seeder

This comprehensive seeder creates realistic test data for the entire PreIPO SIP application, covering all three user classes: **Public**, **User**, and **Admin**.

## Features

### User Classes

1. **Admin Users (3 users)**
   - Super admin with all permissions
   - 2 additional admin users
   - Complete profiles with verified KYC
   - Email: `admin@preipo.com` / `admin1@preipo.com` / `admin2@preipo.com`
   - Password: `password`

2. **Regular Users (50 users)**
   - Active, verified accounts
   - Complete profiles with addresses
   - Mixed KYC statuses (verified, submitted, pending, rejected)
   - Email pattern: `user1@example.com` to `user50@example.com`
   - Password: `password`

3. **Public/Pending Users (20 users)**
   - Unverified accounts
   - Incomplete registration
   - Pending KYC status
   - Email pattern: `guest1@example.com` to `guest20@example.com`
   - Password: `password`

### Data Coverage

#### Products (15 products)
- Complete product information
- 3-5 highlights per product
- 2-4 founders per product
- 2-5 funding rounds per product
- 4-8 key metrics per product
- 3-6 risk disclosures per product
- 10-30 price history entries per product

#### Plans & Subscriptions
- 5 subscription plans with features and configs
- 100 subscriptions across users
- Multiple payments per subscription (1-12 payments)
- Various payment statuses

#### Financial System
- Wallets for all users with balances
- 5-15 transactions per wallet
- 20 withdrawal requests
- 150+ user investments
- Bulk purchases linked to products

#### Support System
- 40 support tickets
- 1-8 messages per ticket
- Assigned to admin users
- Various ticket statuses

#### Content Management
- 15 CMS pages
- 25 blog posts
- 3 menus with 5-10 items each
- 5 active banners
- Knowledge base with 5 categories and 3-8 articles each

#### Other Data
- Bonus transactions
- Referral campaigns and referrals
- 10 URL redirects
- 200+ activity logs
- 100+ notifications

## Usage

### Run the Comprehensive Seeder

```bash
cd backend
php artisan db:seed --class=ComprehensiveFakerSeeder
```

### Run with Fresh Migration

```bash
cd backend
php artisan migrate:fresh --seed --seeder=ComprehensiveFakerSeeder
```

### Run Specific Seeders

Individual seeders are still available:
```bash
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=PlanSeed
```

## Test Credentials

### Admin Access
- **Email:** `admin@preipo.com`
- **Password:** `password`
- **Role:** Super Admin

### Regular User Access
- **Email:** `user1@example.com` (or user2, user3, etc.)
- **Password:** `password`
- **Role:** User

### Public User Access
- **Email:** `guest1@example.com` (or guest2, guest3, etc.)
- **Password:** `password`
- **Role:** None (Pending)

## Database Requirements

Ensure all migrations are run before seeding:
```bash
php artisan migrate
```

## Factories Available

All models have corresponding factories for easy testing:

- `UserFactory` - Creates users with profiles, KYC, and wallets
- `ProductFactory` - Creates products with all related data
- `ProductHighlightFactory`
- `ProductFounderFactory`
- `ProductFundingRoundFactory`
- `ProductKeyMetricFactory`
- `ProductRiskDisclosureFactory`
- `ProductPriceHistoryFactory`
- `PlanFactory` - Creates plans with features and configs
- `PlanFeatureFactory`
- `PlanConfigFactory`
- `SubscriptionFactory`
- `PaymentFactory`
- `WalletFactory`
- `TransactionFactory`
- `WithdrawalFactory`
- `UserInvestmentFactory`
- `BulkPurchaseFactory`
- `BonusTransactionFactory`
- `ReferralCampaignFactory`
- `ReferralFactory`
- `SupportTicketFactory`
- `SupportMessageFactory`
- `PageFactory`
- `BlogPostFactory`
- `MenuFactory`
- `MenuItemFactory`
- `BannerFactory`
- `KbCategoryFactory`
- `KbArticleFactory`
- `RedirectFactory`
- `ActivityLogFactory`
- `NotificationFactory`
- `UserProfileFactory`
- `UserKycFactory`
- `KycDocumentFactory`

## Usage in Tests

You can use these factories in your tests:

```php
use App\Models\User;
use App\Models\Product;

// Create a verified user
$user = User::factory()->create();

// Create a featured product
$product = Product::factory()->featured()->create();

// Create a subscription with payments
$subscription = Subscription::factory()
    ->has(Payment::factory()->count(3))
    ->create();
```

## Notes

- All passwords are set to `password` for testing
- KYC documents are simulated with fake file paths
- Transaction amounts and balances are realistic but random
- All timestamps are properly set for realistic data flow
- Referral codes are unique and randomly generated
- Foreign key relationships are properly maintained

## Development

To add more data, modify the counts in `ComprehensiveFakerSeeder.php`:

```php
// Example: Increase admin users from 2 to 5
for ($i = 1; $i <= 5; $i++) {
    // Create admin...
}
```

## Troubleshooting

### Foreign Key Constraints
If you encounter foreign key errors, ensure:
1. Migrations are run in correct order
2. Dependencies are seeded before dependent data

### Unique Constraints
If you get unique constraint violations:
1. Run `migrate:fresh` to reset the database
2. Ensure Faker's `unique()` is properly used

### Memory Issues
For large datasets, increase PHP memory:
```bash
php -d memory_limit=512M artisan db:seed --class=ComprehensiveFakerSeeder
```
