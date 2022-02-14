<?php
namespace Securitx\Form;

use Laminas\InputFilter;
use Laminas\Form\Element;
use Laminas\Form\Form;

class UploaderForm extends Form {

	public function __construct($name = null, $options = []) {
		parent::__construct($name, $options);
		$this->addElements($name);
		$this->addInputFilter($name);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Upload Selection',
				'id' => 'submitbutton',
			],
		]);
	}

	public function addElements($name = null) {
		$file = new Element\File($name);
		$file->setLabel('PDF Upload');
		$file->setAttribute('id', 'pdf-files');
		$file->setAttribute('multiple', true);
		$file->setAttribute('accept', 'application/pdf');
		$this->add($file);
	}

	public function addInputFilter($name = null) {
		$inputFilter = new InputFilter\InputFilter();

		$fileInput = new InputFilter\FileInput($name);
		$fileInput->setRequired(true);

		$fileInput->getValidatorChain()
		    ->attachByName('filemimetype', ['mimeType' => 'application/pdf']);
		$fileInput->getFilterChain()->attachByName(
			'filerenameupload',
			[
				'target'=> '/securitx/data/uploads/' . $name . '/' . $name . '_file.pdf',
				'randomize' => true,
			]
		);
		$inputFilter->add($fileInput);
		$this->setInputFilter($inputFilter);
	}
}
