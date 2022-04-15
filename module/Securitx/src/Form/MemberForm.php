<?php
namespace Securitx\Form;

use Laminas\Captcha;
use Laminas\Form\Form;
use Laminas\Form\Element;

class MemberForm extends Form {
	public function __construct($name) {
		parent::__construct($name);

		if ($name == 'admin') {
			$this->add([
				'name' => 'is_admin',
				'type' => 'hidden',
				'attributes' => [
					'value' => '1',
				],
			]);
			$this->add([
				'name' => 'is_editor',
				'type' => 'hidden',
				'attributes' => [
					'value' => '1',
				],
			]);
			$this->add([
				'name' => 'setup',
				'type' => 'hidden',
				'attributes' => [
					'value' => '1',
				],
			]);
		} else {
			$this->add([
				'name' => 'is_admin',
				'type' => 'hidden',
				'attributes' => [
					'value' => '0',
				],
			]);
			$this->add([
				'name' => 'is_editor',
				'type' => 'hidden',
				'attributes' => [
					'value' => '0',
				],
			]);
			$this->add([
				'name' => 'setup',
				'type' => 'hidden',
				'attributes' => [
					'value' => '0',
				],
			]);
			$this->add([
				'name' => 'captcha',
				'type' => 'Captcha',
				'options' => [
					'captcha' => new Captcha\ReCaptcha([
						'site_key' => '6LdP_nQfAAAAAK16Np2tFS0hKMzRWiDSOj3MA_g_',
						'secret_key' => '6LdP_nQfAAAAAPggcEHmvrl3JqOGrNPmkqv_KZvh',
					]),
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
		}
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
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Request',
				'id' => 'submitbutton',
			],
		]);
	}
}
