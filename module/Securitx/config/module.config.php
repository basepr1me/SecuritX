<?php
namespace Securitx;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
	'router' => [
		'routes' => [
			'home' => [
				'type'=> Literal::class,
				'options' => [
					'route' => '/',
					'defaults' => [
						'controller'	=> Controller\SecuritxController::class,
						'action'	=> 'index',
					],
				],
			],
			'securitx' => [
				'type'=> Segment::class,
				'options' => [
					'route' => '/securitx[/:action[/:id]]',
					'constraints' => [
						'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
						'id' => '[a-zA-Z0-9]*',
					],
					'defaults' => [
						'controller'	=> Controller\SecuritxController::class,
						'action'	=> 'index',
					],
				],
			],
		],
	],
	'view_manager' => [
		'display_not_found_reason'	=> true,
		'display_exceptions'		=> true,
		'doctype'			=> 'HTML5',
		'not_found_template'		=> 'error/404',
		'exception_template'		=> 'error/index',
		'template_map' => [
			'layout/layout'		=> __DIR__ . '/../view/layout/layout.phtml',
			'error/404'		=> __DIR__ . '/../view/error/404.phtml',
			'error/index'		=> __DIR__ . '/../view/error/index.phtml',

            		'securitx/securitx/index'	=> __DIR__ . '/../view/securitx/index/index.phtml',
            		'securitx/securitx/request'	=> __DIR__ . '/../view/securitx/request/request.phtml',
            		'securitx/securitx/verify'	=> __DIR__ . '/../view/securitx/verify/verify.phtml',
            		'securitx/securitx/upload'	=> __DIR__ . '/../view/securitx/upload/upload.phtml',
            		'securitx/securitx/home'	=> __DIR__ . '/../view/securitx/home/home.phtml',
            		'securitx/securitx/download'	=> __DIR__ . '/../view/securitx/download/download.phtml',
            		'securitx/securitx/cdownload'	=> __DIR__ . '/../view/securitx/cdownload/cdownload.phtml',
            		'securitx/securitx/send'	=> __DIR__ . '/../view/securitx/send/send.phtml',
            		'securitx/securitx/invite'	=> __DIR__ . '/../view/securitx/invite/invite.phtml',
            		'securitx/securitx/companies'	=> __DIR__ . '/../view/securitx/companies/companies.phtml',
            		'securitx/securitx/user'	=> __DIR__ . '/../view/securitx/user/user.phtml',
            		'securitx/securitx/twofa'	=> __DIR__ . '/../view/securitx/twofa/twofa.phtml',
            		'securitx/securitx/forgot'	=> __DIR__ . '/../view/securitx/forgot/forgot.phtml',
		],
		'template_path_stack' => [
			__DIR__ . '/../view/securitx',
		],
	],
];
