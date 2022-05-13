<?php
namespace Securitx\Model;

use RuntimeException;
use Laminas\Db\TableGateway\TableGatewayInterface;

class BlockedDomainsTable {
	private $tableGateway;

	public function __construct(TableGatewayInterface $tableGateway) {
		$this->tableGateway = $tableGateway;
	}
	public function fetchAll() {
		return $this->tableGateway->select();
	}
	public function saveDomain(BlockedDomains $domain) {
		$data = [
			'domain' => $domain->domain,
		];

		$this->tableGateway->insert($data);
		return;
	}
}
