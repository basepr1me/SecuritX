<?php
namespace Securitx\Model;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;

class Emailer {
	private $hostname, $ip;

	public function __construct($email_host) {
		$this->hostname = $email_host['hostname'];
		$this->ip = $email_host['ip'];
	}

	public function sendVerifyEmail($email, $first, $last, $id, $url) {
		$message = new Message();
		$message->addFrom('no-reply@visnet.us', 'SecuritX');
		$message->addTo("$email", "$first $last");
		$message->addReplyTo('no-reply@visnet.us', 'SecuritX');
		$message->setSubject('Your SecuritX Verification Link');
		$message->setBody(
			"Hello $first,\r\n\r\nPlease visit the following " .
			"url to confirm your member request. The link will " .
			"expire in 24 hours. Thank you!\r\n\r\n$url"
		);

		$transport = new SmtpTransport();
		$options = new SmtpOptions([
			'name' => $this->hostname,
			'host' => $this->ip,
		]);
		$transport->setOptions($options);
		$transport->send($message);
	}
	public function sendMemberEmail($email, $first, $last, $id, $url,
	    $company) {
		$message = new Message();
		$message->addFrom('no-reply@visnet.us', 'SecuritX');
		$message->addTo("$email", "$first $last");
		$message->addReplyTo('no-reply@visnet.us', 'SecuritX');
		$message->setSubject('Your Private SecuritX Link');
		$message->setBody(
			"Hello $first,\r\n\r\nThe following link is your " .
			"direct access link to send and receive protected health " .
			"documents for $company. Do not lose or share this " .
			"link with anyone.\r\n\r\nIf this link is not used ".
			"for thirty days, it will be removed. Thank you!" .
			"\r\n\r\n$url"
		);

		$transport = new SmtpTransport();
		$options = new SmtpOptions([
			'name' => $this->hostname,
			'host' => $this->ip,
		]);
		$transport->setOptions($options);
		$transport->send($message);
	}
}
