<?php
namespace Securitx\Controller;

use Securitx\Model\MemberTable;
use Securitx\Model\Member;
use Securitx\Model\CompanyTable;
use Securitx\Model\Company;
use Securitx\Model\Emailer;

use Securitx\Form\MemberForm;
use Securitx\Form\UploaderForm;
use Securitx\Form\CompanyForm;

use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;

use Laminas\Db\Adapter\Adapter;

use InvalidArgumentException;

class SecuritxController extends AbstractActionController {
	private $company_table, $member_table, $email_host;

	public function __construct(MemberTable $member_table,
	    CompanyTable $company_table, $email_host) {
		$this->member_table = $member_table;
		$this->company_table = $company_table;
		$this->email_host = $email_host;
	}

	public function companiesAction() {
		return new ViewModel();
	}

	public function inviteAction() {
		return new ViewModel();
	}

	public function sendAction() {
		return new ViewModel();
	}

	public function downloadAction() {
		return new ViewModel();
	}

	public function forgotAction() {
		return new ViewModel();
	}

	public function requestadminAction() {
		return new ViewModel();
	}

	public function requesteditorAction() {
		return new ViewModel();
	}

	public function homeAction() {
		$member = new Member('member');

		$id = $this->params()->fromRoute('id');

		try {
			$member = $this->member_table->getVMember($id);
		} catch (\InvalidArgumentException $ex) {
			return $this->redirect()->toRoute('securitx');
		}
		if (!$member || !$member->verified)
			return $this->redirect()->toRoute('securitx');

		$member->moddate = time();
		$this->member_table->saveMember($member);
		return new ViewModel([
			'member' => $member,
		]);
	}
	public function indexAction() {
		$file = realpath(getcwd()) . "/data/securitx.db";
		if (!file_exists($file)) {
			if (!$fh = fopen($file, "w+")) {
				return new ViewModel([
					'db' => false,
				]);
			}
			if (fwrite($fh, "") === FALSE) {
				return new ViewModel([
					'db' => false,
				]);
			}
			fclose($fh);
			$adapter = new Adapter([
				'driver' => 'Pdo_Sqlite',
				'database' => $file,
			]);
			$adapter->query('
				CREATE TABLE members (
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					first TEXT NOT NULL,
					last TEXT NOT NULL,
					office TEXT NOT NULL,
					email TEXT NOT NULL,
					v_key UUID,
					u_key UUID,
					verified INTEGER NOT NULL,
					moddate NUMERIC NOT NULL,
					company_id INTEGER NOT NULL,
					is_admin INTEGER NOT NULL,
					is_editor INTEGER NOT NULL,
					inviter TEXT,
					r_admin INTEGER,
					r_editory INTEGER<
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				CREATE TABLE companies (
					company_id INTEGER PRIMARY KEY AUTOINCREMENT,
					name TEXT NOT NULL,
					short TEXT NOT NULL,
					domain TEXT NOT NULL
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				CREATE TABLE downloads (
					downloads_id INTEGER PRIMARY KEY AUTOINCREMENT,
					id_key UUID,
					u_key UUID
				)
			', Adapter::QUERY_MODE_EXECUTE);
		}
		/* setup initial company */
		$co_added = 0;
		$co_cnt = $this->company_table->getCount();
		if ($co_cnt == 0) {
			$form = new CompanyForm();
			$request = $this->getRequest();
			if (!$request->isPost()) {
				return new ViewModel([
					'company' => false,
					'exists' => false,
					'member' => false,
					'verified' => false,
					'form' => $form,
					'valid' => '',
					'db' => true,
				]);
			}

			$company = new Company();
			$form->setInputFilter($company->getInputFilter());
			$form->setData($request->getPost());
			if (!$form->isValid()) {
				return new ViewModel([
					'company' => false,
					'exists' => false,
					'member' => false,
					'verified' => false,
					'form' => $form,
					'valid' => '',
					'db' => true,
				]);
			}

			$company->exchangeArray($form->getData());

			$validator = new Hostname([
				'allow' => Hostname::ALLOW_DNS,
				'useMxCheck' => true,
				'useDeepMxCheck' => true,
				'useDomainCheck' => true,
			]);
			$validator->setOptions([
				'domain' => true,
			]);

			if (!$validator->isValid($company->domain)) {
				return new ViewModel([
					'company' => false,
					'exists' => false,
					'member' => false,
					'verified' => false,
					'form' => $form,
					'valid' => 'Please enter a valid domain',
					'db' => true,
				]);
			}
			$this->company_table->saveCompany($company);
			$folder = realpath(getcwd()) . "/data/uploads/" .
			    $company->short;
			if (!is_dir($folder))
				mkdir($folder, 0755, true);
			$co_added = 1;
		}

		/* setup admin member */
		$member = new Member('member');
		$member = $this->member_table->getAnyAdmin();
		if ($member) {
			if (!$member->verified) {
				return new ViewModel([
					'company' => true,
					'exists' => true,
					'member' => true,
					'verified' => false,
					'db' => true,
				]);
			}
			return new ViewModel([
				'company' => true,
				'exists' => true,
				'member' => true,
				'verified' => true,
				'db' => true,
			]);
		} else {
			$form = new MemberForm('admin');
			$request = $this->getRequest();

			if ($co_added == 1 || !$request->isPost()) {
				return new ViewModel([
					'company' => true,
					'exists' => true,
					'member' => false,
					'form' => $form,
					'valid' => '',
					'verified' => false,
					'db' => true,
				]);
			}

			$member = new Member('member');
			$form->setInputFilter($member->getInputFilter());
			$form->setData($request->getPost());
			if (!$form->isValid()) {
				return new ViewModel([
					'company' => true,
					'exists' => true,
					'member' => false,
					'form' => $form,
					'valid' => '',
					'verified' => false,
					'db' => true,
				]);
			}

			$member->exchangeArray($form->getData());

			$validator = new EmailAddress([
				'allow' => Hostname::ALLOW_DNS,
				'useMxCheck' => true,
				'useDeepMxCheck' => true,
				'useDomainCheck' => true,
			]);
			$validator->setOptions([
				'domain' => true,
			]);

			if ($validator->isValid($member->email)) {
				$member->v_key = uniqid();
				$member->verified = 0;
			} else {
				return new ViewModel([
					'company' => true,
					'exists' => true,
					'member' => false,
					'form' => $form,
					'valid' => 'Please enter a valid email address',
					'verified' => false,
					'db' => true,
				]);
			}

			$url = $this->url()->fromRoute(
				'securitx',
				[
					'action' => 'verify',
					'id' => $member->v_key,
				],
				[
				'force_canonical' => true,
				]
			);
			$emailer = new Emailer($this->email_host);
			$emailer->sendVerifyEmail($member->email,
			    $member->first, $member->last, $member->v_key,
			    $url);

			$companies = $this->company_table->fetchAll();
			foreach ($companies as $company)
				$member->company_id = $company->company_id;

			$this->member_table->saveMember($member);

			return new ViewModel([
				'company' => true,
				'exists' => true,
				'member' => true,
				'verified' => false,
				'db' => true,
			]);
		}

	}
	public function requestAction() {
		$form = new MemberForm('member');
		$companies = $this->company_table->fetchAll();
		$request = $this->getRequest();

		if (!$request->isPost()) {
			return new ViewModel([
				'exists' => false,
				'form' => $form,
				'companies' => $companies,
				'registered' => false,
				'valid' => '',
			]);
		}

		$member = new Member('member');
		$form->setInputFilter($member->getInputFilter());
		$form->setData($request->getPost());

		if (!$form->isValid()) {
			return new ViewModel([
				'exists' => false,
				'form' => $form,
				'companies' => $companies,
				'registered' => false,
				'valid' => '',
			]);
		}

		$member->exchangeArray($form->getData());

		if ($this->member_table->checkMember($member->email,
		    $member->company_id)) {
			return new ViewModel([
				'exists' => true,
				'form' => $form,
				'companies' => $companies,
				'registered' => false,
				'valid' => '',
			]);
		}

		$validator = new EmailAddress([
			'allow' => Hostname::ALLOW_DNS,
			'useMxCheck' => true,
			'useDeepMxCheck' => true,
			'useDomainCheck' => true,
		]);
		$validator->setOptions([
			'domain' => true,
		]);

		if ($validator->isValid($member->email)) {
			$member->v_key = uniqid();
			$member->verified = 0;
			$member->is_admin = 0;
			$member->is_editor = 0;
		} else {
			return new ViewModel([
				'exists' => false,
				'form' => $form,
				'companies' => $companies,
				'registered' => false,
				'valid' => 'Please enter a valid email address',
			]);
		}

		$url = $this->url()->fromRoute(
			'securitx',
			[
				'action' => 'verify',
				'id' => $member->v_key,
			],
			[
				'force_canonical' => true,
			]
		);

		$emailer = new Emailer($this->email_host);
		$emailer->sendVerifyEmail($member->email, $member->first,
		    $member->last, $member->v_key, $url);

		$this->member_table->saveMember($member);
		$company =
		    $this->company_table->getCompany($member->company_id);

		return new ViewModel([
			'company' => $company->name,
			'first' => $member->first,
			'registered' => true,
			'valid' => '',
		]);
	}
	public function verifyAction() {
		$member = new Member('member');

		$id = $this->params()->fromRoute('id');

		try {
			$member = $this->member_table->getUvMember($id);
		} catch (\InvalidArgumentException $ex) {
			return $this->redirect()->toRoute('securitx');
		}
		if (!$member || $member->verified)
			return $this->redirect()->toRoute('securitx');

		$member->v_key = '';
		$member->u_key = uniqid();
		$member->verified = 1;
		$member->moddate = time();

		$company =
		$this->company_table->getCompany($member->company_id);

		$url = $this->url()->fromRoute(
			'securitx',
			[
				'action' => 'home',
				'id' => $member->u_key,
			],
			[
				'force_canonical' => true,
			]
		);

		$emailer = new Emailer($this->email_host);
		$emailer->sendMemberEmail($member->email, $member->first,
		$member->last, $member->v_key, $url, $company->name);

		$this->member_table->saveMember($member);
		return new ViewModel([
			'company' => $company->name,
			'first' => $member->first,
		]);
	}
	public function uploadAction() {
		$member = new Member('member');

		$id = $this->params()->fromRoute('id');

		try {
			$member = $this->member_table->getVMember($id);
		} catch (\InvalidArgumentException $ex) {
			return $this->redirect()->toRoute('securitx');
		}
		if (!$member || !$member->verified)
			return $this->redirect()->toRoute('securitx');

		$member->moddate = time();
		$this->member_table->saveMember($member);

		$company =
		$this->company_table->getCompany($member->company_id);

		$form = new UploaderForm($company->short);
		$request = $this->getRequest();

		if (!$request->isPost()) {
			return new ViewModel([
				'form' => $form,
				'company' => $company->name,
				'short' => $company->short,
				'first' => $member->first,
			]);
		}

		$post = array_merge_recursive(
			$request->getPost()->toArray(),
			$request->getFiles()->toArray()
		);

		$form->setData($post);
		if ($form->isValid()) {
			$data = $form->getData();
			if (! empty($post['isAjax'])) {
				return new JsonModel(array(
					'status'   => true,
					'formData' => $data,
					'form' => $form,
					'company' => $company->name,
					'short' => $company->short,
					'first' => $member->first,
				));
			}
		}

		return new ViewModel([
			'form' => $form,
			'company' => $company->name,
			'short' => $company->short,
			'first' => $member->first,
		]);
	}
}
