<?php
namespace Securitx\Form;

use Laminas\InputFilter;
use Laminas\Form\Element;
use Laminas\Form\Form;

class SendForm extends Form {

	public function __construct($name) {
		parent::__construct($name);
		$this->addElements($name);
		$this->addInputFilter($name);
		$this->add([
			'name' => 'member_id',
			'type' => 'select',
			'options' => [
				'label' => 'Select Member To Receive Documents (Name, Email, Office)',
				'disable_inarray_validator' => true,
			],
		]);
		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Upload Selection To Member',
				'id' => 'submitbutton',
			],
		]);
	}

	public function addElements($name) {
		$file = new Element\File($name);
		$file->setLabel('PDF Upload');
		$file->setAttribute('id', 'pdf-files');
		$file->setAttribute('multiple', true);
		$file->setAttribute('accept', 'application/pdf');
		$this->add($file);
	}

	public function addInputFilter($name) {
		$inputFilter = new InputFilter\InputFilter();

		$fileInput = new InputFilter\FileInput($name);
		$fileInput->setRequired(true);

		/*
		 * XXX: some servers save pdfs as mime-type
		 * application/octet-stream. this validator blocks those
		 * legitimate pdfs if only application/pdf is set, therefore, we
		 * have to validate both types, unfortunately.
		 */
		$fileInput->getValidatorChain()->attachByName(
			'filemimetype', [
				'mimeType' => [
					'application/pdf',
					'application/octet-stream',
				]
			]
		);

		$fileInput->getFilterChain()->attachByName(
			'filerenameupload',
			[
				'target'=> '/securitx/data/tmp/file.pdf',
				'randomize' => true,
			]
		);
		$inputFilter->add($fileInput);
		$this->setInputFilter($inputFilter);
	}
}
