<?php
namespace Securitx\Form;

use Laminas\Captcha;
use Laminas\Form\Form;
use Laminas\Form\Element;

class CompanyDeleteForm extends Form {
	public function __construct($name = null) {
		parent::__construct($name);

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
				'value' => 'Delete Company',
				'id' => 'submitbutton',
			],
		]);
	}
}
