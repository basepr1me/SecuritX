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

	public function exchangeArray(array $data) {
		$this->downloads_id	= !empty($data['downloads_id']) ? $data['downloads_id'] : null;
		$this->id_key	= !empty($data['id_key']) ? $data['id_key'] : null;
		$this->u_key	= !empty($data['u_key']) ? $data['u_key'] : null;
	}
}
