#!/usr/bin/env bash
# ==============================================================================
# VortanCP - Enterprise Web Hosting Control Panel Automated Installer
# Target Systems: Ubuntu 22.04 LTS / Ubuntu 24.04 LTS / Debian 12
# ==============================================================================

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
cat << "EOF"
 __     __         _              ____ ____  
 \ \   / /__  _ __| |_ __ _ _ __ / ___|  _ \ 
  \ \ / / _ \| '__| __/ _` | '_ \| |   | |_) |
   \ V / (_) | |  | || (_| | | | | |___|  __/ 
    \_/ \___/|_|   \__\__,_|_| |_|\____|_|    
  Enterprise Modular Web Hosting Control Panel
EOF
echo -e "${NC}"

# 1. Root Check
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}[ERROR] This installer must be run as root.${NC}" 
   exit 1
fi

# 2. OS Compatibility Check
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VERSION=$VERSION_ID
else
    echo -e "${RED}[ERROR] Cannot detect operating system release.${NC}"
    exit 1
fi

echo -e "${GREEN}[INFO] Detected OS: ${OS} ${VERSION}${NC}"

if [[ "$OS" != "ubuntu" && "$OS" != "debian" ]]; then
    echo -e "${RED}[ERROR] Unsupported distribution. VortanCP requires Ubuntu or Debian.${NC}"
    exit 1
fi

# 3. System Update & Dependencies
echo -e "${YELLOW}[1/7] Updating package index and installing base dependencies...${NC}"
apt-get update -y
apt-get install -y \
    curl \
    wget \
    gnupg2 \
    ca-certificates \
    lsb-release \
    apt-transport-https \
    git \
    unzip \
    software-properties-common \
    ufw \
    fail2ban

# 4. Install PHP 8.3 & Web Stack
echo -e "${YELLOW}[2/7] Installing LEMP, BIND9, Mail, and FTP stack...${NC}"
if [[ "$OS" == "ubuntu" ]]; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi

apt-get install -y \
    nginx \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-mbstring \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-sqlite3 \
    mariadb-server \
    mariadb-client \
    bind9 \
    bind9utils \
    postfix \
    dovecot-imapd \
    dovecot-pop3d \
    pure-ftpd

# 5. Install Composer
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}[3/7] Installing Composer...${NC}"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# 6. Create Vortan System User & Directories
echo -e "${YELLOW}[4/7] Setting up VortanCP system user and directories...${NC}"
if ! id -u vortan >/dev/null 2>&1; then
    useradd -r -m -d /var/vortan -s /bin/bash vortan
fi

mkdir -p /var/vortan/panel
mkdir -p /var/vortan/users
mkdir -p /var/vortan/backups
mkdir -p /run/vortan
mkdir -p /var/log/vortan

chown -R vortan:vortan /var/vortan
chown -R root:vortan /run/vortan
chmod 0775 /run/vortan

# 7. Setup Database
echo -e "${YELLOW}[5/7] Configuring MariaDB database for VortanCP...${NC}"
DB_NAME="vortancp"
DB_USER="vortan"
DB_PASS=$(openssl rand -base64 18 | tr -dc 'a-zA-Z0-9' | head -c16)
AGENT_SECRET=$(openssl rand -hex 32)

mysql -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

# 8. Deploy Panel Files & Environment
echo -e "${YELLOW}[6/7] Deploying application environment and running migrations...${NC}"
cd /var/vortan/panel

if [ ! -f "artisan" ]; then
    echo -e "${BLUE}Copying source files to /var/vortan/panel...${NC}"
    # In production installation, files are cloned or copied from bundle
fi

cat > .env <<ENV_FILE
APP_NAME=VortanCP
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$(hostname -I | awk '{print $1}')

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

VORTAN_AGENT_SECRET=${AGENT_SECRET}
VORTAN_AGENT_SOCKET=/run/vortan/agent.sock
ENV_FILE

chown -R vortan:vortan /var/vortan/panel
chmod -R 775 /var/vortan/panel/storage /var/vortan/panel/bootstrap/cache

# Generate Application Key & Run Migrations
sudo -u vortan php artisan key:generate --force
sudo -u vortan php artisan migrate --force
sudo -u vortan php artisan db:seed --class=IdentityDatabaseSeeder --force

# 9. Install Systemd Services
echo -e "${YELLOW}[7/7] Installing Systemd services...${NC}"
cp /var/vortan/panel/scripts/systemd/vortan-agent.service /etc/systemd/system/
cp /var/vortan/panel/scripts/systemd/vortan-queue.service /etc/systemd/system/
cp /var/vortan/panel/scripts/logrotate/vortan /etc/logrotate.d/

systemctl daemon-reload
systemctl enable --now vortan-agent.service
systemctl enable --now vortan-queue.service

# 10. Summary Banner
SERVER_IP=$(hostname -I | awk '{print $1}')
echo -e "\n${GREEN}================================================================${NC}"
echo -e "${GREEN}  VortanCP Installation Completed Successfully!                ${NC}"
echo -e "${GREEN}================================================================${NC}"
echo -e " Panel URL:      ${BLUE}http://${SERVER_IP}:8000${NC} (or configured web domain)"
echo -e " Admin Login:    ${YELLOW}admin@vortan.local${NC}"
echo -e " Default Pass:   ${YELLOW}password${NC} (Change immediately upon login!)"
echo -e " Agent Socket:   ${BLUE}/run/vortan/agent.sock${NC}"
echo -e " Database User:  ${YELLOW}${DB_USER}${NC}"
echo -e " Database Name:  ${YELLOW}${DB_NAME}${NC}"
echo -e "${GREEN}================================================================${NC}\n"
