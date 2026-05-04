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
