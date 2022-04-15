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
			throw new RuntimeException(sprintf(
			    'Could not find company row with id %d', $id));
		}
		return $row;
	}
	public function saveCompany(Company $company) {
		$data = [
			'name' => $company->name,
			'short' => $company->short,
			'domain' => $company->domain,
			'phone' => $company->phone,
			'downloads' => $company->downloads,
			'is_admin' => $company->is_admin,
		];

		$id = (int)$company->company_id;
		if ($id === 0) {
			$this->tableGateway->insert($data);
			return;
		}

		try {
			$this->getMember($id);
		} catch (RuntimeException $e) {
			return;
		}

		$this->tableGateway->update($data, ['id' => $id]);
	}
}
