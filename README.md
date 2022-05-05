# SecuritX

## Introduction
SecuritX is a simple and secure document uploader for easy sharing of protected
health information. Developed on OpenBSD.

THIS SOFTWARE IS NOT READY TO BE CLONED. Please wait for release. The
functionality is not completed. When all functionality is completed, then
releases will occur.

## httpd

```bash
server "securitx.localhost" {
	listen on $ext_if port 80

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

cp -p /bin/sh /var/www/bin
```

make pretty later:

installation instructions

php.ini set:
upload_max_filesize = 500M
post_max_size = 500M
max_file_uploads = 20

chmod -R 777 /var/www/securitx/data
chown -R www:www /var/www/securitx/data

chmod 1777 /var/www/tmp
chmod 1777 /var/www/securitx/data/downloads/tmp

fstab:
swap /var/www/securitx/data/downloads/tmp mfs rw,nodev,nosuid,-s500M 0 0
swap /var/www/tmp mfs rw,nodev,nosuid,-s500M 0 0

upgrade instructions
edit emailhost in config/autoload/global.php
The src/Form/MemberForm.php document requires reCAPTCHA keys.
