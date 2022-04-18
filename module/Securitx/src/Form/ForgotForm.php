<?php
namespace Securitx\Form;

use Laminas\Captcha;
use Laminas\Form\Form;
use Laminas\Form\Element;

class ForgotForm extends Form {
	private $site_key, $secret_key;
	public function __construct($recaptcha) {
		parent::__construct();
		$this->site_key = $recaptcha['site_key'];
		$this->secret_key = $recaptcha['secret_key'];

		$this->add([
			'name' => 'email',
			'type' => Element\Email::class,
			'options' => [
				'label' => 'Email Address',
			],
			'attributes' => [
				'multiple' => false,
			],
		]);
		$this->add([
			'name' => 'captcha',
			'type' => 'Captcha',
			'options' => [
				'captcha' => new Captcha\ReCaptcha([
					'site_key' => $this->site_key,
					'secret_key' => $this->secret_key,
				]),
			],
		]);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Send Request',
				'id' => 'submitbutton',
			],
		]);
	}
}
