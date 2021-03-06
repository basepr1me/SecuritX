<?php
namespace Securitx\Form;

use Laminas\Captcha;
use Laminas\Form\Form;
use Laminas\Form\Element;

class CompanyForm extends Form {
	public function __construct($name = null) {
		parent::__construct($name);

		$this->add([
			'name' => 'name',
			'type' => 'text',
			'options' => [
				'label' => 'Company Name',
			],
		]);
		$this->add([
			'name' => 'short',
			'type' => 'text',
			'options' => [
				'label' => 'Short Name (2-5 Letters)',
			],
		]);
		$this->add([
			'name' => 'domain',
			'type' => 'text',
			'options' => [
				'label' => 'Company\'s Email Domain',
			],
		]);
		$this->add([
			'name' => 'phone',
			'type' => Element\Tel::class,
			'options' => [
				'label' => 'Company\'s Primary Phone Number',
			],
		]);
		$this->add([
			'name' => 'downloads',
			'type' => 'checkbox',
			'options' => [
				'label' => 'Enable Browser Downloads',
				'use_hidden_element' => true,
				'checked_value' => '1',
				'unchecked_value' => '0',
			],
			'attributes' => [
				'value' => '0',
			],
		]);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Add Company',
				'id' => 'submitbutton',
			],
		]);
	}
}
