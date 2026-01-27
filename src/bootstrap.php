<?php

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/templator.php';
require_once __DIR__ . '/template_view.php';
require_once __DIR__ . '/component_manager.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/user_session_manager.php';
require_once __DIR__ . '/users/users_presenter.php';
require_once __DIR__ . '/events/events_presenter.php';

// load config
// setup component manager with all of the components (presenters, models and the router)
// start front controller

$routes = [
	"/" => [
		"GET" => ["events.presenter", "showLandingPage"]
	],
	"/tos" => [
		"GET" => ["users.presenter", "showTOS"]
	]
];

$beforeRequestCallbacks = [
	["users.presenter", "getUserAuthArgs"]
];

$componentManager = new ComponentManager([
	'templator' => [Templator::class, TEMPLATES_DIR, COMPILED_TEMPLATES_DIR],
	'template_view' => [TemplateView::class, COMPILED_TEMPLATES_DIR],
	'router' => [Router::class, $routes, $beforeRequestCallbacks],
	'user_session_manager' => [UserSessionManager::class],
	'users.presenter' => [UsersPresenter::class],
	'events.presenter' => [EventsPresenter::class],
]);

$templator = $componentManager->getByClass(Templator::class);
$templator->compile();
// var_dump($router);

$router = $componentManager->getByClass(Router::class);
$router->handleRequest();
// $customerList = $componentManager->getByName('customer-list.presenter');
// var_dump($customerList);
