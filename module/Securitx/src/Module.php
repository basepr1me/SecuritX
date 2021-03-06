<?php
namespace Securitx;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface {
	public function getConfig() {
		return include __DIR__ . '/../config/module.config.php';
	}

	public function getServiceConfig() {
		return [
			'factories' => [
				Model\MemberTable::class => function($container) {
					$tableGateway = $container->get(Model\MemberTableGateway::class);
					return new Model\MemberTable($tableGateway);
				},
				Model\MemberTableGateway::class => function ($container) {
					$dbAdapter = $container->get(AdapterInterface::class);
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype(new Model\Member());
					return new TableGateway('members', $dbAdapter, null, $resultSetPrototype);
				},
				Model\CompanyTable::class => function($container) {
					$tableGateway = $container->get(Model\CompanyTableGateway::class);
					return new Model\CompanyTable($tableGateway);
				},
				Model\CompanyTableGateway::class => function ($container) {
					$dbAdapter = $container->get(AdapterInterface::class);
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype(new Model\Company());
					return new TableGateway('companies', $dbAdapter, null, $resultSetPrototype);
				},
				Model\DownloadsTable::class => function($container) {
					$tableGateway = $container->get(Model\DownloadsTableGateway::class);
					return new Model\DownloadsTable($tableGateway);
				},
				Model\DownloadsTableGateway::class => function ($container) {
					$dbAdapter = $container->get(AdapterInterface::class);
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype(new Model\Downloads());
					return new TableGateway('downloads', $dbAdapter, null, $resultSetPrototype);
				},
				Model\BlockedDomainsTable::class => function($container) {
					$tableGateway = $container->get(Model\BlockedDomainsTableGateway::class);
					return new Model\BlockedDomainsTable($tableGateway);
				},
				Model\BlockedDomainsTableGateway::class => function ($container) {
					$dbAdapter = $container->get(AdapterInterface::class);
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype(new Model\BlockedDomains());
					return new TableGateway('blockeddomains', $dbAdapter, null, $resultSetPrototype);
				},
			],
		];
	}

	public function getControllerConfig() {
		return [
			'factories' => [
				Controller\SecuritxController::class => function($container) {
					return new Controller\SecuritxController(
						$container->get(Model\MemberTable::class),
						$container->get(Model\CompanyTable::class),
						$container->get(Model\DownloadsTable::class),
						$container->get(Model\BlockedDomainsTable::class),
						$container->get('config')['email_host'],
						$container->get('config')['recaptcha'],
						$container->get('config')['hipaa'],
					);
				},
			],
		];
	}
}
