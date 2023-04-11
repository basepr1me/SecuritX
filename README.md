# SecuritX

SecuritX is a simple and secure document uploader for easy sharing of protected
health information (PHI) PDFs. SecuritX is developed on OpenBSD using the
Laminas framework.

# Features

* On-disk encryption for data at rest
* Password free design
* Admins and editors can invite members for direct sharing outside of the company upload area
* Automatic member and file clean up via Cron
* 2FA verification after 24 hours of inactivity
* Ability to block domains and members by email address
* Simple installation

# What does this do anyway?

Once installed for a company, people who wish to send the company PHI can
request and verify an account. They can then upload PDFs to the company. If the
member who requested an account has the same domain as the company, they are
only given the option to request administration or editor rights.

Administrators and editors of the company can download those files. This option
can be shut off during installation of other means of file transfer are
implemented by the installer.

Administrators can send files to anyone who is a member. Editors can send files
to members that are part of their company only.

Administrators and editors can invite members. This allows administrators and
editors to receive files directly from the person they have invited. This is a
handy way, especially for hospices, to get paperwork signed and swapped with the
family caregiver, POA, etc.

# Installation

## Clone SecuritX

```bash
# cd /var/www/got/public
# got clone -a ssh://git@github.com/basepr1me/SecuritX.git
# cd /var/www
# got co /var/www/got/public/SecuritX.git securitx
```

## Edit global.php

You will need to sign up for reCaptcha keys and edit /var/www/securitx/config/autoload/global.php.

```bash
	'email_host' => [
		'hostname'	=> 'securitx.localhost',
		'ip'		=> '127.0.0.1',
	],
	'recaptcha' => [
		'site_key' => "enter recaptcha site key",
		'secret_key' => "enter recaptcha secret key",
	],
```

## Folders chmod

```bash
# chmod -R 777 /var/www/securitx/data
# mkdir -p /var/www/securitx/data/tmp
# chown -R www:www /var/www/securitx/data

# chmod 1700 /var/www/tmp
# chmod 1777 /var/www/securitx/data/tmp
```

## fstab

Adjust sizes to your needs.

```bash
swap /var/www/securitx/data/tmp mfs rw,nodev,nosuid,-s500M 0 0
swap /var/www/tmp mfs rw,nodev,nosuid,-s500M 0 0

# mount /var/www/securitx/data/tmp
# mount /var/www/tmp
```

## PHP

```bash
# pkg_add php php-intl php-pdo_sqlite
```

Adjust these settings in php-#.#.ini to your needs:

```bash
upload_max_filesize = 500M
post_max_size = 500M
max_file_uploads = 20
extension=intl.so
extension=pdo_sqlite.so

# You may also need
allow_url_fopen = On
```

### WWW shell

```bash
# cp -p /bin/sh /var/www/bin
# mkdir /var/www/etc
# cp -p /etc/resolv.conf /var/www/etc
# mkdir /var/www/etc/ssl
# cp -p /etc/ssl/cert.pem /var/www/etc/ssl
```

## httpd.conf

```bash
server "securitx.localhost" {
	listen on * port 80

	location "/.well-known/acme-challenge/*" {
		root "/acme"
		request strip 2
	}
	location "/*" {
		block return 302 "https://$SERVER_NAME$REQUEST_URI"
	}
}

server "securitx.localhost" {
	listen on * tls port 443

	# adjust for your setup
        connection { max request body 60000000000 }
	connection { request timeout 2400 }
	connection { timeout 2400 }
	hsts max-age 15552000

	tls certificate "/etc/ssl/securitx.localhost.fullchain.pem"
	tls key "/etc/ssl/private/securitx.localhost.key"

	root "/securitx/public"

	location "/img/*" {
		request no rewrite
	}
	location "/js/*" {
		request no rewrite
	}
	location "/css/*" {
		request no rewrite
	}

	location "/*.php" {
		fastcgi socket "/run/php-fpm.sock"
	}
	location "/*" {
		request rewrite "/index.php"
	}
}

```

## Save branch

```bash
# cd /var/www/securitx
# got ci -m "update production"
```

## Start daemons

```bash
# rcctl start php##_fpm https
```

## Setup SecuritX

Log in to your new SecuritX installation to setup the database, primary company,
and primary administrator.

## Edit cronjob

```bash
# crontab -e

* * * * * /usr/local/bin/php /var/www/securitx/scripts/process_cleanup.php
```

## Updating SecuritX

```bash
# cd /var/www/got/public/SecuritX.git
# git fetch origin main:main
# cd /var/www/securitx
# got up -b main
# got rb production
```

# Author

[Tracey Emery](https://github.com/basepr1me/)

If you like this software, consider [donating](https://k7tle.com/?donate=1).

See the [License](LICENSE.md) file for more information.
