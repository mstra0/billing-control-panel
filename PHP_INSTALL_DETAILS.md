## User

how do i install php 5.6 or any other versions, multipe versions infact, on my ubuntu system?

## Assistant

You can install multiple PHP versions on Ubuntu using the **ondrej/php PPA**, which provides PHP 5.6 through the latest versions.

## Step 1: Add the PPA repository

```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
```

## Step 2: Install PHP versions

Install any versions you need:

```bash
# PHP 5.6
sudo apt install php5.6

# PHP 7.4
sudo apt install php7.4

# PHP 8.1
sudo apt install php8.1

# PHP 8.2
sudo apt install php8.2

# PHP 8.3
sudo apt install php8.3
```

## Step 3: Install common extensions (per version)

```bash
sudo apt install php5.6-{cli,fpm,mysql,curl,xml,mbstring,zip,gd}
sudo apt install php8.2-{cli,fpm,mysql,curl,xml,mbstring,zip,gd}
```

## Step 4: Switch between versions

**For CLI:**
```bash
sudo update-alternatives --set php /usr/bin/php8.2
# Or interactively:
sudo update-alternatives --config php
```

**For Apache:**
```bash
sudo a2dismod php5.6
sudo a2enmod php8.2
sudo systemctl restart apache2
```

**For Nginx (using PHP-FPM):**
Edit your site config to point to the desired FPM socket:
```
fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
```

## Check installed version

```bash
php -v
```

## List all installed PHP versions

```bash
ls /usr/bin/php*
```

This setup lets you run different PHP versions for different projects or switch globally as needed.








# activate 5.6
sudo a2dismod php8.3
sudo a2enmod php5.6
sudo systemctl restart apache2

# activate 8.3
sudo a2dismod php5.6
sudo a2enmod php8.3
sudo systemctl restart apache2
