<?php
namespace Securitx\Form;

use Laminas\Captcha;
use Laminas\Form\Form;
use Laminas\Form\Element;

class MemberForm extends Form {
	public function __construct($name = null) {
		parent::__construct($name);

		$this->add([
			'name' => 'captcha',
			'type' => 'Captcha',
			'options' => [
				'captcha' => new Captcha\ReCaptcha([
					'site_key' => '********',
					'secret_key' => '********',
				]),
			],
		]);
		$this->add([
			'name' => 'id',
			'type' => 'hidden',
		]);
		$this->add([
			'name' => 'first',
			'type' => 'text',
			'options' => [
				'label' => 'First Name',
			],
		]);
		$this->add([
			'name' => 'last',
			'type' => 'text',
			'options' => [
				'label' => 'Last Name',
			],
		]);
		$this->add([
			'name' => 'office',
			'type' => 'text',
			'options' => [
				'label' => 'Office Name',
			],
		]);
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
			'name' => 'company_id',
			'type' => 'select',
			'options' => [
				'label' => 'Select Company',
				'disable_inarray_validator' => true,
			],
		]);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Request',
				'id' => 'submitbutton',
			],
		]);
	}
}