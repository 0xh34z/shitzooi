#!/bin/bash

# --- CONFIGURATIE (Pas dit aan indien nodig) ---
DOMAIN="h34z.tech"
EMAIL="kaspervandekimmenade@proton.me" # Gebruik een echt emailadres voor Let's Encrypt
MYSQL_ROOT_PASSWORD="2dCnbSf4j5"
WEB_DB_NAME="webapp_prod"
WEB_DB_USER="h34z"
WEB_DB_PASSWORD="2dCnbSf4j5"
WEB_ROOT="/var/www/html"
PHPMYADMIN_ALIAS="/lAMZVgQ3vAll7lIs55SdeN9" # Beveiliging door obscure url

# Kleurtjes voor output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== STARTING NGINX + PHP + MariaDB INSTALLATION ===${NC}"

# 1. Systeem Update
echo -e "${GREEN}--> Updating system...${NC}"
apt update && apt upgrade -y

# 2. Installeer MariaDB (vervangt MySQL)
echo -e "${GREEN}--> Installing MariaDB Server...${NC}"
apt install mariadb-server -y

# MariaDB/MySQL Security Configureren via commando's
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.user WHERE User='';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DROP DATABASE IF EXISTS test;"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

# Maak Database en User
echo -e "${GREEN}--> Creating Database...${NC}"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE ${WEB_DB_NAME};"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE USER '${WEB_DB_USER}'@'localhost' IDENTIFIED BY '${WEB_DB_PASSWORD}';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON ${WEB_DB_NAME}.* TO '${WEB_DB_USER}'@'localhost';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

# 3. Installeer Nginx, PHP en phpMyAdmin
echo -e "${GREEN}--> Installing Nginx & PHP...${NC}"
apt install nginx php-fpm php-mysql php-mbstring php-zip php-gd php-json php-curl php-xml -y

# Detecteer PHP Socket pad (dynamisch)
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
PHP_SOCKET="unix:/run/php/php${PHP_VERSION}-fpm.sock"
echo "Detected PHP Version: $PHP_VERSION (Socket: $PHP_SOCKET)"

# Installeer PMA zonder prompt
echo "phpmyadmin phpmyadmin/dbconfig-install boolean true" | debconf-set-selections
echo "phpmyadmin phpmyadmin/app-password-confirm password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
echo "phpmyadmin phpmyadmin/mysql/admin-pass password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
echo "phpmyadmin phpmyadmin/mysql/app-pass password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect" | debconf-set-selections
DEBIAN_FRONTEND=noninteractive apt install phpmyadmin -y

# Link PMA
ln -sf /usr/share/phpmyadmin /var/www/html/phpmyadmin

# 4. Maak web root aan en zet test pagina
echo -e "${GREEN}--> Preparing web root...${NC}"
mkdir -p ${WEB_ROOT}
chown -R www-data:www-data ${WEB_ROOT}
chmod -R 755 ${WEB_ROOT}

# Maak een simpele test PHP pagina
cat > ${WEB_ROOT}/index.php <<PHPEOF
<!DOCTYPE html>
<html>
<head>
    <title>PHP Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .info { background: #f0f0f0; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>PHP is werkend!</h1>
    <div class="info">
        <h2>Server Informatie</h2>
        <p><strong>PHP Versie:</strong> <?php echo phpversion(); ?></p>
        <p><strong>Server:</strong> <?php echo \$_SERVER['SERVER_SOFTWARE']; ?></p>
        <p><strong>Tijd:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
    
    <?php
    // Test database connectie
    \$host = 'localhost';
    \$db = 'information_schema';
    \$user = '${WEB_DB_USER}';
    \$pass = '${WEB_DB_PASSWORD}';
    
    try {
        \$pdo = new PDO("mysql:host=\$host;dbname=\$db", \$user, \$pass);
        echo '<div class="info" style="background: #d4edda; margin-top: 20px;">';
        echo '<h2>✓ Database Connectie Succesvol</h2>';
        echo '<p>Verbonden met database: ' . htmlspecialchars(\$db) . '</p>';
        echo '</div>';
    } catch(PDOException \$e) {
        echo '<div class="info" style="background: #f8d7da; margin-top: 20px;">';
        echo '<h2>✗ Database Connectie Mislukt</h2>';
        echo '<p>' . htmlspecialchars(\$e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
</body>
</html>
PHPEOF

chown www-data:www-data ${WEB_ROOT}/index.php

# 5. Configureer Nginx - FASE 1: HTTP ONLY (Voor Certbot verificatie)
echo -e "${GREEN}--> Configuring Nginx (Stage 1: HTTP Only)...${NC}"
cat > /etc/nginx/sites-available/default <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${WEB_ROOT};
    index index.php index.html index.htm;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass ${PHP_SOCKET};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Activeer site en herstart Nginx
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/
nginx -t && systemctl restart nginx

# 6. CHECKPOINT: Ask about SSL installation
echo -e "${GREEN}========================================================${NC}"
echo -e "${GREEN}Basisinstallatie voltooid!${NC}"
echo -e "${GREEN}========================================================${NC}"
read -p "Wil je een SSL certificaat (HTTPS) installeren? (Y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    ENABLE_SSL=true
else
    ENABLE_SSL=false
fi

# 7. SSL Installation (optional)
if [ "$ENABLE_SSL" = true ]; then
    echo -e "${RED}========================================================${NC}"
    echo -e "${RED}LET OP: We gaan nu Certbot (SSL) draaien.${NC}"
    echo -e "Jouw server interne IP is: $(hostname -I)"
    echo -e "Zorg dat in je router poort 80 en 443 naar DIT IP wijzen (Port Forwarding)."
    echo -e "${RED}========================================================${NC}"
    read -p "Is je Port Forwarding ingesteld? Druk op Enter om door te gaan (of Ctrl+C om te stoppen)..."
    
    echo -e "${GREEN}--> Requesting SSL Certificate...${NC}"
    apt install certbot python3-certbot-nginx -y
    certbot certonly --nginx -d "${DOMAIN}" --email "${EMAIL}" --agree-tos --no-eff-email --non-interactive

    # Check of certificaat bestaat
    if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
        echo -e "${RED}ERROR: SSL Certificaat aanvraag mislukt! Check je port forwarding en DNS.${NC}"
        echo -e "${RED}Het script wordt voortgezet zonder SSL.${NC}"
        ENABLE_SSL=false
    else
        echo -e "${GREEN}SSL Certificaat succesvol aangevraagd!${NC}"
    fi
fi

# 8. Configureer Nginx - Final configuration
echo -e "${GREEN}--> Configuring Nginx (Final)...${NC}"
if [ "$ENABLE_SSL" = true ]; then
    cat > /etc/nginx/sites-available/default <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name ${DOMAIN};

    # SSL Config
    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    # Max upload size
    client_max_body_size 50M;

    # Web root
    root ${WEB_ROOT};
    index index.php index.html index.htm;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass ${PHP_SOCKET};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # phpMyAdmin (Op geheim pad)
    location ${PHPMYADMIN_ALIAS} {
        alias /usr/share/phpmyadmin;
        index index.php index.html index.htm;

        location ~ ^${PHPMYADMIN_ALIAS}/(.+\.php)\$ {
            alias /usr/share/phpmyadmin/\$1;
            fastcgi_pass ${PHP_SOCKET};
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME \$request_filename;
            include fastcgi_params;
        }

        location ~* ^${PHPMYADMIN_ALIAS}/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))\$ {
            alias /usr/share/phpmyadmin/\$1;
        }
    }
}
EOF
else
    cat > /etc/nginx/sites-available/default <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${WEB_ROOT};
    index index.php index.html index.htm;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass ${PHP_SOCKET};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # phpMyAdmin (Op geheim pad)
    location ${PHPMYADMIN_ALIAS} {
        alias /usr/share/phpmyadmin;
        index index.php index.html index.htm;

        location ~ ^${PHPMYADMIN_ALIAS}/(.+\.php)\$ {
            alias /usr/share/phpmyadmin/\$1;
            fastcgi_pass ${PHP_SOCKET};
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME \$request_filename;
            include fastcgi_params;
        }

        location ~* ^${PHPMYADMIN_ALIAS}/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))\$ {
            alias /usr/share/phpmyadmin/\$1;
        }
    }
}
EOF
fi

# Reload Nginx
nginx -t && systemctl reload nginx

echo -e "${GREEN}=============================================${NC}"
echo -e "${GREEN}INSTALLATION COMPLETE!${NC}"
if [ "$ENABLE_SSL" = true ]; then
    echo -e "Website:      https://${DOMAIN}"
else
    echo -e "Website:      http://${DOMAIN}"
fi
echo -e "phpMyAdmin:   http${ENABLE_SSL:+s}://${DOMAIN}${PHPMYADMIN_ALIAS}"
echo -e "Web Root:     ${WEB_ROOT}"
echo -e "DB Name:      ${WEB_DB_NAME}"
echo -e "DB User:      ${WEB_DB_USER}"
echo -e "DB Pass:      ${WEB_DB_PASSWORD}"
echo -e "${GREEN}=============================================${NC}"
