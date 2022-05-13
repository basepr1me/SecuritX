<?php
namespace Securitx\Model;

use DomainException;

class BlockedDomains {
	public $domain_id;
	public $domain;

	public function exchangeArray(array $data) {
		$this->domain_id	= !empty($data['domain_id']) ? $data['domain_id'] : null;
		$this->domain	= !empty($data['domain']) ? $data['domain'] : null;
	}
}
