<?php

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/templator.php';
require_once __DIR__ . '/template_view.php';
require_once __DIR__ . '/component_manager.php';
require_once __DIR__ . '/shutdown_manager.php';
require_once __DIR__ . '/db_manager.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/session_manager.php';
require_once __DIR__ . '/user/user_presenter.php';
require_once __DIR__ . '/user/user_model.php';
require_once __DIR__ . '/events/events_presenter.php';
require_once __DIR__ . '/events/events_model.php';
require_once __DIR__ . '/flash_message_presenter.php';

$configFile = parse_ini_file(ENV_FILE, true);

$routes = [
	"/" => [
		"GET" => ["events.presenter", "showLanding"]
	],
	"/events" => [

		"GET" => ["events.presenter", "showAllEvents"]
	],
	"/events/new" => [
		"GET" => ["events.presenter", "showCreateEvent"],
		"POST" => ["events.presenter", "processCreateEvent"]
	],
	"/tos" => [
		"GET" => ["user.presenter", "showTOS"]
	],
	"/login" => [
		"GET" => ["user.presenter", "showLogin"],
		"POST" => ["user.presenter", "processLogin"]
	],
	"/register" => [
		"GET" => ["user.presenter", "showRegister"],
		"POST" => ["user.presenter", "processRegister"]
	],
	"/logout" => [
		"POST" => ["user.presenter", "processLogout"]
	],
	"/settings" => [
		"GET" => ["user.presenter", "showSettings"],
		"POST" => ["user.presenter", "processSettingsChange"]
	],
	"/settings/delete" => [
		"POST" => ["user.presenter", "processDelete"]
	],
];

$sharedTemplateArgsCallbacks = [
	["user.presenter", "getUserAuthArgs"],
	["flash_message_presenter", "getFlashMessageArgs"]
];

$componentManager = new ComponentManager([
	'shutdown_manager' => [ShutdownManager::class],
	'db_manager' => [DBManager::class, $configFile['DB']],
	'templator' => [Templator::class, TEMPLATES_DIR, COMPILED_TEMPLATES_DIR],
	'template_view' => [TemplateView::class, COMPILED_TEMPLATES_DIR],
	'router' => [Router::class, $routes, $sharedTemplateArgsCallbacks],
	'session_manager' => [SessionManager::class],
	'user.presenter' => [UserPresenter::class],
	'user.model' => [UserModel::class],
	'events.presenter' => [EventsPresenter::class],
	'events.model' => [EventsModel::class],
	'flash_message_presenter' => [FlashMessagePresenter::class],
]);

return $componentManager;
