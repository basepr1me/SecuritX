<?php
namespace Securitx\Model;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;

class Emailer {
	public function sendVerifyEmail($email, $first, $last, $id, $url) {
		$message = new Message();
		$message->addFrom('no-reply@visnet.us', 'SecuritX');
		$message->addTo("$email", "$first $last");
		$message->addReplyTo('no-reply@visnet.us', 'SecuritX');
		$message->setSubject('SecuritX Verification Link');
		$message->setBody(
			"Hello $first,\r\n\r\nPlease visit the following " .
			"url to confirm your member request. The link will " .
			"expire in 24 hours. Thank you!\r\n\r\n$url"
		);

		$transport = new SmtpTransport();
		$options = new SmtpOptions([
			'name' => 'thelma',
			'host' => '192.168.0.254',
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
		$message->setSubject('SecuritX Uploader Link');
		$message->setBody(
			"Hello $first,\r\n\r\nThe following link is your " .
			"direct access link to upload protected health " .
			"documents to $company. Do not lose or share this " .
			"link with anyone.\r\n\r\nIf this link is not used ".
			"for thirty days, it will be removed. Thank you!" .
			"\r\n\r\n$url"
		);

		$transport = new SmtpTransport();
		$options = new SmtpOptions([
			'name' => 'thelma',
			'host' => '192.168.0.254',
		]);
		$transport->setOptions($options);
		$transport->send($message);
	}
}
