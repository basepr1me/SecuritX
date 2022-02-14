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

class Member implements InputFilterAwareInterface {
	public $id;
	public $first;
	public $last;
	public $office;
	public $email;
	public $v_key;
	public $u_key;
	public $verified;
	public $moddate;
	public $company_id;

	private $inputFilter;

	public function exchangeArray(array $data) {
		$this->id	= !empty($data['id']) ? $data['id'] : null;
		$this->first	= !empty($data['first']) ? $data['first'] : null;
		$this->last	= !empty($data['last']) ? $data['last'] : null;
		$this->office	= !empty($data['office']) ? $data['office'] : null;
		$this->email	= !empty($data['email']) ? $data['email'] : null;
		$this->v_key	= !empty($data['v_key']) ? $data['v_key'] : null;
		$this->u_key	= !empty($data['u_key']) ? $data['u_key'] : null;
		$this->verified	= !empty($data['verified']) ? $data['verified'] : null;
		$this->moddate	= !empty($data['moddate']) ? $data['moddate'] : null;
		$this->company_id	= !empty($data['company_id']) ? $data['company_id'] : null;
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
			'name' => 'id',
			'required' => true,
			'filters' => [
				['name' => ToInt::class],
			],
		]);

		$inputFilter->add([
			'name' => 'first',
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
			'name' => 'last',
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
			'name' => 'office',
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
			'name' => 'email',
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
