<?php

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/utils.php';
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
require_once __DIR__ . '/flash_message/flash_message_presenter.php';
require_once __DIR__ . '/flash_message/flash_message_model.php';

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
	"/events/{}" => [
		"GET" => ["events.presenter", "showEventDetails"],
	],
	"/events/{}/edit" => [
		"GET" => ["events.presenter", "showEditEvent"],
		"POST" => ["events.presenter", "processEditEvent"]
	],
	"/events/{}/delete" => [
		"POST" => ["events.presenter", "processDeleteEvent"]
	],
	"/events/{}/register" => [
		"GET" => ["events.presenter", "showEventRegistration"],
		"POST" => ["events.presenter", "processEventRegistration"]
	],
	"/events/{}/register/edit" => [
		"GET" => ["events.presenter", "showEventEditRegistration"],
		"POST" => ["events.presenter", "processEventEditRegistration"]
	],
	"/events/{}/register/cancel" => [
		"POST" => ["events.presenter", "processEventCancelRegistration"]
	],
	"/events/mine" => [
		"GET" => ["events.presenter", "showMyEvents"],
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

$injectBeforeRenderCallbacks = [
	["user.presenter", "getUserAuthArgs"],
	["flash_message.presenter", "getFlashMessageArgs"]
];

$componentManager = new ComponentManager([
	'shutdown_manager' => [ShutdownManager::class],
	'db_manager' => [DBManager::class, $configFile['DB']],
	'templator' => [Templator::class, TEMPLATES_DIR, COMPILED_TEMPLATES_DIR],
	'template_view' => [TemplateView::class, COMPILED_TEMPLATES_DIR, $injectBeforeRenderCallbacks],
	'router' => [Router::class, $routes],
	'session_manager' => [SessionManager::class],
	'user.presenter' => [UserPresenter::class],
	'user.model' => [UserModel::class],
	'events.presenter' => [EventsPresenter::class],
	'events.model' => [EventsModel::class],
	'flash_message.presenter' => [FlashMessagePresenter::class],
	'flash_message.model' => [FlashMessageModel::class],
]);

return $componentManager;
