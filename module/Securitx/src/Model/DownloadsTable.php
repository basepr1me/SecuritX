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
	public function getDownload($id) {
		$rowset = $this->tableGateway->select(['downloads_id' => $id]);
		$row = $rowset->current();
		return $row;
	}
	public function getCount($u_key) {
		return $this->tableGateway->select([
			'u_key' => $u_key,
			'company_id' => 0,
		])->count();
	}
	public function getCCount($c_id) {
		return $this->tableGateway->select([
			'company_id' =>$c_id,
		])->count();
	}
	public function getDownloads($u_key) {
		return $this->tableGateway->select([
			'u_key' => $u_key,
			'company_id' => 0,
		]);
	}
	public function getCDownloads($c_id) {
		return $this->tableGateway->select([
			'company_id' =>$c_id,
		]);
	}
	public function deleteDownload($id) {
		return $this->tableGateway->delete(['downloads_id' => $id]);
	}
	public function saveDownload(Downloads $download) {
		$data = [
			'moddate' => $download->moddate,
			'id_key' => $download->id_key,
			'u_key' => $download->u_key,
			'downloaded' => $download->downloaded,
			'e_key' => $download->e_key,
			'company_id' => $download->company_id,
		];

		$id = (int)$download->downloads_id;
		if ($id === 0) {
			$this->tableGateway->insert($data);
			return;
		}

		try {
			$this->getDownload($id);
		} catch (RuntimeException $e) {
			return;
		}
		$this->tableGateway->update($data, ['downloads_id' => $id]);
	}
}
