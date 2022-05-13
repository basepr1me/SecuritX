<?php
namespace Securitx\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class BlockDomainForm extends Form {
	public function __construct() {
		parent::__construct();
		$this->add([
			'name' => 'domain',
			'type' => 'text',
			'options' => [
				'label' => 'Domain Name',
			],
		]);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Block This Domain',
				'id' => 'submitbutton',
			],
		]);
	}
}
