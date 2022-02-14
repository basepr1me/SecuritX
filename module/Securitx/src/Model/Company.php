<?php
namespace Securitx\Model;

class Company {
	public $company_id;
	public $name;
	public $short;

	public function exchangeArray(array $data) {
		$this->company_id	= !empty($data['company_id']) ? $data['company_id'] : null;
		$this->name	= !empty($data['name']) ? $data['name'] : null;
		$this->short	= !empty($data['short']) ? $data['short'] : null;
	}
}
