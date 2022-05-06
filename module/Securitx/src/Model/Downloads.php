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

class Downloads {
	public $downloads_id;
	public $id_key;
	public $u_key;
	public $e_key;
	public $company_id;

	public function exchangeArray(array $data) {
		$this->downloads_id	= !empty($data['downloads_id']) ? $data['downloads_id'] : null;
		$this->id_key	= !empty($data['id_key']) ? $data['id_key'] : null;
		$this->u_key	= !empty($data['u_key']) ? $data['u_key'] : null;
		$this->e_key	= !empty($data['e_key']) ? $data['e_key'] : null;
		$this->company_id	= !empty($data['company_id']) ? $data['company_id'] : null;
	}
}
