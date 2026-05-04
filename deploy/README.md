# Pod24 deployment — Lightsail

Self-hosted Lightsail deploy without Forge. Free aside from Lightsail itself
(starting ~$24/mo for 2 vCPU / 4 GB).

## What you'll have at the end

- Ubuntu 22.04 + PHP 8.3 + Postgres 16 + Redis + Nginx
- Free Let's Encrypt SSL via certbot
- Queue worker + scheduler running as systemd units (auto-restart on crash, auto-start on boot)
- Git pull + `deploy.sh` workflow for updates

## Step-by-step

### 1. Create the Lightsail instance

1. Log into the AWS Lightsail console (`lightsail.aws.amazon.com`)
2. **Create instance**
   - Region: `me-central-1` (UAE) or `eu-central-1` (Frankfurt) for the lowest UAE latency
   - Platform: **Linux/Unix**
   - Blueprint: **OS Only → Ubuntu 22.04 LTS**
   - Plan: **$24/mo (2 GB RAM, 2 vCPU)** minimum. Bump to $40 if you expect heavy traffic.
   - Identifier: `pod24-prod` (or whatever)
3. **Networking** tab → IPv4 firewall: ports `22`, `80`, `443` should already be open.
4. Optionally **attach a static IP** so the IP doesn't change on reboot. Free for an attached instance.

### 2. SSH in & run the bootstrap

The Lightsail console gives you a browser SSH shell, or download the `.pem` key and use:

```bash
ssh -i ~/Downloads/LightsailDefaultKey-me-central-1.pem ubuntu@<your-ip>
```

Then run:

```bash
curl -fsSL https://raw.githubusercontent.com/kamelasmar/pod24/main/deploy/bootstrap.sh \
  | sudo bash -s https://github.com/kamelasmar/pod24.git
```

This:
- Installs PHP 8.3, Postgres, Redis, Nginx, Composer, Node 20, certbot
- Creates a `pod24` Linux user
- Clones the repo to `/var/www/pod24`
- Runs `composer install`, `npm ci && npm run build`
- Creates a default `.env` from `.env.example`
- Wires Nginx + queue worker + scheduler systemd units
- Opens UFW firewall

### 3. Configure secrets in `.env`

The bootstrap script prints the path. Edit it:

```bash
sudo -u pod24 -- nano /var/www/pod24/.env
```

Required values:

```ini
APP_NAME=Pod24
APP_ENV=production
APP_KEY=                           # bootstrap auto-generates this
APP_DEBUG=false
APP_TIMEZONE=Asia/Dubai
APP_URL=https://pod24.kamelasmar.com   # your real domain

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pod24
DB_USERNAME=pod24
DB_PASSWORD=<pick a strong password>

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Stripe (live keys when you go live)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...    # from dashboard webhook pointing at https://your-domain/webhooks/stripe

# SendGrid
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@pod24.kamelasmar.com
MAIL_FROM_NAME=Pod24

# Demo mode — drop or set false in production
POD24_FORCE_STUDIO_OPEN=false
```

After editing, sync the Postgres password to match:

```bash
sudo -u postgres psql -c "ALTER USER pod24 WITH PASSWORD '<your-pw>';"
```

### 4. Point DNS at the server

Get the Lightsail public IP from the console. Add an `A` record at your DNS provider:

| Type | Name | Value |
|---|---|---|
| A | `pod24` (or `@`) | `<Lightsail public IP>` |

Wait for DNS to propagate (`dig pod24.kamelasmar.com` should return the IP).

### 5. SSL via Let's Encrypt

```bash
sudo certbot --nginx -d pod24.kamelasmar.com
```

Certbot auto-edits the Nginx config to serve HTTPS, sets up auto-renewal cron, and redirects HTTP to HTTPS.

### 6. First deploy

```bash
sudo bash /var/www/pod24/deploy/deploy.sh
```

This runs migrations + seeds + builds production caches + restarts services.

### 7. Create your first admin

```bash
cd /var/www/pod24
sudo -u pod24 -- php artisan pod24:create-admin admin@pod24.kamelasmar.com "Kamel Asmar"
# (prompts for password securely)
```

Visit `https://pod24.kamelasmar.com/admin` and log in.

### 8. Wire up Stripe webhook in production

In the Stripe dashboard (test mode for now), add a webhook endpoint:

- URL: `https://pod24.kamelasmar.com/webhooks/stripe`
- Events: `payment_intent.succeeded`, `checkout.session.completed`
- Copy the new `whsec_...` into `.env` (replacing the local-CLI one)
- `sudo -u pod24 -- php artisan config:clear`

## Updating after code changes

On your laptop:

```bash
git push origin main
```

On the Lightsail box:

```bash
sudo bash /var/www/pod24/deploy/deploy.sh
```

That's the full update cycle. The script pulls, installs, migrates, rebuilds assets, restarts queue + nginx + php-fpm.

## Useful operations

| Task | Command |
|---|---|
| Tail app logs | `sudo tail -f /var/www/pod24/storage/logs/laravel.log` |
| Tail queue worker | `sudo tail -f /var/log/pod24-queue.log` |
| Tail scheduler | `sudo tail -f /var/log/pod24-schedule.log` |
| Restart queue | `sudo systemctl restart pod24-queue` |
| Restart everything | `sudo systemctl restart php8.3-fpm pod24-queue && sudo systemctl reload nginx` |
| Check queue health | `sudo systemctl status pod24-queue` |
| Filament admin | `https://pod24.kamelasmar.com/admin` |

## Switching to live Stripe keys

When you're ready to take real money:

1. In Stripe dashboard, top-right, toggle **Live mode**
2. Copy live `pk_live_...` and `sk_live_...` from Developers → API keys
3. Add a live webhook endpoint pointing at the same `/webhooks/stripe` path
4. Edit `/var/www/pod24/.env` — replace the `pk_test_` / `sk_test_` / `whsec_` (test) with live ones
5. `sudo -u pod24 -- php artisan config:clear`

No code changes needed — the same handler works for both modes.
