#!/usr/bin/env bash
# Pod24 Lightsail provisioning — one-time setup on a fresh Ubuntu 24.04 LTS instance.
#
# Why 24.04 not 22.04? PHP 8.3 ships natively in 24.04's apt — no Sury, no PPAs,
# no IPv6 routing problems. If you're stuck on 22.04, switch the Lightsail
# blueprint and run this on a fresh 24.04 instance.
#
# Usage (from a fresh Lightsail Ubuntu 24.04 instance):
#   curl -fsSL https://raw.githubusercontent.com/<user>/<repo>/main/deploy/bootstrap.sh \
#     | sudo bash -s <git-url>
#
# Or, after cloning manually:
#   sudo bash deploy/bootstrap.sh <git-url>
#
# Idempotent. Re-running is safe.

set -euo pipefail

GIT_URL="${1:-}"
APP_DIR="/var/www/pod24"
APP_USER="pod24"
DB_NAME="pod24"
DB_USER="pod24"

if [ "$EUID" -ne 0 ]; then
    echo "Run with sudo." >&2
    exit 1
fi

# Bail out early if we're on a release that doesn't ship PHP 8.3 natively.
. /etc/os-release 2>/dev/null || true
if [ "${VERSION_ID:-}" != "24.04" ]; then
    echo
    echo "  ⚠  This script targets Ubuntu 24.04 LTS (noble)."
    echo "     You're on: ${PRETTY_NAME:-unknown}"
    echo
    echo "     Re-create the Lightsail instance with the Ubuntu 24.04 LTS blueprint"
    echo "     and re-run. PHP 8.3 ships natively in 24.04; older Ubuntus need third-"
    echo "     party PPAs that are unreliable on Lightsail's network."
    echo
    exit 1
fi

echo "==> Updating apt cache..."
apt-get update -y

echo "==> Installing system packages + PHP 8.3 (native to Ubuntu 24.04)..."
DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg unzip git \
    nginx postgresql postgresql-contrib redis-server \
    certbot python3-certbot-nginx ufw fail2ban \
    php8.3 php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
    php8.3-readline

echo "==> Installing Composer..."
if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "==> Installing Node.js 20..."
if ! command -v node >/dev/null 2>&1; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi

echo "==> Creating app user '${APP_USER}'..."
if ! id "${APP_USER}" &>/dev/null; then
    useradd -m -d "/home/${APP_USER}" -s /bin/bash "${APP_USER}"
    usermod -aG www-data "${APP_USER}"
fi

echo "==> Creating Postgres database + user..."
sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" | grep -q 1 \
    || sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD 'changeme';"
sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" | grep -q 1 \
    || sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};"

echo "==> Cloning / updating repo..."
mkdir -p "${APP_DIR}"
chown "${APP_USER}:www-data" "${APP_DIR}"
if [ -d "${APP_DIR}/.git" ]; then
    sudo -u "${APP_USER}" -- git -C "${APP_DIR}" pull --ff-only
else
    if [ -z "${GIT_URL}" ]; then
        echo "First-time install needs the git URL as argument." >&2
        exit 1
    fi
    sudo -u "${APP_USER}" -- git clone "${GIT_URL}" "${APP_DIR}"
fi

echo "==> Installing PHP + JS dependencies (this can take a couple minutes)..."
cd "${APP_DIR}"
sudo -u "${APP_USER}" -- composer install --no-dev --optimize-autoloader --no-interaction
sudo -u "${APP_USER}" -- npm ci
sudo -u "${APP_USER}" -- npm run build

echo "==> Setting up .env..."
if [ ! -f "${APP_DIR}/.env" ]; then
    sudo -u "${APP_USER}" -- cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
    sudo -u "${APP_USER}" -- php artisan key:generate
    echo
    echo "  ⚠  /var/www/pod24/.env was created from .env.example — edit it now:"
    echo "     sudo -u ${APP_USER} -- nano ${APP_DIR}/.env"
    echo
    echo "  Required values: APP_URL, DB_PASSWORD (matches Postgres), STRIPE_*, MAIL_PASSWORD"
    echo
fi

echo "==> Installing Nginx site..."
cp "${APP_DIR}/deploy/nginx-pod24.conf" /etc/nginx/sites-available/pod24
ln -sf /etc/nginx/sites-available/pod24 /etc/nginx/sites-enabled/pod24
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "==> Installing systemd units..."
cp "${APP_DIR}/deploy/pod24-queue.service" /etc/systemd/system/pod24-queue.service
cp "${APP_DIR}/deploy/pod24-schedule.service" /etc/systemd/system/pod24-schedule.service
cp "${APP_DIR}/deploy/pod24-schedule.timer" /etc/systemd/system/pod24-schedule.timer
systemctl daemon-reload
systemctl enable --now pod24-queue.service
systemctl enable --now pod24-schedule.timer

echo "==> Setting permissions..."
chown -R "${APP_USER}:www-data" "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo "==> Configuring firewall..."
ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

echo
echo "================================================================"
echo "  Pod24 bootstrap complete."
echo
echo "  Next steps:"
echo "    1. Edit /var/www/pod24/.env — set APP_URL, DB_PASSWORD, STRIPE_*, MAIL_*"
echo "    2. Update Postgres password if you changed DB_PASSWORD:"
echo "         sudo -u postgres psql -c \"ALTER USER pod24 WITH PASSWORD '<your-pw>';\""
echo "    3. Point DNS A record at this server's IP:"
echo "         $(curl -fsSL https://api.ipify.org 2>/dev/null || echo '<server ip>')"
echo "    4. Once DNS resolves, get a free SSL cert:"
echo "         sudo certbot --nginx -d <your-domain>"
echo "    5. Run the deploy script:"
echo "         sudo bash /var/www/pod24/deploy/deploy.sh"
echo "================================================================"
