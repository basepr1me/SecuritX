<?php
namespace Securitx\Model;

use RuntimeException;
use Laminas\Db\TableGateway\TableGatewayInterface;

class DownloadsTable {
	private $tableGateway;

	public function __construct(TableGatewayInterface $tableGateway) {
		$this->tableGateway = $tableGateway;
	}
	public function fetchAll() {
		return $this->tableGateway->select();
	}
	public function getCount($u_key) {
		return $this->tableGateway->select(['u_key' => $u_key])->count();
	}
	public function getCountC($c_id) {
		return $this->tableGateway->select([
			'company_id' =>$c_id,
		])->count();
	}
	public function getDownloads($u_key) {
		return $this->tableGateway->select()(['u_key' => $u_key]);
	}
	public function saveDownload(Downloads $download) {
		$data = [
			'moddate' => time(),
			'id_key' => $download->id_key,
			'u_key' => $download->u_key,
			'e_key' => $download->e_key,
			'company_id' => $download->company_id,
		];

		$this->tableGateway->insert($data);
	}
}
