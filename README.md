
# Tea Inside Bot S8
Telegram bot.

# Installation (tested on Docker Ubuntu 20.04)
Run these commands as root
```sh

apt-get update -y;

## Basic tools for managing daemon and config.
apt-get install -y --no-install-recommends \
git gcc g++ cmake make autoconf \
build-essential vim net-tools ssh \
openssl curl wget ca-certificates screen;

# Requirement for building PHP.
apt-get install -y --no-install-recommends \
re2c \
bison \
libkrb5-dev \
libxml2-dev \
libsqlite3-dev \
libcurl4-gnutls-dev \
libenchant-2-dev \
libwebp-dev \
libjpeg-dev \
libxpm-dev \
libgmp3-dev \
libonig-dev \
postgresql-server-dev-all \
libpspell-dev \
libedit-dev \
libsodium-dev \
libargon2-dev \
libtidy-dev \
libxslt1-dev \
libexpat1-dev \
libzip-dev;

# GitHub CA Cert.
mkdir /usr/local/share/ca-certificates/cacert.org;
wget -P /usr/local/share/ca-certificates/cacert.org \
http://www.cacert.org/certs/root.crt http://www.cacert.org/certs/class3.crt;
update-ca-certificates;
git config --global http.sslCAinfo /etc/ssl/certs/ca-certificates.crt;

# Clone and build PHP.
git clone https://github.com/ammarfaizi2/php8.0.2.git;
cd php8.0.2;
./buildconf --force;
./configure \
--config-cache \
--prefix=/usr/local \
--enable-fpm \
--enable-zts \
--enable-debug \
--with-valgrind \
--enable-sigchild \
--enable-phpdbg \
--enable-phpdbg-debug \
--enable-phpdbg-readline \
--enable-phpdbg-webhelper \
--enable-embed=shared \
--with-openssl=shared \
--with-kerberos=shared \
--with-system-ciphers=shared \
--with-external-pcre=shared \
--with-pcre-jit=shared \
--with-zlib=shared \
--enable-bcmath=shared \
--enable-calendar=shared \
--with-curl=shared \
--enable-dba=shared \
--with-enchant=shared \
--enable-exif=shared \
--with-ffi=shared \
--enable-ftp=shared \
--enable-gd=shared \
--with-webp=shared \
--with-jpeg=shared \
--with-xpm=shared \
--with-freetype=shared \
--enable-gd-jis-conv \
--with-gettext=shared \
--with-gmp=shared \
--with-mhash=shared \
--enable-mbstring=shared \
--with-mysqli=shared \
--enable-pcntl \
--with-pdo-mysql \
--with-pdo-pgsql \
--without-pdo-sqlite \
--with-pspell \
--with-libedit \
--with-readline \
--enable-shmop \
--enable-soap \
--enable-sockets \
--with-sodium \
--with-password-argon2 \
--enable-sysvmsg \
--enable-sysvsem \
--enable-sysvshm \
--with-tidy \
--with-expat \
--with-xsl \
--with-zip \
--enable-mysqlnd \
--with-gnu-ld;
make -j$(nproc);
make install -j$(nproc);
cd ..;

# Clone and build TeaBot.
git clone https://github.com/TeaInside/tea-inside-bot-s8 teabot;
cd teabot;
php build.php;
```

# License
This project is licensed under GPL-v3 license.
