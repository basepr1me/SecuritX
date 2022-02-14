# SecuritX

## Introduction
SecuritX is a simple and secure document uploader for easy sharing of protected
health information. Developed on OpenBSD.

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
```

The src/Form/MemberForm.php document requires reCaptcha keys.
