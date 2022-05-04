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
upgrade instructions
edit emailhost in config/autoload/global.php
The src/Form/MemberForm.php document requires reCAPTCHA keys.
