<?php
namespace Securitx\Form;

use Laminas\Captcha;
use Laminas\Form\Form;
use Laminas\Form\Element;

class BlockMemberForm extends Form {
	public function __construct() {
		parent::__construct();

		$this->add([
			'name' => 'member_id',
			'type' => 'select',
			'options' => [
				'label' => 'Select Member',
				'disable_inarray_validator' => true,
			],
		]);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Block Member',
				'id' => 'submitbutton',
			],
		]);
	}
}
