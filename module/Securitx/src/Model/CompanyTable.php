<?php
namespace Securitx\Model;

use RuntimeException;
use Laminas\Db\TableGateway\TableGatewayInterface;

class CompanyTable {
	private $tableGateway;

	public function __construct(TableGatewayInterface $tableGateway) {
		$this->tableGateway = $tableGateway;
	}
	public function fetchAll() {
		return $this->tableGateway->select();
	}
	public function getCompany($id) {
		$id = (int)$id;
		$rowset = $this->tableGateway->select(['company_id' => $id]);
		$row = $rowset->current();
		if (!$row) {
			throw new RuntimeException(sprintf(
			    'Could not find company row with id %d', $id));
		}
		return $row;
	}
}