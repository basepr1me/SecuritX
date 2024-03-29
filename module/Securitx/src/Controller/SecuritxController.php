<?php
namespace Securitx\Controller;

use Securitx\Model\MemberTable;
use Securitx\Model\Member;
use Securitx\Model\CompanyTable;
use Securitx\Model\Company;
use Securitx\Model\DownloadsTable;
use Securitx\Model\Downloads;
use Securitx\Model\Emailer;
use Securitx\Model\BlockedDomainsTable;
use Securitx\Model\BlockedDomains;

use Securitx\Form\MemberForm;
use Securitx\Form\UploaderForm;
use Securitx\Form\CompanyForm;
use Securitx\Form\CompanyEditForm;
use Securitx\Form\CompanyDeleteForm;
use Securitx\Form\ForgotForm;
use Securitx\Form\SendForm;
use Securitx\Form\TwofaForm;
use Securitx\Form\BlockDomainForm;
use Securitx\Form\BlockMemberForm;

use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;
use Laminas\I18n\Validator\PhoneNumber;

use Laminas\Db\Adapter\Adapter;

use Laminas\Crypt\FileCipher;

use Laminas\Http\Client;
use Laminas\Http\PhpEnvironment\Response;


use InvalidArgumentException;

class SecuritxController extends AbstractActionController {
	private $company_table, $member_table, $downloads_table;
	private $email_host, $file, $recaptcha, $hipaa;

	public function __construct(MemberTable $member_table,
	    CompanyTable $company_table, DownloadsTable $downloads_table,
	    BlockedDomainsTable $blockeddomains_table, $email_host, $recaptcha,
	    $hipaa) {
		$this->member_table = $member_table;
		$this->company_table = $company_table;
		$this->downloads_table = $downloads_table;
		$this->blockeddomains_table = $blockeddomains_table;
		$this->email_host = $email_host;
		$this->file = realpath(getcwd()) . "/data/securitx.db";
		$this->recaptcha = $recaptcha;
		$this->hipaa = $hipaa;
	}

	private function getMember() {
		$member = new Member();
		$id = $this->params()->fromRoute('id');

		try {
			$member = $this->member_table->getVMember($id);
		} catch (\InvalidArgumentException $ex) {
			return $this->redirect()->toRoute('securitx');
		}
		if (!$member || !$member->verified || $member->blocked)
			return $this->redirect()->toRoute('securitx');
		if ((time() - $member->moddate) > 60 * 60 * 24) {
			if ((time() - $member->twofa_moddate) > 60 * 10 ||
			    !$member->twofa_moddate) {
				$member->twofa = rand(1000000, 9999999);
				$member->twofa_moddate = time();
				$this->member_table->saveMember($member);
				$emailer = new Emailer($this->email_host);
				$emailer->sendTwofa($member->email,
				    $member->first, $member->last, $url,
				    $this->hipaa['notice'], $member->twofa);
			}
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'twofa',
					'id' => $member->u_key,
				)
			);
		}

		$member->moddate = time();
		$this->member_table->saveMember($member);
		return($member);
	}
	private function checkAnyAdmin() {
		$t_member = new Member();
		if (file_exists($this->file)) {
			if ($t_member = $this->member_table->getAnyValidAdmin())
				return 1;
			else
				return 0;
		}
		return 0;
	}
	public function blockdomainAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		if (!$member->is_admin) {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $admin->u_key,
				)
			);
		}
		$form = new BlockDomainForm();
		$request = $this->getRequest();

		if (!$request->isPost()) {
			return new ViewModel([
				'form' => $form,
				'completed' => false,
				'first' => $member->first,
				'id' => $member->u_key,
				'valid' => '',
			]);
		}
		$form->setData($request->getPost());
		if (!$form->isValid()) {
			return new ViewModel([
				'form' => $form,
				'completed' => false,
				'first' => $member->first,
				'id' => $member->u_key,
				'valid' => '',
			]);
		}

		$domain = new BlockedDomains();
		$domain->exchangeArray($form->getData());
		$domains = $this->blockeddomains_table->fetchAll();
		foreach ($domains as $tdomain) {
			if ($tdomain->domain == $domain->domain) {
				return new ViewModel([
					'form' => $form,
					'completed' => false,
					'first' => $member->first,
					'id' => $member->u_key,
					'valid' => 'That domain is already blocked',
				]);
			}
		}

		$this->blockeddomains_table->saveDomain($domain);
		return new ViewModel([
			'form' => $form,
			'completed' => true,
			'first' => $member->first,
			'valid' => '',
		]);
	}
	public function authempAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$admin = $this->getMember();
		if (!$admin->is_admin)
			goto ret;
		if ($_GET) {
			if (array_key_exists('member', $_GET))
				$member = $this->member_table->getVMember(
				    $_GET['member']);
			else
				goto ret;
			if (!$member)
				goto ret;
			if (array_key_exists('type', $_GET))
				$type = $_GET['type'];
			else
				goto ret;
			if (array_key_exists('action', $_GET))
				$action = $_GET['action'];
			else
				goto ret;
			if ($action == "allow") {
				if ($type == "admin")
					$member->is_admin = 1;
				else
					$member->is_editor = 1;
			} else {
				if ($type == "admin")
					$member->is_admin = 2;
				else
					$member->is_editor = 2;
			}
			$company =
			    $this->company_table->getCompany($member->company_id);
			$member->r_admin = 0;
			$member->r_editor = 0;
			$this->member_table->saveMember($member);

			$emailer = new Emailer($this->email_host);
			$emailer->sendAuthEmail($member->email, $member->first,
			    $member->last, $action, $type,
			    $company->name, $this->hipaa['notice']);
		}
ret:
		return $this->redirect()->toRoute('securitx',
			array(
				'action' => 'home',
				'id' => $admin->u_key,
			)
		);
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
		if ($_GET) {
			$companies = '';
			$id = '';
			if (array_key_exists('add', $_GET)) {
				$form = new CompanyForm();
				$action = "add";
			} else if (array_key_exists('edit', $_GET)) {
				$form = new CompanyEditForm();
				$companies = $this->company_table->fetchAll();
				$action = "edit";
				if (array_key_exists('id', $_GET))
					$id = $_GET['id'];
			} else if (array_key_exists('delete', $_GET)) {
				$form = new CompanyDeleteForm();
				$companies = $this->company_table->fetchAll();
				$action = "delete";
			} else
				goto ret;
			$count = $this->company_table->getCount();
			if ($count == 1 && $action == "delete")
				goto ret;
			$request = $this->getRequest();
			if (!$request->isPost()) {
				return new ViewModel([
					'member' => $member,
					'action' => $action,
					'exists' => '',
					'form' => $form,
					'completed' => false,
					'valid_domain' => '',
					'valid_phone' => '',
					'companies' => $companies,
					'company' => '',
				]);
			}
			$company = new Company();
			if ($action == "add")
				$form->setInputFilter($company->getInputFilter());
			if ($action == "add" || $action == "delete") {
				$form->setData($request->getPost());
				if (!$form->isValid()) {
					return new ViewModel([
						'member' => $member,
						'action' => $action,
						'exists' => '',
						'form' => $form,
						'completed' => false,
						'valid_domain' => '',
						'valid_phone' => '',
					]);
				}

				$company->exchangeArray($form->getData());
				$count = $this->company_table->getShortCount(
					$company->short
				);

				if ($count) {
					return new ViewModel([
						'member' => $member,
						'action' => $action,
						'exists' => 'Short name already exists',
						'form' => $form,
						'completed' => false,
						'valid_domain' => '',
						'valid_phone' => '',
					]);
				}
				$domain = $this->company_table->getDomainCount(
					$company->domain
				);
				if ($domain) {
					return new ViewModel([
						'member' => $member,
						'action' => $action,
						'exists' => '',
						'form' => $form,
						'completed' => false,
						'valid_domain' => 'Domain already exists',
						'valid_phone' => '',
					]);
				}
			}
			if ($action == "edit" && $id) {
				$form->setInputFilter($company->getInputFilter());
				$form->setData($request->getPost());
				if (!$form->isValid()) {
					return new ViewModel([
						'member' => $member,
						'action' => $action,
						'form' => $form,
						'completed' => false,
						'valid_domain' => '',
						'valid_phone' => '',
						'company' => $ce,
					]);
				}

				$company->exchangeArray($form->getData());
				$this->company_table->saveCompany($company);
				return new ViewModel([
					'member' => $member,
					'action' => 'edited',
					'completed' => true,
				]);
			} else if ($action == "edit" && !$id) {
				$form->setData($request->getPost());
				$form->isValid();
				$company->exchangeArray($form->getData());
				$ce = $this->company_table->getCompany(
					$company->company_id
				);
				$form = new CompanyEditForm();
				return new ViewModel([
					'member' => $member,
					'action' => $action,
					'form' => $form,
					'completed' => false,
					'valid_domain' => '',
					'valid_phone' => '',
					'company' => $ce,
				]);
			}
			if ($action == "delete") {
				$cd = $this->company_table->getCompany(
					$company->company_id
				);
				if ($cd == null)
					goto ret;
				$members = $this->member_table->getMembersByCompany(
					$cd->company_id
				);
				$cfolder = realpath(getcwd()) . "/data/uploads/" .
				    $cd->short;
				$cdls = $this->downloads_table->getCDownloads(
					$cd->company_id
				);
				foreach ($cdls as $cdl) {
					$file = $cfolder . "/" . $cdl . ".pdf";
					if (file_exists($file))
						unlink($file);
				}
				if (is_dir($cfolder))
					rmdir($cfolder);
				$this->company_table->deleteCompany($cd->company_id);
				foreach ($members as $member) {
					$folder = realpath(getcwd()) .
					    "/data/downloads/" . $member->u_key;
					$dls = $this->downloads_table->getDownloads(
						$member->u_key
					);
					foreach($dls as $dl) {
						$file = $folder . "/" .
						    $dl->id_key . ".pdf";
						if (file_exists($file))
							unlink($file);
						$this->downloads_table->deleteDownload(
							$dl->downloads_id
						);
					}
					if (is_dir($folder))
						rmdir($folder);
					$this->member_table->deleteMember(
						$member->id
					);
				}
				return new ViewModel([
					'member' => $member,
					'action' => 'deleted',
					'completed' => true,
				]);
			}
			$phone_validator = new PhoneNumber([
				'country' => 'US',
				'allow_possible' => true,
			]);
			$phone_test = preg_replace('/[\-\+\(\)\s]+/i', '',
			    $company->phone);
			if (!$phone_validator->isValid($phone_test)) {
				return new ViewModel([
					'member' => $member,
					'action' => $action,
					'exists' => '',
					'form' => $form,
					'completed' => false,
					'valid_domain' => '',
					'valid_phone' => '',
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
					'member' => $member,
					'action' => $action,
					'exists' => '',
					'form' => $form,
					'completed' => false,
					'valid_domain' => '',
					'valid_phone' => '',
				]);
			}
			$this->company_table->saveCompany($company);

			if ($action == "add") {
				$folder = realpath(getcwd()) . "/data/uploads/" .
				    $company->short;
				if (!is_dir($folder))
					mkdir($folder, 0755, true);
				return new ViewModel([
					'member' => $member,
					'action' => 'added',
					'completed' => true,
				]);
			}
			if ($action == "delete") {
				/* delete folder here */
			}
ret:
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		} else {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}
	}
	public function inviteAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		if (!$member->is_admin && !$member->is_editor) {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}

		$inviter = $member->first . " " .$member->last;
		$inviter_id = $member->id;
		$m_key = $member->u_key;
		$form = new MemberForm('invite', $this->recaptcha);
		if ($member->is_admin)
			$companies = $this->company_table->fetchAll();
		else {
			$companies = $this->company_table->getCCompany(
				$member->company_id
			);
		}
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

		$member = new Member();
		$member->setFilter(false);
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
		$member->moddate = time();
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

		$member->inviter = $inviter_id;
		$this->member_table->saveMember($member);
		$company =
		    $this->company_table->getCompany($member->company_id);

		$emailer = new Emailer($this->email_host);
		$emailer->sendInviteEmail($member->email, $member->first,
		    $member->last, $url, $this->hipaa['notice'],
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
		$invited = 0;
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$admin = $this->getMember();
		if ($admin->inviter)
			$invited = 1;
		else if (!$admin->is_admin) {
		       if (!$admin->is_editor) {
				return $this->redirect()->toRoute('securitx',
					array(
						'action' => 'home',
						'id' => $admin->u_key,
					)
				);
			}
		}

		if ($invited) {
			$members = array();
			$member =
			$this->member_table->getMember($admin->inviter);
			array_push($members, $member);
		} else if ($admin->is_editor && !$admin->is_admin)
			$members = $this->member_table->getCMembers($admin->company_id);
		else
			$members = $this->member_table->fetchAll();
		$companies = $this->company_table->fetchAll();
		$form = new SendForm("sender", "sender");
		$request = $this->getRequest();

		if (!$request->isPost()) {
			return new ViewModel([
				'form' => $form,
				'id' => $admin->id,
				'u_key' => $admin->u_key,
				'members' => $members,
				'first' => $admin->first,
			]);
		}

		$post = array_merge_recursive(
			$request->getPost()->toArray(),
			$request->getFiles()->toArray()
		);

		$form->setData($post);
		if ($form->isValid()) {
			$data = $form->getData();
			$member = $this->member_table->getVMember(
			    $data['member_id']);
			$company = $this->company_table->getCompany(
			    $member->company_id);
			foreach($data['sender'] as $key=>$item) {
				$new_name = str_replace("tmp",
				    "downloads/" . $data['member_id'],
				    $item['tmp_name']);
				$fc = new FileCipher;
				$download = new Downloads();
				$download->moddate = time();
				$download->id_key = basename($new_name, ".pdf");
				$download->u_key = $member->u_key;
				$download->e_key = uniqid();
				$download->downloaded = 0;
				$download->company_id = 0;
				$fc->setKey($download->e_key);
				$fc->encrypt($item['tmp_name'], $new_name);
				unlink($item['tmp_name']);
				$this->downloads_table->
				    saveDownload($download);
			}
			$emailer = new Emailer($this->email_host);
			if ($invited)
				$company->name = "$admin->first $admin->last";
			$emailer->sendDownloadEmail($member->email,
			    $member->first, $member->last,
			    $this->hipaa['notice'], $company->name);
			if (!empty($post['isAjax'])) {
				return new JsonModel(array(
					'formData' => $data,
					'form' => $form,
					'id' => $admin->id,
					'u_key' => $admin->u_key,
					'members' => $members,
					'first' => $admin->first,
				));
			}
		}

		return new ViewModel([
			'form' => $form,
			'id' => $admin->id,
			'u_key' => $admin->u_key,
			'members' => $members,
			'first' => $admin->first,
		]);
	}
	public function membersAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		$members = $this->member_table->fetchAll();
		if (!$members)
			goto ret;

		if ($_GET) {
			if (array_key_exists('block', $_GET)) {
				$form = new BlockMemberForm();
				$action = "block";
			} else
				goto ret;
			$request = $this->getRequest();
			if (!$request->isPost()) {
				return new ViewModel([
					'members' => $members,
					'member' => $member,
					'action' => $action,
					'form' => $form,
					'completed' => false,
				]);
			}

			$form->setData($request->getPost());
			if (!$form->isValid()) {
				return new ViewModel([
					'members' => $members,
					'member' => $member,
					'action' => $action,
					'form' => $form,
					'completed' => false,
				]);
			}
			$data = $form->getData();
			$dm = new Member();
			$dm = $this->member_table->getMember($data['member_id']);
			$dm->blocked = 1;
			$this->member_table->saveMember($dm);
			return new ViewModel([
				'member' => $member,
				'action' => $action,
				'completed' => true,
			]);
		} else {
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
		}
ret:
		return $this->redirect()->toRoute('securitx',
			array(
				'action' => 'home',
				'id' => $member->u_key,
			)
		);
	}
	public function cdownloadAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		$company = $this->company_table->getCompany($member->company_id);
		$downloads = new Downloads;
		$downloads = $this->downloads_table->getCDownloads(
				$company->company_id
		);
		$da = array();
		foreach($downloads as $download)
			array_push($da, $download);
		return new ViewModel(array(
			'first' => $member->first,
			'u_key' => $member->u_key,
			'downloads' => $da,
		));
	}
	public function downloaderAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		if ($_GET) {
			$download =
			    $this->downloads_table->getDownload($_GET['id']);
			$download->downloaded = time();
			$this->downloads_table->saveDownload($download);

			if ($_GET['type'] == "p") {
				$file = sprintf('%s/data/downloads/%s/%s.pdf',
				    realpath(getcwd()), $member->u_key,
				    $download->id_key);

				$new_name = sprintf('%s/data/tmp/%s.pdf',
				    realpath(getcwd()), $download->id_key);
			} else if ($_GET['type'] == "c") {
				$company = $this->company_table->getCompany(
						$member->company_id
					);
				$file = sprintf('%s/data/uploads/%s/%s.pdf',
				    realpath(getcwd()), $company->short,
				    $download->id_key);

				$new_name = sprintf('%s/data/tmp/%s.pdf',
				    realpath(getcwd()), $download->id_key);
			}
			$fc = new FileCipher;
			$fc->setKey($download->e_key);
			$fc->decrypt($file, $new_name);

			$client = new Client();
			$response = new Response();
			$response->getHeaders()->addHeaders([
				'Content-Description' => 'File Transfer',
				'Content-Type' => 'application/pdf',
				'Content-Disposition' => 'attachment; filename="' . basename($new_name) . '"',
				'Expires' => '0',
				'Cache-Control' => 'must-revalidate',
				'Pragma' => 'public',
				'Content-Length' => filesize($new_name),
				'Content-Transfer-Encoding' => 'binary',
			]);
			$response->setContent(file_get_contents($new_name));

			unlink($new_name);
			return $response;
		}
		return new ViewModel();
	}
	public function downloadAction() {
		if (!$this->checkAnyAdmin())
			return $this->redirect()->toRoute('securitx');
		$member = $this->getMember();
		$downloads = new Downloads;
		$downloads =
		    $this->downloads_table->getDownloads($member->u_key);
		$da = array();
		foreach($downloads as $download)
			array_push($da, $download);
		return new ViewModel(array(
			'first' => $member->first,
			'u_key' => $member->u_key,
			'downloads' => $da,
		));
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
			$member = new Member();
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
			    $member->last, $url, $company->name,
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

		/* check member domain */
		$company =
		    $this->company_table->getCompany($member->company_id);
		$my_company = explode("@", $member->email);

		if ($company->domain == $my_company[1])
			$has_domain = 1;
		else
			$has_domain = 0;

		if ($_GET && $has_domain) {
			$admins = $this->member_table->getAllAdmins();
			if (array_key_exists('r_admin', $_GET)) {
				$type = "admin";
				if ($member->is_admin > 0 || $member->r_admin)
					goto skip;
				$member->r_admin = 1;
			} else if (array_key_exists('r_editor', $_GET)) {
				$type = "editor";
				if ($member->is_editor > 0 || $member->r_editor)
					goto skip;
				$member->r_editor = 1;
			} else
				goto skip;
			$emailer = new Emailer($this->email_host);
			foreach ($admins as $admin) {
				$url1 = $this->url()->fromRoute(
					'securitx',
					[
						'action' => 'authemp',
						'id' => $admin->u_key,
					],
					[
						'query' => [
							'member' => $member->u_key,
							'type' => $type,
							'action' => 'allow',
						],
					],
					[
						'force_canonical' => true,
					]
				);
				$url2 = $this->url()->fromRoute(
					'securitx',
					[
						'action' => 'authemp',
						'id' => $admin->u_key,
					],
					[
						'query' => [
							'member' => $member->u_key,
							'type' => $type,
							'action' => 'deny',
						],
					],
					[
						'force_canonical' => true,
					]
				);
				$url1 = "https://" . $_SERVER['SERVER_NAME'] .
				    $url1;
				$url2 = "https://" . $_SERVER['SERVER_NAME'] .
				    $url2;
				$emailer->sendAdminRequest($admin, $member,
				    $url1, $url2, $this->hipaa['notice'],
				    $type);
			}
		}
skip:
		$this->member_table->saveMember($member);

		$inviter = "";
		if ($member->inviter) {
			$admin =
			    $this->member_table->getMember($member->inviter);
			$inviter = "$admin->first $admin->last";
		}
		$has_downloads =
		    $this->downloads_table->getCount($member->u_key);
		$c_has_downloads = 0;
		if ($company->downloads)
			if ($this->downloads_table->getCCount($member->company_id))
				$c_has_downloads = 1;
		$count = $this->company_table->getCount();
		return new ViewModel([
			'member' => $member,
			'company' => $company->name,
			'c_downloads' => $c_has_downloads,
			'inviter' => $inviter,
			'has_downloads' => $has_downloads,
			'has_domain' => $has_domain,
			'count' => $count,
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
					office TEXT,
					email TEXT NOT NULL,
					v_key UUID,
					u_key UUID,
					verified INTEGER NOT NULL,
					moddate NUMERIC NOT NULL,
					company_id INTEGER NOT NULL,
					is_admin INTEGER,
					is_editor INTEGER,
					inviter INTEGER,
					r_admin INTEGER,
					r_editor INTEGER,
					ip_address TEXT,
					twofa INTEGER,
					twofa_moddate INTEGER,
					phone INTEGER,
					blocked INTEGER
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				CREATE TABLE companies (
					company_id INTEGER PRIMARY KEY AUTOINCREMENT,
					name TEXT NOT NULL,
					short TEXT NOT NULL,
					domain TEXT NOT NULL,
					phone TEXT NOT NULL,
					downloads INTEGER
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				CREATE TABLE downloads (
					downloads_id INTEGER PRIMARY KEY AUTOINCREMENT,
					moddate NUMERIC NOT NULL,
					id_key UUID NOT NULL,
					u_key UUID NOT NULL,
					e_key UUID NOT NULL,
					company_id INTEGER NOT NULL,
					downloaded INTEGER NOT NULL
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				CREATE TABLE blockeddomains (
					domain_id INTEGER PRIMARY KEY AUTOINCREMENT,
					domain TEXT NOT NULL
				)
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				INSERT INTO blockeddomains
					("domain")
					values ("mailinator.com")
			', Adapter::QUERY_MODE_EXECUTE);
			$adapter->query('
				INSERT INTO blockeddomains
					("domain")
					values ("sharklazers.com")
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
			$folder = realpath(getcwd()) . "/data/tmp/";
			if (!is_dir($folder))
				mkdir($folder, 0755, true);
			$co_added = 1;
		}

		/* setup admin member */
		$member = new Member();
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

			$member = new Member();
			$member->setFilter(true);
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
			$member->moddate = time();
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
			    $member->first, $member->last, $url,
			    $this->hipaa['notice']);

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
	public function twofaAction() {
		$member = new Member();
		$id = $this->params()->fromRoute('id');

		try {
			$member = $this->member_table->getVMember($id);
		} catch (\InvalidArgumentException $ex) {
			return $this->redirect()->toRoute('securitx');
		}

		$form = new TwofaForm();
		if ((time() - $member->twofa_moddate) > 60 * 10)
	       		$expired = true;
		else
			$expired = false;
		$request = $this->getRequest();
		if (!$request->isPost()) {
			return new ViewModel([
				'member' => $member,
				'valid' => '',
				'expired' => $expired,
				'form' =>  $form,
			]);
		}
		$form->setData($request->getPost());
		if (!$form->isValid()) {
			return new ViewModel([
				'member' => $member,
				'valid' => '',
				'expired' => $expired,
				'form' =>  $form,
			]);
		}
		$data = $form->getData();
		if (!$data['code']) {
			return new ViewModel([
				'member' => $member,
				'valid' => 'A code is required',
				'expired' => $expired,
				'form' =>  $form,
			]);
		}
		if($data['code'] != $member->twofa) {
			return new ViewModel([
				'member' => $member,
				'valid' => 'Incorrect code',
				'expired' => $expired,
				'form' =>  $form,
			]);
		} else {
			$member->moddate = time();
			$member->twofa = '';
			$member->twofa_moddate = '';
			$this->member_table->saveMember($member);
			return $this->redirect()->toRoute('securitx',
				array(
					'action' => 'home',
					'id' => $member->u_key,
				)
			);
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

		$member = new Member();
		$member->setFilter(true);
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
		$member->moddate = time();
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

		$domains = new BlockedDomains();
		$domains = $this->blockeddomains_table->fetchAll();
		$parts = explode('@', $member->email);
		foreach($domains as $domain) {
			if ($domain->domain == $parts[1]) {
				return new ViewModel([
					'exists' => false,
					'form' => $form,
					'companies' => $companies,
					'registered' => false,
					'valid' => 'Email addresses from this domain are prohibited',
				]);
			}
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
		    $member->last, $url, $this->hipaa['notice']);

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
		$member = new Member();

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
		    $member->last, $url, $company->name,
		    $this->hipaa['notice']);

		$this->member_table->saveMember($member);
		$folder = realpath(getcwd()) . "/data/downloads/" .
			    $member->u_key;
		if (!is_dir($folder))
			mkdir($folder, 0755, true);

		if ($member->inviter) {
			$inviter = new Member();
			try {
				$inviter =
				    $this->member_table->getMember(
					$member->inviter
				    );
			} catch (\InvalidArgumentException $ex) {
				return $this->redirect()->toRoute('securitx');
			}
			$emailer->sendInviterEmail($inviter->email,
			    $inviter->first, $inviter->last, $member->first,
			    $member->last, $this->hipaa['notice']);
		}

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
				'u_key' => $member->u_key,
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
			$company = $this->company_table->getCompany(
				$member->company_id);
			foreach($data[$company->short] as $key=>$item) {
				$new_name = str_replace("tmp",
				    "uploads/$company->short",
				    $item['tmp_name']);
				echo $new_name;
				$fc = new FileCipher;
				$download = new Downloads();
				$download->moddate = time();
				$download->id_key = basename($new_name, ".pdf");
				$download->u_key = $member->u_key;
				$download->e_key = uniqid();
				$download->downloaded = 0;
				$download->company_id = $company->company_id;
				$fc->setKey($download->e_key);
				$fc->encrypt($item['tmp_name'], $new_name);
				unlink($item['tmp_name']);
				$this->downloads_table->
				    saveDownload($download);
			}
			if (!empty($post['isAjax'])) {
				return new JsonModel(array(
					'formData' => $data,
					'form' => $form,
					'id' => $member->id,
					'u_key' => $member->u_key,
					'first' => $member->first,
					'company' => $company->name,
					'short' => $company->short,
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
