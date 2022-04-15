<?php
namespace Securitx\Model;

use DomainException;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Filter\ToInt;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\Validator\StringLength;

class Company implements InputFilterAwareInterface {
	public $company_id;
	public $name;
	public $short;
	public $domain;
	public $phone;
	public $downloads;
	public $is_admin;

	private $inputFilter;

	public function exchangeArray(array $data) {
		$this->company_id	= !empty($data['company_id']) ? $data['company_id'] : null;
		$this->name	= !empty($data['name']) ? $data['name'] : null;
		$this->short	= !empty($data['short']) ? $data['short'] : null;
		$this->phone	= !empty($data['phone']) ? $data['phone'] : null;
		$this->domain	= !empty($data['domain']) ? $data['domain'] : null;
		$this->downloads	= !empty($data['downloads']) ? $data['downloads'] : 0;
		$this->is_admin	= !empty($data['is_admin']) ? $data['is_admin'] : 0;
	}

	public function setInputFilter(InputFilterInterface $inputFilter) {
		throw new DomainException(sprintf(
		    '%s does not allow injection of an alternate input filter',
		    __CLASS__
		));
	}

	public function getInputFilter() {
		if ($this->inputFilter) {
			return $this->inputFilter;
		}

		$inputFilter = new InputFilter();

		$inputFilter->add([
			'name' => 'name',
			'required' => true,
			'filters' => [
				['name' => StripTags::class],
				['name' => StringTrim::class],
			],
			'validators' => [
				[
					'name' => StringLength::class,
					'options' => [
						'encoding' => 'UTF-8',
						'min' => 1,
						'max' => 100,
					],
				],
			],
		]);

		$inputFilter->add([
			'name' => 'short',
			'required' => true,
			'filters' => [
				['name' => StripTags::class],
				['name' => StringTrim::class],
			],
			'validators' => [
				[
					'name' => StringLength::class,
					'options' => [
						'encoding' => 'UTF-8',
						'min' => 2,
						'max' => 2,
					],
				],
			],
		]);

		$inputFilter->add([
			'name' => 'domain',
			'required' => true,
			'filters' => [
				['name' => StripTags::class],
				['name' => StringTrim::class],
			],
			'validators' => [
				[
					'name' => StringLength::class,
					'options' => [
						'encoding' => 'UTF-8',
						'min' => 1,
						'max' => 100,
					],
				],
			],
		]);

		$this->inputFilter = $inputFilter;
		return $this->inputFilter;
	}
}
