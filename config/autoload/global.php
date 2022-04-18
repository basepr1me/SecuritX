<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return [
	/* edit this */
	'email_host' => [
		'hostname'	=> 'securitx.localhost',
		'ip'		=> '127.0.0.1',
	],

	/* do not edit beyond this point */
	'db' => [
		'driver'	=> 'Pdo_Sqlite',
		'dsn'		=> sprintf('sqlite:%s/data/securitx.db', realpath(getcwd())),
	],
	'recaptcha' => [
		'site_key' => "enter recaptcha site key",
		'secret_key' => "enter recaptcha secret key",
	],
	'hipaa' => [
		'notice' => 'DISCLOSURE: This message is intended only for the use of the individual(s) to whom it is addressed and contains information that is privileged, confidential, and exempt from disclosure under applicable law. Any further dissemination or copying of this communication is strictly prohibited. If you have received this communication in error, please notify us immediately by telephone or email as listed in our signature above. This message is provided in accordance with HIPAA Omnibus Rule of 2013.',
	],
];
