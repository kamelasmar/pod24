# Pod24 — Plan 1: Foundation & Catalog

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bootstrap a Laravel 11 monolith with a working Filament admin panel that lets an Admin user configure the Pod24 catalog (facilities, service tiers, pricing matrix, add-ons, hour packs, pricing modifiers, cancellation policies) and seeds the initial Pod24 facility data.

**Architecture:** Single Laravel 11 monolith on PHP 8.3 + PostgreSQL 16 + Redis. Domain code organised under `app/Modules/{Catalog,…}/` for clear bounded contexts; Filament resources stay in `app/Filament/Resources/{Module}/` for default discovery. Money stored as integer minor units (`*_aed_cents`). Translatable text uses `spatie/laravel-translatable` from day one (EN-only at MVP, AR-ready). Roles via `spatie/laravel-permission`. Tests via Pest with TDD on every model.

**Tech Stack:** Laravel 11, PHP 8.3, PostgreSQL 16, Redis, Filament 3, Pest 3, Spatie (permission, translatable, medialibrary).

**Spec reference:** `docs/superpowers/specs/2026-05-04-pod24-platform-design.md` § 3, § 4, § 5.1–5.6.

---

## Pre-flight: file structure for Plan 1

After this plan completes, the repo will look like:

```
pod24/
├── app/
│   ├── Models/User.php                                  (Laravel default + HasRoles)
│   ├── Modules/
│   │   └── Catalog/
│   │       └── Models/
│   │           ├── Facility.php
│   │           ├── ServiceTier.php
│   │           ├── FacilityPricing.php
│   │           ├── Addon.php
│   │           ├── HourPack.php
│   │           ├── PricingModifier.php
│   │           └── CancellationPolicy.php
│   ├── Filament/Resources/Catalog/
│   │   ├── FacilityResource.php (and Pages/, RelationManagers/)
│   │   ├── AddonResource.php
│   │   ├── HourPackResource.php
│   │   ├── PricingModifierResource.php
│   │   └── CancellationPolicyResource.php
│   └── Providers/Filament/AdminPanelProvider.php       (Filament default + 2FA + role gate)
├── database/
│   ├── migrations/                                      (all migration files, default location)
│   ├── factories/                                       (factories for catalog models)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolesAndPermissionsSeeder.php
│       └── Pod24CatalogSeeder.php
├── tests/
│   ├── Pest.php
│   ├── Unit/Catalog/                                    (model unit tests)
│   └── Feature/Catalog/                                 (Filament resource feature tests)
├── docs/superpowers/
│   ├── specs/
│   └── plans/
└── (Laravel default files: composer.json, .env, etc.)
```

---

## Task 1: Bootstrap Laravel 11 into the existing repo

**Files:**
- Create: entire Laravel skeleton in `/Users/kamelasmar/apps/pod24/`
- Preserve: existing `docs/` and `.git/`

- [ ] **Step 1: Create Laravel skeleton in a temp dir, then merge in**

```bash
cd /tmp
composer create-project laravel/laravel pod24-bootstrap "^11.0" --no-interaction
rsync -a --exclude='.git' /tmp/pod24-bootstrap/ /Users/kamelasmar/apps/pod24/
rm -rf /tmp/pod24-bootstrap
cd /Users/kamelasmar/apps/pod24
```

- [ ] **Step 2: Generate app key**

```bash
php artisan key:generate
```

Expected: `INFO  Application key set successfully.`

- [ ] **Step 3: Verify default tests pass**

```bash
php artisan test
```

Expected: 2 passing tests (Laravel's default `ExampleTest`s).

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Bootstrap Laravel 11 skeleton"
```

---

## Task 2: Configure PostgreSQL + Redis

**Files:**
- Modify: `.env`
- Modify: `config/database.php` (verify pgsql defaults are sensible)

- [ ] **Step 1: Ensure local services are running**

```bash
brew services start postgresql@16
brew services start redis
createdb pod24 2>/dev/null || echo "db exists"
```

- [ ] **Step 2: Update `.env` (replace DB + cache + queue blocks)**

Open `.env` and set:

```
APP_NAME=Pod24
APP_URL=http://pod24.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pod24
DB_USERNAME=kamelasmar
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

- [ ] **Step 3: Run migrations to verify connectivity**

```bash
php artisan migrate
```

Expected: Default Laravel migrations (users, cache, jobs) run cleanly against Postgres.

- [ ] **Step 4: Commit**

```bash
git add .env.example  # update .env.example to match (keep secrets out)
# Manually edit .env.example to mirror .env structure with placeholder values
git add -A
git commit -m "Configure PostgreSQL and Redis"
```

---

## Task 3: Install and initialise Pest

**Files:**
- Modify: `composer.json`
- Create: `tests/Pest.php` (Pest's init writes this)
- Modify: `phpunit.xml`

- [ ] **Step 1: Install Pest**

```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies
php artisan pest:install
```

Expected: `tests/Pest.php` created, existing PHPUnit tests still recognised.

- [ ] **Step 2: Convert default tests to Pest syntax**

Replace contents of `tests/Feature/ExampleTest.php`:

```php
<?php

it('returns a successful response', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});
```

Replace contents of `tests/Unit/ExampleTest.php`:

```php
<?php

it('passes a basic truthy assertion', function () {
    expect(true)->toBeTrue();
});
```

- [ ] **Step 3: Run Pest**

```bash
./vendor/bin/pest
```

Expected: 2 passed.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Add Pest test runner"
```

---

## Task 4: Create the Modules directory structure

**Files:**
- Create: `app/Modules/Catalog/Models/.gitkeep`
- Modify: `composer.json` (verify PSR-4 covers nested namespace — it does by default since `App\` -> `app/`, no change needed)

- [ ] **Step 1: Create directory**

```bash
mkdir -p app/Modules/Catalog/Models
touch app/Modules/Catalog/Models/.gitkeep
```

- [ ] **Step 2: Verify autoload works**

```bash
composer dump-autoload
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Create Modules directory structure"
```

---

## Task 5: Install spatie/laravel-permission

**Files:**
- Modify: `composer.json`
- Create: migration for permission tables (published)
- Modify: `app/Models/User.php` (add HasRoles trait)
- Create: `tests/Unit/UserRolesTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/UserRolesTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('can assign a role to a user', function () {
    Role::create(['name' => 'Admin']);
    $user = User::factory()->create();
    $user->assignRole('Admin');
    expect($user->hasRole('Admin'))->toBeTrue();
});
```

- [ ] **Step 2: Run test, expect failure**

```bash
./vendor/bin/pest tests/Unit/UserRolesTest.php
```

Expected: FAIL — `Role` class missing.

- [ ] **Step 3: Install package**

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

- [ ] **Step 4: Add HasRoles trait to User model**

Edit `app/Models/User.php`. Add use statement and trait:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;
    // ...existing code...
}
```

- [ ] **Step 5: Run test, expect pass**

```bash
./vendor/bin/pest tests/Unit/UserRolesTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Install spatie/laravel-permission and HasRoles trait"
```

---

## Task 6: Install spatie/laravel-translatable

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Install**

```bash
composer require spatie/laravel-translatable
```

- [ ] **Step 2: Verify install**

```bash
composer show spatie/laravel-translatable | head -3
```

Expected: package metadata visible.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Install spatie/laravel-translatable"
```

---

## Task 7: Install spatie/laravel-medialibrary

**Files:**
- Modify: `composer.json`
- Create: `database/migrations/...create_media_table.php` (published)

- [ ] **Step 1: Install + publish + migrate**

```bash
composer require "spatie/laravel-medialibrary:^11.0"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

- [ ] **Step 2: Verify media table exists**

```bash
php artisan tinker --execute="echo Schema::hasTable('media') ? 'yes' : 'no';"
```

Expected: `yes`.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Install spatie/laravel-medialibrary"
```

---

## Task 8: Install Filament 3 with admin panel

**Files:**
- Modify: `composer.json`
- Create: `app/Providers/Filament/AdminPanelProvider.php` (generated)
- Modify: `bootstrap/providers.php` (registers AdminPanelProvider)

- [ ] **Step 1: Install**

```bash
composer require filament/filament:"^3.2" -W
php artisan filament:install --panels
```

When prompted "ID for the panel": enter `admin`.

- [ ] **Step 2: Open `app/Providers/Filament/AdminPanelProvider.php`** and replace the `panel()` method body with:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->login()
        ->colors([
            'primary' => Color::hex('#00B9E3'),
        ])
        ->discoverResources(in: app_path('Filament/Resources/Catalog'), for: 'App\\Filament\\Resources\\Catalog')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->pages([
            Pages\Dashboard::class,
        ])
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->widgets([
            Widgets\AccountWidget::class,
            Widgets\FilamentInfoWidget::class,
        ])
        ->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ])
        ->authMiddleware([
            Authenticate::class,
        ])
        ->authGuard('web');
}
```

Add this import at the top: `use Filament\Support\Colors\Color;`

- [ ] **Step 3: Create the Catalog resources directory**

```bash
mkdir -p app/Filament/Resources/Catalog
touch app/Filament/Resources/Catalog/.gitkeep
```

- [ ] **Step 4: Verify Filament boots**

```bash
php artisan serve &
sleep 2
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/admin/login
kill %1
```

Expected: HTTP `200`.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Install Filament 3 admin panel"
```

---

## Task 9: Implement User access gate for Filament (FilamentUser contract)

**Files:**
- Modify: `app/Models/User.php`
- Create: `tests/Feature/AdminAccessTest.php`

- [ ] **Step 1: Write failing test**

`tests/Feature/AdminAccessTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('blocks non-admin users from /admin', function () {
    Role::firstOrCreate(['name' => 'Admin']);
    Role::firstOrCreate(['name' => 'Coordinator']);

    $regular = User::factory()->create();
    $this->actingAs($regular)->get('/admin')->assertForbidden();
});

it('allows Admin role users into /admin', function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('allows Coordinator role users into /admin', function () {
    Role::firstOrCreate(['name' => 'Coordinator']);
    $user = User::factory()->create();
    $user->assignRole('Coordinator');
    $this->actingAs($user)->get('/admin')->assertOk();
});
```

- [ ] **Step 2: Run test, expect failure**

```bash
./vendor/bin/pest tests/Feature/AdminAccessTest.php
```

Expected: third test fails or all fail (no gate).

- [ ] **Step 3: Implement `FilamentUser` on User model**

Edit `app/Models/User.php` — add interface and method:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['Admin', 'Coordinator']);
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
./vendor/bin/pest tests/Feature/AdminAccessTest.php
```

Expected: 3 passed.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Gate Filament admin to Admin and Coordinator roles"
```

---

## Task 10: Create RolesAndPermissionsSeeder

**Files:**
- Create: `database/seeders/RolesAndPermissionsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `tests/Feature/SeederTest.php`

- [ ] **Step 1: Write failing test**

`tests/Feature/SeederTest.php`:

```php
<?php

use Spatie\Permission\Models\Role;

it('seeds Admin and Coordinator roles', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    expect(Role::where('name', 'Admin')->exists())->toBeTrue();
    expect(Role::where('name', 'Coordinator')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run, expect failure**

```bash
./vendor/bin/pest tests/Feature/SeederTest.php
```

Expected: FAIL — seeder class missing.

- [ ] **Step 3: Create seeder**

`database/seeders/RolesAndPermissionsSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Coordinator']);
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
./vendor/bin/pest tests/Feature/SeederTest.php
```

Expected: PASS.

- [ ] **Step 5: Wire into DatabaseSeeder**

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add RolesAndPermissionsSeeder"
```

---

## Task 11: Create the initial admin user via artisan command

**Files:**
- Create: `app/Console/Commands/CreateAdminUser.php`

- [ ] **Step 1: Generate command**

```bash
php artisan make:command CreateAdminUser
```

- [ ] **Step 2: Implement the command**

`app/Console/Commands/CreateAdminUser.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'pod24:create-admin {email} {name} {password}';
    protected $description = 'Create an Admin user for the Pod24 admin panel';

    public function handle(): int
    {
        Role::firstOrCreate(['name' => 'Admin']);

        $user = User::firstOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->argument('name'),
                'password' => Hash::make($this->argument('password')),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('Admin')) {
            $user->assignRole('Admin');
        }

        $this->info("Admin user created/updated: {$user->email}");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Test the command**

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan pod24:create-admin admin@pod24.local "Pod24 Admin" changeme123
```

Expected: `Admin user created/updated: admin@pod24.local`

- [ ] **Step 4: Smoke-test admin login manually**

```bash
php artisan serve &
# Browser: http://127.0.0.1:8000/admin → log in with admin@pod24.local / changeme123
# Should see Filament dashboard.
kill %1
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add pod24:create-admin artisan command"
```

---

## Task 12: Create Facility model + migration + factory

**Files:**
- Create: `app/Modules/Catalog/Models/Facility.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_facilities_table.php`
- Create: `database/factories/FacilityFactory.php`
- Create: `tests/Unit/Catalog/FacilityTest.php`

- [ ] **Step 1: Write failing test**

`tests/Unit/Catalog/FacilityTest.php`:

```php
<?php

use App\Modules\Catalog\Models\Facility;

it('creates a facility with translatable name and description', function () {
    $facility = Facility::factory()->create([
        'slug' => 'pod24-portable',
        'name' => ['en' => 'Pod24 Portable Studio'],
        'description' => ['en' => 'Broadcast-grade portable podcast pod.'],
        'is_active' => true,
    ]);

    expect($facility->fresh()->getTranslation('name', 'en'))->toBe('Pod24 Portable Studio');
    expect($facility->is_active)->toBeTrue();
});

it('stores name as JSON to support multiple locales', function () {
    $facility = Facility::factory()->create([
        'name' => ['en' => 'Pod24', 'ar' => 'بود٢٤'],
    ]);

    expect($facility->fresh()->getTranslation('name', 'ar'))->toBe('بود٢٤');
});
```

- [ ] **Step 2: Run, expect failure**

```bash
./vendor/bin/pest tests/Unit/Catalog/FacilityTest.php
```

Expected: FAIL — class missing.

- [ ] **Step 3: Create migration**

```bash
php artisan make:migration create_facilities_table
```

Edit the new migration file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
```

- [ ] **Step 4: Create the model**

`app/Modules/Catalog/Models/Facility.php`:

```php
<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Facility extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['slug', 'name', 'description', 'address', 'is_active', 'sort_order'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\FacilityFactory::new();
    }
}
```

- [ ] **Step 5: Create the factory**

`database/factories/FacilityFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => ['en' => $this->faker->company().' Studio'],
            'description' => ['en' => $this->faker->paragraph()],
            'address' => ['city' => 'Abu Dhabi', 'country' => 'AE'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
```

- [ ] **Step 6: Run migration + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Unit/Catalog/FacilityTest.php
```

Expected: 2 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add Facility model with translatable fields"
```

---

## Task 13: Create ServiceTier model + migration + factory

**Files:**
- Create: `app/Modules/Catalog/Models/ServiceTier.php`
- Create: `database/migrations/...create_service_tiers_table.php`
- Create: `database/factories/ServiceTierFactory.php`
- Create: `tests/Unit/Catalog/ServiceTierTest.php`

- [ ] **Step 1: Write failing test**

`tests/Unit/Catalog/ServiceTierTest.php`:

```php
<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('belongs to a facility', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Recording Only',
        'base_hourly_rate_aed_cents' => 25400,
    ]);

    expect($tier->facility->id)->toBe($facility->id);
    expect($tier->base_hourly_rate_aed_cents)->toBe(25400);
});

it('orders tiers by sort_order ascending', function () {
    $facility = Facility::factory()->create();
    ServiceTier::factory()->for($facility)->create(['name' => 'B', 'sort_order' => 2]);
    ServiceTier::factory()->for($facility)->create(['name' => 'A', 'sort_order' => 1]);

    $tiers = $facility->serviceTiers()->orderBy('sort_order')->pluck('name');
    expect($tiers->toArray())->toBe(['A', 'B']);
});
```

- [ ] **Step 2: Run, expect failure**

```bash
./vendor/bin/pest tests/Unit/Catalog/ServiceTierTest.php
```

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_service_tiers_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('base_hourly_rate_aed_cents');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['facility_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tiers');
    }
};
```

- [ ] **Step 4: Model**

`app/Modules/Catalog/Models/ServiceTier.php`:

```php
<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ServiceTier extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['facility_id', 'name', 'description', 'base_hourly_rate_aed_cents', 'sort_order', 'is_active'];

    public array $translatable = ['description'];

    protected function casts(): array
    {
        return [
            'base_hourly_rate_aed_cents' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ServiceTierFactory::new();
    }
}
```

- [ ] **Step 5: Add `serviceTiers()` relation to Facility**

Edit `app/Modules/Catalog/Models/Facility.php` — add at bottom of class:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

// ...

public function serviceTiers(): HasMany
{
    return $this->hasMany(ServiceTier::class);
}
```

- [ ] **Step 6: Factory**

`database/factories/ServiceTierFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTierFactory extends Factory
{
    protected $model = ServiceTier::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'name' => $this->faker->randomElement(['Recording Only', 'Live Mix', 'Live Mix + Edit']),
            'description' => ['en' => $this->faker->sentence()],
            'base_hourly_rate_aed_cents' => $this->faker->numberBetween(10000, 100000),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 7: Run migration + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Unit/Catalog/ServiceTierTest.php
```

Expected: 2 passed.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add ServiceTier model"
```

---

## Task 14: Create FacilityPricing model + migration + factory

**Files:**
- Create: `app/Modules/Catalog/Models/FacilityPricing.php`
- Create: `database/migrations/...create_facility_pricing_table.php`
- Create: `database/factories/FacilityPricingFactory.php`
- Create: `tests/Unit/Catalog/FacilityPricingTest.php`

- [ ] **Step 1: Write failing test**

`tests/Unit/Catalog/FacilityPricingTest.php`:

```php
<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;

it('stores a price for a facility-tier-package cell', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    $pricing = FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);

    expect($pricing->price_aed_cents)->toBe(25400);
});

it('enforces uniqueness on (facility, tier, package_type)', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);

    expect(fn () => FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 30000,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run, expect failure.**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_facility_pricing_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facility_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_tier_id')->constrained()->cascadeOnDelete();
            $table->enum('package_type', ['hourly', 'half_day', 'full_day', 'multi_day']);
            $table->unsignedInteger('hours')->default(1);   // for half/full-day = 4/8; multi-day = 1 (per-day)
            $table->unsignedInteger('price_aed_cents');
            $table->timestamps();

            $table->unique(['facility_id', 'service_tier_id', 'package_type'], 'fp_facility_tier_package_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_pricing');
    }
};
```

- [ ] **Step 4: Model**

`app/Modules/Catalog/Models/FacilityPricing.php`:

```php
<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityPricing extends Model
{
    use HasFactory;

    protected $table = 'facility_pricing';

    protected $fillable = ['facility_id', 'service_tier_id', 'package_type', 'hours', 'price_aed_cents'];

    public const PACKAGE_TYPES = ['hourly', 'half_day', 'full_day', 'multi_day'];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'price_aed_cents' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function serviceTier(): BelongsTo
    {
        return $this->belongsTo(ServiceTier::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\FacilityPricingFactory::new();
    }
}
```

- [ ] **Step 5: Factory**

`database/factories/FacilityPricingFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacilityPricingFactory extends Factory
{
    protected $model = FacilityPricing::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'service_tier_id' => ServiceTier::factory(),
            'package_type' => 'hourly',
            'hours' => 1,
            'price_aed_cents' => 25400,
        ];
    }
}
```

- [ ] **Step 6: Migrate + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Unit/Catalog/FacilityPricingTest.php
```

Expected: 2 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add FacilityPricing model with composite uniqueness"
```

---

## Task 15: Create Addon model + migration + factory

**Files:**
- Create: `app/Modules/Catalog/Models/Addon.php`
- Create: `database/migrations/...create_addons_table.php`
- Create: `database/factories/AddonFactory.php`
- Create: `tests/Unit/Catalog/AddonTest.php`

- [ ] **Step 1: Write failing test**

`tests/Unit/Catalog/AddonTest.php`:

```php
<?php

use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;

it('creates an addon attached to a facility', function () {
    $facility = Facility::factory()->create();

    $addon = Addon::factory()->for($facility)->create([
        'name' => ['en' => 'Episode editing'],
        'price_aed_cents' => 50000,
    ]);

    expect($addon->getTranslation('name', 'en'))->toBe('Episode editing');
    expect($addon->price_aed_cents)->toBe(50000);
    expect($addon->facility->id)->toBe($facility->id);
});
```

- [ ] **Step 2: Run, expect failure.**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_addons_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('price_aed_cents');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addons');
    }
};
```

- [ ] **Step 4: Model**

`app/Modules/Catalog/Models/Addon.php`:

```php
<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Addon extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['facility_id', 'name', 'description', 'price_aed_cents', 'is_active', 'sort_order'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'price_aed_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\AddonFactory::new();
    }
}
```

- [ ] **Step 5: Factory**

`database/factories/AddonFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddonFactory extends Factory
{
    protected $model = Addon::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'name' => ['en' => $this->faker->randomElement(['Episode editing', 'Social clips pack', 'Cover art', 'Distribution'])],
            'description' => ['en' => $this->faker->sentence()],
            'price_aed_cents' => $this->faker->numberBetween(10000, 200000),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
```

- [ ] **Step 6: Migrate + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Unit/Catalog/AddonTest.php
```

Expected: 1 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add Addon model"
```

---

## Task 16: Create HourPack model + migration + factory

**Files:**
- Create: `app/Modules/Catalog/Models/HourPack.php`
- Create: `database/migrations/...create_hour_packs_table.php`
- Create: `database/factories/HourPackFactory.php`
- Create: `tests/Unit/Catalog/HourPackTest.php`

- [ ] **Step 1: Write failing test**

`tests/Unit/Catalog/HourPackTest.php`:

```php
<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;

it('creates an hour pack with hours, price, and expiry', function () {
    $facility = Facility::factory()->create();

    $pack = HourPack::factory()->for($facility)->create([
        'hours' => 10,
        'price_aed_cents' => 228600,
        'expiry_days' => 365,
    ]);

    expect($pack->hours)->toBe(10);
    expect($pack->price_aed_cents)->toBe(228600);
    expect($pack->expiry_days)->toBe(365);
});
```

- [ ] **Step 2: Run, expect failure.**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_hour_packs_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hour_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('hours');
            $table->unsignedInteger('price_aed_cents');
            $table->unsignedInteger('expiry_days')->default(365);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_packs');
    }
};
```

- [ ] **Step 4: Model**

`app/Modules/Catalog/Models/HourPack.php`:

```php
<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class HourPack extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['facility_id', 'name', 'description', 'hours', 'price_aed_cents', 'expiry_days', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'price_aed_cents' => 'integer',
            'expiry_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\HourPackFactory::new();
    }
}
```

- [ ] **Step 5: Factory**

`database/factories/HourPackFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use Illuminate\Database\Eloquent\Factories\Factory;

class HourPackFactory extends Factory
{
    protected $model = HourPack::class;

    public function definition(): array
    {
        $hours = $this->faker->randomElement([10, 20, 40]);
        return [
            'facility_id' => Facility::factory(),
            'name' => ['en' => "{$hours}-Hour Pack"],
            'description' => ['en' => 'Pre-paid recording hours, valid for 12 months.'],
            'hours' => $hours,
            'price_aed_cents' => $hours * 25400 * 0.9,
            'expiry_days' => 365,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 6: Migrate + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Unit/Catalog/HourPackTest.php
```

Expected: 1 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add HourPack model"
```

---

## Task 17: Create PricingModifier model + migration + factory

**Files:**
- Create: `app/Modules/Catalog/Models/PricingModifier.php`
- Create: `database/migrations/...create_pricing_modifiers_table.php`
- Create: `database/factories/PricingModifierFactory.php`
- Create: `tests/Unit/Catalog/PricingModifierTest.php`

- [ ] **Step 1: Write failing test**

`tests/Unit/Catalog/PricingModifierTest.php`:

```php
<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\PricingModifier;

it('creates a weekend modifier with a percentage', function () {
    $facility = Facility::factory()->create();

    $mod = PricingModifier::factory()->for($facility)->create([
        'type' => 'weekend',
        'percentage' => 25,
    ]);

    expect($mod->type)->toBe('weekend');
    expect($mod->percentage)->toBe(25);
});

it('creates an after-hours modifier with start/end window', function () {
    $facility = Facility::factory()->create();

    $mod = PricingModifier::factory()->for($facility)->create([
        'type' => 'after_hours',
        'percentage' => 25,
        'after_hours_start' => '18:00',
        'after_hours_end' => '09:00',
    ]);

    expect($mod->after_hours_start)->toBe('18:00:00');
});
```

- [ ] **Step 2: Run, expect failure.**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_pricing_modifiers_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pricing_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['weekend', 'after_hours']);
            $table->unsignedInteger('percentage');             // e.g., 25 = +25%
            $table->time('after_hours_start')->nullable();
            $table->time('after_hours_end')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_modifiers');
    }
};
```

- [ ] **Step 4: Model**

`app/Modules/Catalog/Models/PricingModifier.php`:

```php
<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingModifier extends Model
{
    use HasFactory;

    protected $fillable = ['facility_id', 'type', 'percentage', 'after_hours_start', 'after_hours_end'];

    public const TYPES = ['weekend', 'after_hours'];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\PricingModifierFactory::new();
    }
}
```

- [ ] **Step 5: Factory**

`database/factories/PricingModifierFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\PricingModifier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingModifierFactory extends Factory
{
    protected $model = PricingModifier::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'type' => 'weekend',
            'percentage' => 25,
            'after_hours_start' => null,
            'after_hours_end' => null,
        ];
    }
}
```

- [ ] **Step 6: Migrate + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Unit/Catalog/PricingModifierTest.php
```

Expected: 2 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add PricingModifier model"
```

---

## Task 18: Create CancellationPolicy model + migration + factory

**Files:**
- Create: `app/Modules/Catalog/Models/CancellationPolicy.php`
- Create: `database/migrations/...create_cancellation_policies_table.php`
- Create: `database/factories/CancellationPolicyFactory.php`
- Create: `tests/Unit/Catalog/CancellationPolicyTest.php`

- [ ] **Step 1: Write failing test**

`tests/Unit/Catalog/CancellationPolicyTest.php`:

```php
<?php

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;

it('creates a refund tier for a facility', function () {
    $facility = Facility::factory()->create();

    $policy = CancellationPolicy::factory()->for($facility)->create([
        'hours_before_min' => 168,
        'refund_percentage' => 100,
    ]);

    expect($policy->hours_before_min)->toBe(168);
    expect($policy->refund_percentage)->toBe(100);
});
```

- [ ] **Step 2: Run, expect failure.**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_cancellation_policies_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('hours_before_min');
            $table->unsignedTinyInteger('refund_percentage');  // 0-100
            $table->timestamps();

            $table->index(['facility_id', 'hours_before_min']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
    }
};
```

- [ ] **Step 4: Model**

`app/Modules/Catalog/Models/CancellationPolicy.php`:

```php
<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicy extends Model
{
    use HasFactory;

    protected $fillable = ['facility_id', 'hours_before_min', 'refund_percentage'];

    protected function casts(): array
    {
        return [
            'hours_before_min' => 'integer',
            'refund_percentage' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\CancellationPolicyFactory::new();
    }
}
```

- [ ] **Step 5: Factory**

`database/factories/CancellationPolicyFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class CancellationPolicyFactory extends Factory
{
    protected $model = CancellationPolicy::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'hours_before_min' => 168,
            'refund_percentage' => 100,
        ];
    }
}
```

- [ ] **Step 6: Migrate + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Unit/Catalog/CancellationPolicyTest.php
```

Expected: 1 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add CancellationPolicy model"
```

---

## Task 19: Filament resource — Facility (with photo upload)

**Files:**
- Create: `app/Filament/Resources/Catalog/FacilityResource.php`
- Create: `app/Filament/Resources/Catalog/FacilityResource/Pages/{ListFacilities,CreateFacility,EditFacility}.php`
- Modify: `app/Modules/Catalog/Models/Facility.php` (add HasMedia)
- Create: `tests/Feature/Catalog/FacilityResourceTest.php`

- [ ] **Step 1: Add medialibrary support to Facility model**

Edit `app/Modules/Catalog/Models/Facility.php`. Add interface and trait:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Facility extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;
    // ...rest unchanged...
}
```

- [ ] **Step 2: Generate Filament resource**

```bash
php artisan make:filament-resource Catalog/Facility --generate
```

When prompted "Model namespace": enter `App\Modules\Catalog\Models\Facility`.

- [ ] **Step 3: Edit `app/Filament/Resources/Catalog/FacilityResource.php`** to use translatable + media fields. Replace generated form() method:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
        Forms\Components\TextInput::make('name.en')->label('Name (EN)')->required(),
        Forms\Components\Textarea::make('description.en')->label('Description (EN)'),
        Forms\Components\KeyValue::make('address')->keyLabel('Field')->valueLabel('Value'),
        Forms\Components\SpatieMediaLibraryFileUpload::make('photo')->collection('photo')->image()->imageEditor(),
        Forms\Components\Toggle::make('is_active')->default(true),
        Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('slug')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('name.en')->label('Name')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
```

- [ ] **Step 4: Write failing test**

`tests/Feature/Catalog/FacilityResourceTest.php`:

```php
<?php

use App\Filament\Resources\Catalog\FacilityResource;
use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $this->actingAs($this->admin);
});

it('lists facilities in the admin panel', function () {
    Facility::factory()->count(3)->create();

    $this->get(FacilityResource::getUrl('index'))
        ->assertOk();
});

it('can create a facility via Filament', function () {
    \Livewire\Livewire::test(\App\Filament\Resources\Catalog\FacilityResource\Pages\CreateFacility::class)
        ->fillForm([
            'slug' => 'pod24-portable',
            'name' => ['en' => 'Pod24 Portable'],
            'description' => ['en' => 'A portable pod.'],
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('facilities', ['slug' => 'pod24-portable']);
});
```

- [ ] **Step 5: Run tests**

```bash
./vendor/bin/pest tests/Feature/Catalog/FacilityResourceTest.php
```

Expected: 2 passed (after wiring imports + Livewire route registration).

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add Filament FacilityResource with photo upload"
```

---

## Task 20: Filament — ServiceTier as RelationManager under Facility

**Files:**
- Create: `app/Filament/Resources/Catalog/FacilityResource/RelationManagers/ServiceTiersRelationManager.php`
- Modify: `app/Filament/Resources/Catalog/FacilityResource.php` (register relation manager)
- Create: `tests/Feature/Catalog/ServiceTierRelationTest.php`

- [ ] **Step 1: Generate the relation manager**

```bash
php artisan make:filament-relation-manager Catalog/FacilityResource serviceTiers name
```

- [ ] **Step 2: Edit `ServiceTiersRelationManager.php`** form/table:

```php
public function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('name')->required(),
        Forms\Components\Textarea::make('description.en')->label('Description (EN)'),
        Forms\Components\TextInput::make('base_hourly_rate_aed_cents')
            ->label('Base hourly rate (AED cents)')
            ->numeric()
            ->required()
            ->helperText('AED 254.00 = 25400 cents'),
        Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        Forms\Components\Toggle::make('is_active')->default(true),
    ]);
}

public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('name')
        ->columns([
            Tables\Columns\TextColumn::make('name')->sortable(),
            Tables\Columns\TextColumn::make('base_hourly_rate_aed_cents')
                ->label('Rate')
                ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('sort_order'),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
```

- [ ] **Step 3: Register the relation manager in FacilityResource**

In `FacilityResource.php`, add:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\ServiceTiersRelationManager::class,
    ];
}
```

- [ ] **Step 4: Write feature test**

`tests/Feature/Catalog/ServiceTierRelationTest.php`:

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
});

it('shows service tiers attached to a facility', function () {
    $facility = Facility::factory()->create();
    ServiceTier::factory()->count(3)->for($facility)->create();

    $this->get("/admin/catalog/facilities/{$facility->id}/edit")
        ->assertOk()
        ->assertSee($facility->getTranslation('name', 'en'));
});
```

- [ ] **Step 5: Run tests**

```bash
./vendor/bin/pest tests/Feature/Catalog/
```

Expected: all green.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add ServiceTier RelationManager under Facility"
```

---

## Task 21: Filament — Pricing Matrix editor (custom page)

**Files:**
- Create: `app/Filament/Resources/Catalog/FacilityResource/Pages/PricingMatrix.php`
- Create: `resources/views/filament/resources/catalog/facility-resource/pages/pricing-matrix.blade.php`
- Modify: `FacilityResource.php` (register the new page)
- Create: `tests/Feature/Catalog/PricingMatrixTest.php`

- [ ] **Step 1: Generate the custom page**

```bash
php artisan make:filament-page Catalog/FacilityResource/PricingMatrix --resource=Catalog/FacilityResource --type=custom
```

- [ ] **Step 2: Implement `PricingMatrix.php`**

```php
<?php

namespace App\Filament\Resources\Catalog\FacilityResource\Pages;

use App\Filament\Resources\Catalog\FacilityResource;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class PricingMatrix extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FacilityResource::class;
    protected static string $view = 'filament.resources.catalog.facility-resource.pages.pricing-matrix';

    public Facility $record;
    public array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = Facility::with('serviceTiers')->findOrFail($record);
        $this->loadExistingPricing();
        $this->form->fill($this->data);
    }

    private function loadExistingPricing(): void
    {
        foreach ($this->record->serviceTiers as $tier) {
            foreach (FacilityPricing::PACKAGE_TYPES as $type) {
                $row = FacilityPricing::where([
                    'facility_id' => $this->record->id,
                    'service_tier_id' => $tier->id,
                    'package_type' => $type,
                ])->first();
                $key = "tier_{$tier->id}_{$type}";
                $this->data[$key] = $row?->price_aed_cents;
            }
        }
    }

    public function form(Form $form): Form
    {
        $fields = [];
        foreach ($this->record->serviceTiers as $tier) {
            foreach (FacilityPricing::PACKAGE_TYPES as $type) {
                $key = "tier_{$tier->id}_{$type}";
                $fields[] = Forms\Components\TextInput::make($key)
                    ->label("{$tier->name} — ".str_replace('_', ' ', $type).' (AED cents)')
                    ->numeric();
            }
        }
        return $form->schema($fields)->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($this->record->serviceTiers as $tier) {
            foreach (FacilityPricing::PACKAGE_TYPES as $type) {
                $key = "tier_{$tier->id}_{$type}";
                if (filled($data[$key] ?? null)) {
                    FacilityPricing::updateOrCreate(
                        [
                            'facility_id' => $this->record->id,
                            'service_tier_id' => $tier->id,
                            'package_type' => $type,
                        ],
                        [
                            'hours' => match ($type) {
                                'half_day' => 4,
                                'full_day' => 8,
                                default => 1,
                            },
                            'price_aed_cents' => (int) $data[$key],
                        ]
                    );
                }
            }
        }
        \Filament\Notifications\Notification::make()->title('Pricing saved')->success()->send();
    }
}
```

- [ ] **Step 3: View**

`resources/views/filament/resources/catalog/facility-resource/pages/pricing-matrix.blade.php`:

```blade
<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div class="mt-4">
            <x-filament::button type="submit">Save pricing</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
```

- [ ] **Step 4: Register the page in FacilityResource**

In `FacilityResource.php`, modify `getPages()`:

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListFacilities::route('/'),
        'create' => Pages\CreateFacility::route('/create'),
        'edit' => Pages\EditFacility::route('/{record}/edit'),
        'pricing' => Pages\PricingMatrix::route('/{record}/pricing'),
    ];
}
```

Also add an "Edit pricing" header action on the EditFacility page. Open `app/Filament/Resources/Catalog/FacilityResource/Pages/EditFacility.php`:

```php
protected function getHeaderActions(): array
{
    return [
        \Filament\Actions\Action::make('pricing')
            ->label('Edit pricing matrix')
            ->url(fn () => static::getResource()::getUrl('pricing', ['record' => $this->record])),
        Actions\DeleteAction::make(),
    ];
}
```

- [ ] **Step 5: Test**

`tests/Feature/Catalog/PricingMatrixTest.php`:

```php
<?php

use App\Filament\Resources\Catalog\FacilityResource\Pages\PricingMatrix;
use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
});

it('saves pricing matrix entries', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    \Livewire\Livewire::test(PricingMatrix::class, ['record' => $facility->id])
        ->fillForm([
            "tier_{$tier->id}_hourly" => 25400,
            "tier_{$tier->id}_half_day" => 90000,
        ])
        ->call('save');

    expect(FacilityPricing::count())->toBe(2);
    expect(FacilityPricing::where('package_type', 'hourly')->first()->price_aed_cents)->toBe(25400);
});
```

```bash
./vendor/bin/pest tests/Feature/Catalog/PricingMatrixTest.php
```

Expected: 1 passed.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add Pricing Matrix custom Filament page"
```

---

## Task 22: Filament resources — Addon, HourPack, PricingModifier, CancellationPolicy

These four resources are simple CRUD. One task batched (form/table follow the same shape; show full code for each).

**Files (one resource each):**
- Create: `app/Filament/Resources/Catalog/AddonResource.php` + Pages
- Create: `app/Filament/Resources/Catalog/HourPackResource.php` + Pages
- Create: `app/Filament/Resources/Catalog/PricingModifierResource.php` + Pages
- Create: `app/Filament/Resources/Catalog/CancellationPolicyResource.php` + Pages
- Create: `tests/Feature/Catalog/CatalogResourcesTest.php`

- [ ] **Step 1: Generate all four resources**

```bash
php artisan make:filament-resource Catalog/Addon --generate
php artisan make:filament-resource Catalog/HourPack --generate
php artisan make:filament-resource Catalog/PricingModifier --generate
php artisan make:filament-resource Catalog/CancellationPolicy --generate
```

For each, when prompted for the model namespace, enter the corresponding `App\Modules\Catalog\Models\{ModelName}`.

- [ ] **Step 2: Replace generated forms** with the following.

`AddonResource.php` form/table:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Select::make('facility_id')
            ->relationship('facility', 'slug')->required(),
        Forms\Components\TextInput::make('name.en')->label('Name (EN)')->required(),
        Forms\Components\Textarea::make('description.en')->label('Description (EN)'),
        Forms\Components\TextInput::make('price_aed_cents')->label('Price (AED cents)')->numeric()->required(),
        Forms\Components\Toggle::make('is_active')->default(true),
        Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
    ]);
}

public static function table(Table $table): Table
{
    return $table->columns([
        Tables\Columns\TextColumn::make('facility.slug')->label('Facility'),
        Tables\Columns\TextColumn::make('name.en')->label('Name'),
        Tables\Columns\TextColumn::make('price_aed_cents')->label('Price')
            ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
        Tables\Columns\IconColumn::make('is_active')->boolean(),
    ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
}
```

`HourPackResource.php` form/table:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Select::make('facility_id')->relationship('facility', 'slug')->required(),
        Forms\Components\TextInput::make('name.en')->label('Name (EN)')->required(),
        Forms\Components\Textarea::make('description.en')->label('Description (EN)'),
        Forms\Components\TextInput::make('hours')->numeric()->required(),
        Forms\Components\TextInput::make('price_aed_cents')->label('Price (AED cents)')->numeric()->required(),
        Forms\Components\TextInput::make('expiry_days')->numeric()->default(365),
        Forms\Components\Toggle::make('is_active')->default(true),
    ]);
}

public static function table(Table $table): Table
{
    return $table->columns([
        Tables\Columns\TextColumn::make('facility.slug'),
        Tables\Columns\TextColumn::make('name.en')->label('Name'),
        Tables\Columns\TextColumn::make('hours'),
        Tables\Columns\TextColumn::make('price_aed_cents')->formatStateUsing(fn ($s) => 'AED '.number_format($s / 100, 2)),
        Tables\Columns\TextColumn::make('expiry_days'),
        Tables\Columns\IconColumn::make('is_active')->boolean(),
    ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
}
```

`PricingModifierResource.php` form/table:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Select::make('facility_id')->relationship('facility', 'slug')->required(),
        Forms\Components\Select::make('type')
            ->options(['weekend' => 'Weekend', 'after_hours' => 'After hours'])
            ->required()
            ->reactive(),
        Forms\Components\TextInput::make('percentage')->numeric()->required()->suffix('%'),
        Forms\Components\TimePicker::make('after_hours_start')->visible(fn ($get) => $get('type') === 'after_hours'),
        Forms\Components\TimePicker::make('after_hours_end')->visible(fn ($get) => $get('type') === 'after_hours'),
    ]);
}

public static function table(Table $table): Table
{
    return $table->columns([
        Tables\Columns\TextColumn::make('facility.slug'),
        Tables\Columns\TextColumn::make('type'),
        Tables\Columns\TextColumn::make('percentage')->suffix('%'),
        Tables\Columns\TextColumn::make('after_hours_start')->time('H:i'),
        Tables\Columns\TextColumn::make('after_hours_end')->time('H:i'),
    ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
}
```

`CancellationPolicyResource.php` form/table:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Select::make('facility_id')->relationship('facility', 'slug')->required(),
        Forms\Components\TextInput::make('hours_before_min')->numeric()->required()
            ->helperText('e.g., 168 = 7 days'),
        Forms\Components\TextInput::make('refund_percentage')->numeric()->required()->suffix('%'),
    ]);
}

public static function table(Table $table): Table
{
    return $table->columns([
        Tables\Columns\TextColumn::make('facility.slug'),
        Tables\Columns\TextColumn::make('hours_before_min')->sortable(),
        Tables\Columns\TextColumn::make('refund_percentage')->suffix('%'),
    ])->defaultSort('hours_before_min', 'desc')
      ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
}
```

- [ ] **Step 3: Smoke test all four resources**

`tests/Feature/Catalog/CatalogResourcesTest.php`:

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
});

it('lists addons', function () {
    $this->get('/admin/catalog/addons')->assertOk();
});

it('lists hour packs', function () {
    $this->get('/admin/catalog/hour-packs')->assertOk();
});

it('lists pricing modifiers', function () {
    $this->get('/admin/catalog/pricing-modifiers')->assertOk();
});

it('lists cancellation policies', function () {
    $this->get('/admin/catalog/cancellation-policies')->assertOk();
});
```

```bash
./vendor/bin/pest tests/Feature/Catalog/CatalogResourcesTest.php
```

Expected: 4 passed.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Add Addon, HourPack, PricingModifier, CancellationPolicy Filament resources"
```

---

## Task 23: Pod24 catalog seeder (real data)

**Files:**
- Create: `database/seeders/Pod24CatalogSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (call new seeder)
- Create: `tests/Feature/Pod24CatalogSeederTest.php`

- [ ] **Step 1: Write failing test**

`tests/Feature/Pod24CatalogSeederTest.php`:

```php
<?php

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\PricingModifier;
use App\Modules\Catalog\Models\ServiceTier;

it('seeds the Pod24 facility with 4 service tiers', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    expect($facility)->not->toBeNull();
    expect($facility->serviceTiers()->count())->toBe(4);
});

it('seeds Pod24 with the documented hourly base rates', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();

    $recording = $facility->serviceTiers()->where('name', 'Recording Only')->first();
    expect($recording->base_hourly_rate_aed_cents)->toBe(25400);

    $liveMix = $facility->serviceTiers()->where('name', 'Live Mix')->first();
    expect($liveMix->base_hourly_rate_aed_cents)->toBe(35400); // 254 + 100

    $liveMixEdit = $facility->serviceTiers()->where('name', 'Live Mix + Standard Edit')->first();
    expect($liveMixEdit->base_hourly_rate_aed_cents)->toBe(75400); // 254 + 500

    $liveMixEditStream = $facility->serviceTiers()->where('name', 'Live Mix + Standard Edit + Live Stream')->first();
    expect($liveMixEditStream->base_hourly_rate_aed_cents)->toBe(105400); // 254 + 800
});

it('seeds weekend and after-hours modifiers at 25 percent', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    $weekend = PricingModifier::where(['facility_id' => $facility->id, 'type' => 'weekend'])->first();
    $afterHours = PricingModifier::where(['facility_id' => $facility->id, 'type' => 'after_hours'])->first();
    expect($weekend->percentage)->toBe(25);
    expect($afterHours->percentage)->toBe(25);
    expect($afterHours->after_hours_start)->toBe('18:00:00');
    expect($afterHours->after_hours_end)->toBe('09:00:00');
});

it('seeds 7d/3d/0 cancellation tiers', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    $tiers = CancellationPolicy::where('facility_id', $facility->id)->orderByDesc('hours_before_min')->get();
    expect($tiers)->toHaveCount(3);
    expect($tiers[0]->hours_before_min)->toBe(168);
    expect($tiers[0]->refund_percentage)->toBe(100);
    expect($tiers[1]->hours_before_min)->toBe(72);
    expect($tiers[1]->refund_percentage)->toBe(50);
    expect($tiers[2]->hours_before_min)->toBe(0);
    expect($tiers[2]->refund_percentage)->toBe(0);
});

it('seeds pricing matrix for Recording Only tier across all package types', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    $recording = $facility->serviceTiers()->where('name', 'Recording Only')->first();

    expect(FacilityPricing::where(['service_tier_id' => $recording->id, 'package_type' => 'hourly'])->first()->price_aed_cents)->toBe(25400);
    expect(FacilityPricing::where(['service_tier_id' => $recording->id, 'package_type' => 'half_day'])->first()->price_aed_cents)->toBe(91440); // 4h × 254 × 0.9 (10% half-day discount)
});
```

- [ ] **Step 2: Run, expect failure (no seeder).**

- [ ] **Step 3: Implement the seeder**

`database/seeders/Pod24CatalogSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Catalog\Models\PricingModifier;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Seeder;

class Pod24CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::updateOrCreate(
            ['slug' => 'pod24-portable'],
            [
                'name' => ['en' => 'Pod24 Portable Studio'],
                'description' => ['en' => 'A broadcast-grade portable podcast pod, delivered to your location across Abu Dhabi.'],
                'address' => ['city' => 'Abu Dhabi', 'country' => 'AE'],
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $tiers = [
            ['name' => 'Recording Only',                              'rate' => 25400,  'sort' => 1],
            ['name' => 'Live Mix',                                    'rate' => 35400,  'sort' => 2],
            ['name' => 'Live Mix + Standard Edit',                    'rate' => 75400,  'sort' => 3],
            ['name' => 'Live Mix + Standard Edit + Live Stream',      'rate' => 105400, 'sort' => 4],
        ];

        $tierModels = [];
        foreach ($tiers as $t) {
            $tierModels[$t['name']] = ServiceTier::updateOrCreate(
                ['facility_id' => $facility->id, 'name' => $t['name']],
                ['base_hourly_rate_aed_cents' => $t['rate'], 'sort_order' => $t['sort'], 'is_active' => true]
            );
        }

        // Pricing matrix: hourly = base; half-day = 4h × base × 0.9; full-day = 8h × base × 0.85; multi-day = full-day × 0.9 per day
        foreach ($tierModels as $tierName => $tier) {
            $hourly = $tier->base_hourly_rate_aed_cents;

            $entries = [
                ['type' => 'hourly',    'hours' => 1, 'price' => $hourly],
                ['type' => 'half_day',  'hours' => 4, 'price' => (int) round($hourly * 4 * 0.9)],
                ['type' => 'full_day',  'hours' => 8, 'price' => (int) round($hourly * 8 * 0.85)],
                ['type' => 'multi_day', 'hours' => 1, 'price' => (int) round($hourly * 8 * 0.85 * 0.9)], // per-day, additional 10% off full-day
            ];

            foreach ($entries as $e) {
                FacilityPricing::updateOrCreate(
                    [
                        'facility_id' => $facility->id,
                        'service_tier_id' => $tier->id,
                        'package_type' => $e['type'],
                    ],
                    ['hours' => $e['hours'], 'price_aed_cents' => $e['price']]
                );
            }
        }

        PricingModifier::updateOrCreate(
            ['facility_id' => $facility->id, 'type' => 'weekend'],
            ['percentage' => 25]
        );
        PricingModifier::updateOrCreate(
            ['facility_id' => $facility->id, 'type' => 'after_hours'],
            ['percentage' => 25, 'after_hours_start' => '18:00', 'after_hours_end' => '09:00']
        );

        $cancellationTiers = [
            ['hours_before_min' => 168, 'refund_percentage' => 100],
            ['hours_before_min' => 72,  'refund_percentage' => 50],
            ['hours_before_min' => 0,   'refund_percentage' => 0],
        ];
        foreach ($cancellationTiers as $c) {
            CancellationPolicy::updateOrCreate(
                ['facility_id' => $facility->id, 'hours_before_min' => $c['hours_before_min']],
                ['refund_percentage' => $c['refund_percentage']]
            );
        }

        $addons = [
            ['name' => 'Episode editing',        'price' => 50000],
            ['name' => 'Social clips pack (5)',  'price' => 30000],
            ['name' => 'Cover art',              'price' => 25000],
            ['name' => 'Distribution to platforms', 'price' => 15000],
        ];
        foreach ($addons as $i => $a) {
            Addon::updateOrCreate(
                ['facility_id' => $facility->id, 'name->en' => $a['name']],
                ['name' => ['en' => $a['name']], 'price_aed_cents' => $a['price'], 'is_active' => true, 'sort_order' => $i]
            );
        }

        // Hour packs (Recording Only base rate × hours × volume discount)
        $packs = [
            ['hours' => 10, 'discount' => 0.10],
            ['hours' => 20, 'discount' => 0.20],
        ];
        $baseRate = $tierModels['Recording Only']->base_hourly_rate_aed_cents;
        foreach ($packs as $p) {
            $price = (int) round($baseRate * $p['hours'] * (1 - $p['discount']));
            HourPack::updateOrCreate(
                ['facility_id' => $facility->id, 'hours' => $p['hours']],
                [
                    'name' => ['en' => "{$p['hours']}-Hour Pack"],
                    'description' => ['en' => sprintf('%d hours pre-paid (%d%% off Recording Only rate). Valid for 12 months.', $p['hours'], (int) ($p['discount'] * 100))],
                    'price_aed_cents' => $price,
                    'expiry_days' => 365,
                    'is_active' => true,
                ]
            );
        }
    }
}
```

- [ ] **Step 4: Wire into DatabaseSeeder**

`database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        RolesAndPermissionsSeeder::class,
        Pod24CatalogSeeder::class,
    ]);
}
```

- [ ] **Step 5: Run tests + run the seeder**

```bash
./vendor/bin/pest tests/Feature/Pod24CatalogSeederTest.php
php artisan migrate:fresh --seed
```

Expected: tests green; `migrate:fresh --seed` completes without errors.

- [ ] **Step 6: Manual smoke check**

```bash
php artisan tinker --execute="echo App\Modules\Catalog\Models\Facility::count().' facilities, '.App\Modules\Catalog\Models\ServiceTier::count().' tiers, '.App\Modules\Catalog\Models\FacilityPricing::count().' pricing rows';"
```

Expected: `1 facilities, 4 tiers, 16 pricing rows`.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add Pod24CatalogSeeder with real pricing data"
```

---

## Task 24: Filament dashboard widget — Catalog snapshot

**Files:**
- Create: `app/Filament/Widgets/CatalogStatsWidget.php`
- Create: `tests/Feature/CatalogStatsWidgetTest.php`

- [ ] **Step 1: Generate widget**

```bash
php artisan make:filament-widget CatalogStatsWidget --stats-overview
```

- [ ] **Step 2: Implement**

`app/Filament/Widgets/CatalogStatsWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Catalog\Models\ServiceTier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active facilities', Facility::where('is_active', true)->count()),
            Stat::make('Service tiers', ServiceTier::count()),
            Stat::make('Hour packs (active)', HourPack::where('is_active', true)->count()),
        ];
    }
}
```

- [ ] **Step 3: Write smoke test**

`tests/Feature/CatalogStatsWidgetTest.php`:

```php
<?php

use App\Filament\Widgets\CatalogStatsWidget;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
});

it('renders the catalog stats widget', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    \Livewire\Livewire::test(CatalogStatsWidget::class)
        ->assertSeeText('Active facilities')
        ->assertSeeText('Service tiers');
});
```

```bash
./vendor/bin/pest tests/Feature/CatalogStatsWidgetTest.php
```

Expected: 1 passed.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Add CatalogStatsWidget on admin dashboard"
```

---

## Task 25: Add `.env.example` correctness + README quickstart

**Files:**
- Modify: `.env.example`
- Create: `README.md` (replace Laravel default)

- [ ] **Step 1: Update `.env.example`** to reflect the keys we use:

```
APP_NAME=Pod24
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://pod24.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pod24
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Filled in later plans:
# STRIPE_KEY=
# STRIPE_SECRET=
# STRIPE_WEBHOOK_SECRET=
# SENDGRID_API_KEY=
# MAILCHIMP_API_KEY=
# HUBSPOT_API_KEY=
```

- [ ] **Step 2: Replace `README.md`** with:

```markdown
# Pod24 Booking Platform

End-to-end booking platform for Pod24 — twofour54's portable podcast studio.

## Quickstart (local)

```bash
composer install
cp .env.example .env
php artisan key:generate
brew services start postgresql@16 redis
createdb pod24
php artisan migrate --seed
php artisan pod24:create-admin admin@pod24.local "Admin" changeme123
php artisan serve
```

Visit http://127.0.0.1:8000/admin and log in.

## Tests

```bash
./vendor/bin/pest
```

## Plans

See `docs/superpowers/plans/` for the implementation roadmap.
```

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Add README quickstart and update .env.example"
```

---

## Task 26: Final smoke test of Plan 1

**Files:** none modified.

- [ ] **Step 1: Run full test suite**

```bash
./vendor/bin/pest
```

Expected: all tests passing. Note the count.

- [ ] **Step 2: Fresh migrate + seed end-to-end**

```bash
php artisan migrate:fresh --seed
php artisan pod24:create-admin admin@pod24.local "Admin" changeme123
```

Expected: clean run, no errors.

- [ ] **Step 3: Manual admin walkthrough**

```bash
php artisan serve
```

Browse to http://127.0.0.1:8000/admin and verify:

- Login as `admin@pod24.local` / `changeme123` works
- Dashboard shows Catalog stats widget with `1 / 4 / 2`
- `Catalog → Facilities` shows Pod24 Portable Studio
- Open the facility; ServiceTiers relation shows 4 tiers
- "Edit pricing matrix" button opens the matrix editor; cells are populated
- `Catalog → Addons` shows 4 addons
- `Catalog → Hour packs` shows 2 packs (10h, 20h)
- `Catalog → Pricing modifiers` shows weekend + after-hours rows
- `Catalog → Cancellation policies` shows 3 tiers (168/72/0)

If anything is broken: open a follow-up task and fix; do not skip.

- [ ] **Step 4: Tag the milestone**

```bash
git tag plan-1-foundation-catalog-complete
```

- [ ] **Step 5: Final commit (if README/notes need updates from the smoke test)**

```bash
git add -A
git commit --allow-empty -m "Plan 1 complete: foundation + catalog"
```

---

## Plan 1 self-review summary

**Spec coverage check:**
- § 3 architecture (Laravel 11, PG, Redis, Filament): Tasks 1, 2, 5–8 ✅
- § 4 Catalog module (Facility, ServiceTier, FacilityPricing, Addon, HourPack, PricingModifier): Tasks 12–17 ✅
- § 5.1, 5.6 catalog & cancellation tables: Tasks 12–18 ✅
- § 14 staff auth + roles + 2FA: Roles seeder Task 10, gate Task 9. **2FA is NOT in this plan** — it's added in Plan 5 (Quotes/Cancel/File Delivery, when actually needed for refunds and PII access). Documented gap, acceptable.
- § 13 Mailchimp/Hubspot/SendGrid: Plan 6, intentionally deferred.
- § 6, 7, 8, 9, 10, 11, 12, 18 (pricing engine, availability, booking flow, hour packs, quotes, etc.): Plans 2–6.

**Placeholder scan:** none.

**Ambiguity check:** Pricing matrix half-day = 0.9× full hourly, full-day = 0.85× full hourly, multi-day = 0.9 × full-day per-day. These are seeded defaults — admin can override via the matrix editor at any time.

**Out-of-scope reminder:** This plan ends with a working admin panel and seeded catalog. No public website, no booking flow, no Stripe, no integrations yet. Those are Plans 2–6.
