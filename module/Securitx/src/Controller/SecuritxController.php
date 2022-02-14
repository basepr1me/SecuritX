<?php
namespace Securitx\Controller;

use Securitx\Model\MemberTable;
use Securitx\Model\Member;
use Securitx\Model\CompanyTable;
use Securitx\Model\Emailer;

use Securitx\Form\MemberForm;
use Securitx\Form\UploaderForm;

use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;

use InvalidArgumentException;

class SecuritxController extends AbstractActionController {
	private $company_table, $member_table;

	public function __construct(MemberTable $member_table,
	CompanyTable $company_table) {
		$this->member_table = $member_table;
		$this->company_table = $company_table;
	}

	public function indexAction() {
		return new ViewModel();
	}
	public function requestAction() {
		$form = new MemberForm();
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

		$emailer = new Emailer();
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
				'action' => 'upload',
				'id' => $member->u_key,
			],
			[
				'force_canonical' => true,
			]
		);

		$emailer = new Emailer();
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
