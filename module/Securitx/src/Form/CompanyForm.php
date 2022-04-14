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
				'label' => 'Two Letter Short Name',
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
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Add Company',
				'id' => 'submitbutton',
			],
		]);
	}
}
