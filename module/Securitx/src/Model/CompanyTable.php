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
	public function getCount() {
		return $this->tableGateway->select()->count();
	}
	public function getCompany($id) {
		$id = (int)$id;
		$rowset = $this->tableGateway->select(['company_id' => $id]);
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	public function getDomainCount($id) {
		return $this->tableGateway->select(['domain' => $id])->count();
	}
	public function getShortCount($id) {
		return $this->tableGateway->select(['short' => $id])->count();
	}
	public function deleteCompany($id) {
		return $this->tableGateway->delete(['company_id' => $id]);
	}
	public function saveCompany(Company $company) {
		$data = [
			'name' => $company->name,
			'short' => $company->short,
			'domain' => $company->domain,
			'phone' => $company->phone,
			'downloads' => $company->downloads,
		];

		$id = (int)$company->company_id;
		if ($id === 0) {
			$this->tableGateway->insert($data);
			return;
		}

		try {
			$this->getCompany($id);
		} catch (RuntimeException $e) {
			return;
		}

		$this->tableGateway->update($data, ['company_id' => $id]);
	}
}
