<?php
namespace Securitx\Controller;

use Securitx\Model\MemberTable;
use Securitx\Model\Member;
use Securitx\Model\CompanyTable;
use Securitx\Model\Company;
use Securitx\Model\DownloadsTable;
use Securitx\Model\Downloads;
use Securitx\Model\Emailer;

use Securitx\Form\MemberForm;
use Securitx\Form\UploaderForm;
use Securitx\Form\CompanyForm;
use Securitx\Form\ForgotForm;

use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;
use Laminas\I18n\Validator\PhoneNumber;

use Laminas\Db\Adapter\Adapter;

use InvalidArgumentException;

class SecuritxController extends AbstractActionController {
	private $company_table, $member_table, $downloads_table;
	private $email_host, $file, $recaptcha, $hipaa;

	public function __construct(MemberTable $member_table,
	    CompanyTable $company_table, DownloadsTable $downloads_table,
	    $email_host, $recaptcha, $hipaa) {
		$this->member_table = $member_table;
		$this->company_table = $company_table;
		$this->downloads_table = $downloads_table;
		$this->email_host = $email_host;
		$this->file = realpath(getcwd()) . "/data/securitx.db";
		$this->recaptcha = $recaptcha;
		$this->hipaa = $hipaa;
	}

	private function getMember() {
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
		return($member);
	}
	private function checkAnyAdmin() {
		$t_member = new Member('admin');
		if (file_exists($this->file)) {
			if ($t_member = $this->member_table->getAnyValidAdmin())
				return 1;
			else
				return 0;
		}
		return 0;
	}
	public function companiesAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		if (!$member->is_admin) {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}
		return new ViewModel();
	}
	public function inviteAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		if (!$member->is_admin || !$member->is_editor) {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}

		$inviter = $member->first . " " .$member->last;
		$m_key = $member->u_key;
		$form = new MemberForm('invite', $this->recaptcha);
		$companies = $this->company_table->fetchAll();
		$request = $this->getRequest();

		if (!$request->isPost()) {
			return new ViewModel([
				'exists' => false,
				'form' => $form,
				'companies' => $companies,
				'registered' => false,
				'valid' => '',
				'id' => $m_key,
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
				'id' => $m_key,
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
				'id' => $m_key,
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
		} else {
			return new ViewModel([
				'exists' => false,
				'form' => $form,
				'companies' => $companies,
				'registered' => false,
				'valid' => 'Please enter a valid email address',
				'id' => $m_key,
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

		$member->inviter = $inviter;
		$this->member_table->saveMember($member);
		$company =
		    $this->company_table->getCompany($member->company_id);

		$emailer = new Emailer($this->email_host);
		$emailer->sendInviteEmail($member->email, $member->first,
		    $member->last, $member->v_key, $url, $this->hipaa['notice'],
		    $company->name, $inviter);

		return new ViewModel([
			'company' => $company->name,
			'first' => $member->first,
			'registered' => true,
			'valid' => '',
			'id' => $m_key,
		]);
	}
	public function sendAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		if (!$member->is_admin || !$member->is_editor) {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}
		return new ViewModel();
	}
	public function userAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		if (!$member->is_admin) {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}
		return new ViewModel();
	}
	public function downloadAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		return new ViewModel();
	}
	public function forgotAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');

		$form = new ForgotForm($this->recaptcha);
		$request = $this->getRequest();

		if (!$request->isPost()) {
			return new ViewModel([
				'form' => $form,
				'valid' => '',
				'completed' => false,
			]);
		}

		$form->setData($request->getPost());
		if (!$form->isValid()) {
			return new ViewModel([
				'form' => $form,
				'valid' => '',
				'completed' => false,
			]);
		}

		$data = $form->getData();
		$validator = new EmailAddress([
			'allow' => Hostname::ALLOW_DNS,
			'useMxCheck' => true,
			'useDeepMxCheck' => true,
			'useDomainCheck' => true,
		]);
		$validator->setOptions([
			'domain' => true,
		]);

		if ($validator->isValid($data['email'])) {
			$member = new Member('member');
			$member = $this->member_table->getMemberByEmail($data['email']);
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

			$company =
			    $this->company_table->getCompany($member->company_id);
			$emailer = new Emailer($this->email_host);
			$emailer->sendMemberEmail($member->email, $member->first,
			    $member->last, $member->v_key, $url, $company->name,
			    $this->hipaa['notice']);
			return new ViewModel([
				'completed' => true,
			]);
		} else {
			return new ViewModel([
				'form' => $form,
				'valid' => 'Please enter a valid email address',
				'completed' => false,
			]);
		}
	}
	public function requestadminAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		return new ViewModel();
	}
	public function requesteditorAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		return new ViewModel();
	}
	public function homeAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');

		$member = $this->getMember();

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

		$has_admin = 1;
		$has_editor = 1;

		/* check member domain */
		$company =
		    $this->company_table->getCompany($member->company_id);
		$my_company = explode("@", $member->email);

		if ($company->domain == $my_company[1])
			$has_domain = 1;
		else
			$has_domain = 0;
		if ($has_domain) {
			if (!$member->is_admin)
				$has_admin = 0;
			if (!$member->is_editor)
				$has_editor = 0;
		}

		$has_downloads =
		    $this->downloads_table->getCount($member->u_key);
		return new ViewModel([
			'member' => $member,
			'has_admin' => $has_admin,
			'has_editor' => $has_editor,
			'has_downloads' => $has_downloads
		]);
	}
	public function indexAction() {
		if (!file_exists($this->file)) {
			if (!$fh = fopen($this->file, "w+")) {
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
				'database' => $this->file,
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
					is_admin INTEGER,
					is_editor INTEGER,
					inviter TEXT,
					r_admin INTEGER,
					r_editor INTEGER,
					ip_address TEXT
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				CREATE TABLE companies (
					company_id INTEGER PRIMARY KEY AUTOINCREMENT,
					name TEXT NOT NULL,
					short TEXT NOT NULL,
					domain TEXT NOT NULL,
					phone TEXT NOT NULL,
					downloads INTEGER,
					is_admin INTEGER
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				CREATE TABLE downloads (
					downloads_id INTEGER PRIMARY KEY AUTOINCREMENT,
					moddate NUMERIC NOT NULL,
					id_key UUID NOT NULL,
					u_key UUID NOT NULL
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
					'valid_domain' => '',
					'valid_phone' => '',
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
					'valid_domain' => '',
					'valid_phone' => '',
					'db' => true,
				]);
			}

			$company->exchangeArray($form->getData());

			$phone_validator = new PhoneNumber([
				'country' => 'US',
				'allow_possible' => true,
			]);
			$phone_test = preg_replace('/[\-\+\(\)\s]+/i', '',
			    $company->phone);
			if (!$phone_validator->isValid($phone_test)) {
				return new ViewModel([
					'company' => false,
					'exists' => false,
					'member' => false,
					'verified' => false,
					'form' => $form,
					'valid_domain' => '',
					'valid_phone' => 'Please enter a valid phone number',
					'db' => true,
				]);
			}
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
					'valid_domain' => 'Please enter a valid domain',
					'valid_phone' => '',
					'db' => true,
				]);
			}
			$company->is_admin = 1;
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
			$form = new MemberForm('admin', $this->recaptcha);
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
			    $url, $this->hipaa['notice']);

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
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');

		$form = new MemberForm('member', $this->recaptcha);
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
		    $member->last, $member->v_key, $url, $this->hipaa['notice']);

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
		    $member->last, $member->v_key, $url, $company->name,
		    $this->hipaa['notice']);

		$this->member_table->saveMember($member);
		$folder = realpath(getcwd()) . "/data/downloads/" .
			    $member->u_key;
		if (!is_dir($folder))
			mkdir($folder, 0755, true);
		return new ViewModel([
			'company' => $company->name,
			'first' => $member->first,
			'id' => $member->u_key,
		]);
	}
	public function uploadAction() {
		$member = $this->getMember();
		if ($member->is_admin || $member->is_editor) {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}
		$member->ip_address = $_SERVER['REMOTE_ADDR'];
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
