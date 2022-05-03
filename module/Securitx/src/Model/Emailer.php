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

	public function sendVerifyEmail($email, $first, $last, $url, $hipaa) {
		$message = new Message();
		$message->addFrom('no-reply@' . $this->hostname, 'SecuritX');
		$message->addTo("$email", "$first $last");
		$message->addReplyTo('no-reply@' . $this->hostname, 'SecuritX');
		$message->setSubject('Your SecuritX Verification Link');
		$message->setBody(
			"Hello $first,\r\n\r\nPlease visit the following " .
			"url to confirm your member request. The link will " .
			"expire in 24 hours. Thank you!\r\n\r\n$url\r\n\r\n" .
			"--\r\n\r\n$hipaa"
		);

		$transport = new SmtpTransport();
		$options = new SmtpOptions([
			'name' => $this->hostname,
			'host' => $this->ip,
		]);
		$transport->setOptions($options);
		$transport->send($message);
	}
	public function sendInviteEmail($email, $first, $last, $url, $hipaa,
	    $company, $inviter) {
		$message = new Message();
		$message->addFrom('no-reply@' . $this->hostname, 'SecuritX');
		$message->addTo("$email", "$first $last");
		$message->addReplyTo('no-reply@' . $this->hostname, 'SecuritX');
		$message->setSubject('Your SecuritX Verification Link');
		$message->setBody(
			"Hello $first,\r\n\r\nYou have been invited to become " .
			"a member of the SecuritX instance of $company by " .
			"$inviter. Please visit the following url to verify " .
			"your email. The link will expire in 24 hours. ".
			"Thank you!\r\n\r\n$url\r\n\r\n--\r\n\r\n$hipaa"
		);

		$transport = new SmtpTransport();
		$options = new SmtpOptions([
			'name' => $this->hostname,
			'host' => $this->ip,
		]);
		$transport->setOptions($options);
		$transport->send($message);
	}
	public function sendMemberEmail($email, $first, $last, $url, $company,
	    $hipaa) {
		$message = new Message();
		$message->addFrom('no-reply@' . $this->hostname, 'SecuritX');
		$message->addTo("$email", "$first $last");
		$message->addReplyTo('no-reply@' . $this->hostname, 'SecuritX');
		$message->setSubject('Your Private SecuritX Link');
		$message->setBody(
			"Hello $first,\r\n\r\nThe following link is your " .
			"direct access link to send and receive protected health " .
			"documents for $company. Do not lose or share this " .
			"link with anyone.\r\n\r\nIf this link is not used ".
			"for thirty days, it will be removed. Thank you!" .
			"\r\n\r\n$url\r\n\r\n--\r\n\r\n$hipaa"
		);

		$transport = new SmtpTransport();
		$options = new SmtpOptions([
			'name' => $this->hostname,
			'host' => $this->ip,
		]);
		$transport->setOptions($options);
		$transport->send($message);
	}
	public function sendInviterEmail($email, $first, $last, $ifirst,
	    $ilast, $hipaa) {
		$message = new Message();
		$message->addFrom('no-reply@' . $this->hostname, 'SecuritX');
		$message->addTo("$email", "$first $last");
		$message->addReplyTo('no-reply@' . $this->hostname, 'SecuritX');
		$message->setSubject('Your SecuritX Invitation');
		$message->setBody(
			"Hello $first,\r\n\r\nYour invitation to $ifirst $ilast " .
			"has been verifies.\r\nYou can send them files now." .
			"\r\n\r\n--\r\n\r\n$hipaa"
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
