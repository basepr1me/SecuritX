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
	public $is_admin;
	public $is_editor;
	public $inviter;
	public $r_admin;
	public $r_editor;
	public $ip_address;
	public $company_id;
	public $twofa;
	public $twofa_moddate;
	public $phone;

	private $inputFilter;
	private $val;

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
		$this->is_admin = !empty($data['is_admin']) ? $data['is_admin'] : 0;
		$this->is_editor = !empty($data['is_editor']) ? $data['is_editor'] : 0;
		$this->inviter = !empty($data['inviter']) ? $data['inviter'] : null;
		$this->r_admin = !empty($data['r_admin']) ? $data['r_admin'] : 0;
		$this->r_editor = !empty($data['r_editor']) ? $data['r_editor'] : 0;
		$this->ip_address = !empty($data['ip_address']) ? $data['ip_address'] : 0;
		$this->company_id	= !empty($data['company_id']) ? $data['company_id'] : null;
		$this->twofa	= !empty($data['twofa']) ? $data['twofa'] : null;
		$this->twofa_moddate	= !empty($data['twofa_moddate']) ? $data['twofa_moddate'] : null;
		$this->phone	= !empty($data['phone']) ? $data['phone'] : null;
	}

	public function setInputFilter(InputFilterInterface $inputFilter) {
		throw new DomainException(sprintf(
		    '%s does not allow injection of an alternate input filter',
		    __CLASS__
		));
	}

	public function setFilter($val) {
		$this->val = $val;
	}

	public function getInputFilter() {
		if ($this->inputFilter) {
			return $this->inputFilter;
		}

		$inputFilter = new InputFilter();

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
			'required' => $this->val,
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
