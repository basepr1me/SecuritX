<?php
namespace Securitx\Model;

use RuntimeException;
use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\TableGateway\TableGateway;

class MemberTable {
	private $tableGateway;

	public function __construct(TableGatewayInterface $tableGateway) {
		$this->tableGateway = $tableGateway;
	}
	public function fetchAll() {
		return $this->tableGateway->select();
	}
	public function checkMember($email, $company_id) {
		$rowset = $this->tableGateway->select([
			'company_id' => $company_id,
			'email' => $email,
		]);
		$row = $rowset->current();
		return $row;
	}
	public function getUvMember($id) {
		$rowset = $this->tableGateway->select(['v_key' => $id]);
		$row = $rowset->current();
		return $row;
	}
	public function getVMember($id) {
		$rowset = $this->tableGateway->select(['u_key' => $id]);
		$row = $rowset->current();
		return $row;
	}
	public function getMember($id) {
		$id = (int)$id;
		$rowset = $this->tableGateway->select(['id' => $id]);
		$row = $rowset->current();
		return $row;
	}
	public function saveMember(Member $member) {
		$data = [
			'first' => $member->first,
			'last' => $member->last,
			'office' => $member->office,
			'email' => $member->email,
			'v_key' => $member->v_key,
			'u_key' => $member->u_key,
			'verified' => $member->verified,
			'moddate' => time(),
			'company_id' => intval($member->company_id),
		];

		$id = (int)$member->id;
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
	public function deleteMember(SecuritX $member) {
		$this->tableGateway->delete(['id' => (int)$id]);
	}
}