<?php
namespace Securitx\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class TwofaForm extends Form {
	private $site_key, $secret_key;
	public function __construct() {
		parent::__construct();

		$this->add([
			'name' => 'code',
			'type' => 'text',
			'options' => [
				'label' => 'Verification code',
			],
		]);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Verify Code',
				'id' => 'submitbutton',
			],
		]);
	}
}
