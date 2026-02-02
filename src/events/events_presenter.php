<?php

class EventsPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function showLanding() {
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		$args = [
			"events" => $eventsModel->getAllEventsAndOwnerNames(3),
			"eventsCount" => $eventsModel->countEvents(),
			"eventRegistrationsCount" => $eventsModel->countEventRegistrations(),
			"usersCount" => $userModel->countUsers(),

		];
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("landing", $args);
	}

	public function showAllEvents() {
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$args = [
			"events" => $eventsModel->getAllEventsAndOwnerNames(),
		];
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("all_events", $args);
	}

	public function showCreateEvent() {
		$userPresenter = $this->componentManager->getByClass(UserPresenter::class);
		$userPresenter->getModelAndCheckIfLoggedIn();
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("create_event");
	}

	private function validateStartAndEndDates(?string $startDateStr, ?string $endDateStr): bool {
		$startDate = DateTime::createFromFormat('Y-m-d', $startDateStr);
		$endDate = DateTime::createFromFormat('Y-m-d', $endDateStr);
		if (!$startDate || $startDate->format('Y-m-d') !== $startDateStr
			|| !$endDate || $endDate->format('Y-m-d') !== $endDateStr) {
			return false;
		}
		return $startDate <= $endDate;
	}

	private function validateImageFileUpload(?array $fileData, bool $noFileOk = false): bool {
		if (empty($fileData)) {
			return false;
		}
		if ($noFileOk && $fileData['error'] == UPLOAD_ERR_NO_FILE) {
			return true;
		}

		if ($fileData['error'] != UPLOAD_ERR_OK) {
			return false;
		}
		$allowed = ['png', 'jpg', 'jpeg'];
		$ext = pathinfo(strtolower($fileData['name']), PATHINFO_EXTENSION);
		if (!in_array($ext, $allowed)) {
			return false;
		}
		return true;
	}

	private function validateWorkshopNames(?array $workshopNames, bool $missingOk = false): bool {
		if ($missingOk && !isset($workshopNames)) {
			return true;
		}
		if (!is_array($workshopNames)) {
			return false;
		}
		return !in_array(false, array_map(function ($x) {
			return !isNullOrEmptyString($x) && strlen($x) <= 255;
		}, $workshopNames));
	}

	private function validateWorkshopIds(?array $workshopIds, array $currentEventWorkshops, bool $missingOk = false): bool {
		if ($missingOk && !isset($workshopIds)) {
			return true;
		}
		if (!is_array($workshopIds)) {
			return false;
		}
		$validWorkshopIds = array_map(function ($workshop) {
			return $workshop["workshop_id"];
		}, $currentEventWorkshops);
		return !in_array(false, array_map(function ($workshopId) use ($validWorkshopIds) {
			return filter_var($workshopId, FILTER_VALIDATE_INT) && in_array((int)$workshopId, $validWorkshopIds, true);
		}, $workshopIds));
	}

	private function validateWorkshops(?array $workshops, array $currentEventWorkshops): bool {
		if (!is_array($workshops)) {
			return false;
		}
		$workshopIds = array_map(function ($workshop) {
			return $workshop["workshopId"];
		}, $workshops);
		$workshopNames = array_map(function ($workshop) {
			return $workshop["name"];
		}, $workshops);
		return $this->validateWorkshopNames($workshopNames) && $this->validateWorkshopIds($workshopIds, $currentEventWorkshops);
	}


	public function processCreateEvent($postArgs, $_, $files) {
		$userPresenter = $this->componentManager->getByClass(UserPresenter::class);
		$userModel = $userPresenter->getModelAndCheckIfLoggedIn();

		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		if (
			isNullOrEmptyString($postArgs["name"]) || strlen($postArgs["name"]) > 64
			|| isNullOrEmptyString($postArgs["description"]) || strlen($postArgs["description"]) > 1024
			|| !$this->validateStartAndEndDates($postArgs["startDate"], $postArgs["endDate"])
			|| !$this->validateImageFileUpload($files["heroImage"])
			|| !$this->validateWorkshopNames($postArgs["workshop"])
		) {
			$flashMessageModel->addFlashMessage("Form invalid!", "error");
			header('Location: /events/new');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$eventId = $eventsModel->createEvent($userModel->getUserId(), $postArgs['name'], $postArgs['description'], $postArgs['startDate'], $postArgs['endDate'], $files["heroImage"], $postArgs['workshop']);

		$flashMessageModel->addFlashMessage("Creation was succesful!", "success");
		header('Location: /events/'.$eventId);
	}

	private function getEventDetailWithCheck(int $eventId): array {
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$event = $eventsModel->getEventDetails($eventId);
		if (!isset($event["event_id"])) {
			$this->componentManager->getByClass(TemplateView::class)->renderTemplate("404");
		}
		return $event;
	}

	private function getEventDetailAndRegisteredWorkshopsWithCheck(int $userId, int $eventId): array {
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$event = $eventsModel->getEventDetailsAndRegisteredWorkshops($userId, $eventId);
		if (!isset($event["event_id"])) {
			$this->componentManager->getByClass(TemplateView::class)->renderTemplate("404");
		}
		return $event;
	}

	private function checkUserLoggedInAndIsOwner(array $event) {
		$userPresenter = $this->componentManager->getByClass(UserPresenter::class);
		$userModel = $userPresenter->getModelAndCheckIfLoggedIn();
		$isUserOwner = $event["owner_user_id"] === $userModel->getUserId();
		if (!$isUserOwner) {
			$this->componentManager->getByClass(TemplateView::class)->renderTemplate("403");
		}
	}

	public function showEditEvent(string $eventId) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCheck($eventId);
		$this->checkUserLoggedInAndIsOwner($event);

		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("edit_event", ["event" => $event]);
	}

	public function processEditEvent(string $eventId, $postArgs, $_, $files) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCheck($eventId);
		$this->checkUserLoggedInAndIsOwner($event);

		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		if (
			isNullOrEmptyString($postArgs["name"]) || strlen($postArgs["name"]) > 64
			|| isNullOrEmptyString($postArgs["description"]) || strlen($postArgs["description"]) > 1024
			|| !$this->validateStartAndEndDates($postArgs["startDate"], $postArgs["endDate"])
			|| !$this->validateImageFileUpload($files["heroImage"], true)
			|| !$this->validateWorkshopNames($postArgs["addWorkshop"], true)
			|| !$this->validateWorkshopIds($postArgs["removeWorkshopId"], $event['workshops'], true)
			|| !$this->validateWorkshops($postArgs["updateWorkshop"], $event['workshops'])
		) {
			$flashMessageModel->addFlashMessage("Form invalid!", "error");
			header('Location: /events/'.$eventId.'/edit');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$eventsModel->updateEvent($eventId, $postArgs['name'], $postArgs['description'], $postArgs['startDate'], $postArgs['endDate'], $files["heroImage"], $postArgs['updateWorkshop'], $postArgs['removeWorkshopId'] ?? [], $postArgs['addWorkshop'] ?? []);

		$flashMessageModel->addFlashMessage("Changes were saved!", "success");
		header('Location: /events/'.$eventId);
	}

	public function processDeleteEvent(string $eventId) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCheck($eventId);
		$this->checkUserLoggedInAndIsOwner($event);
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$eventsModel->deleteEvent($eventId);

		$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Event was deleted!", "success");
		header('Location: /events');

	}

	public function showEventDetails(string $eventId) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCheck($eventId);

		$args = ["event" => $event];
		$userModel = $this->componentManager->getByClass(UserModel::class);
		if ($userModel->isLoggedIn()) {
			$currentUserId = $userModel->getUserId();
			$eventsModel = $this->componentManager->getByClass(EventsModel::class);
			$args["isRegisteredToEvent"] = $eventsModel->isUserRegisteredToEvent($currentUserId, $eventId);
			$args["isEventOwner"] = $event["owner_user_id"] === $currentUserId;
		}
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("event_details", $args);
	}

	public function showMyEvents($getArgs) {
		$userPresenter = $this->componentManager->getByClass(UserPresenter::class);
		$userModel = $userPresenter->getModelAndCheckIfLoggedIn();
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$registeredEvents = $eventsModel->getAllEventsUserIsRegisteredFor($userModel->getUserId());
		$ownedEvents = $eventsModel->getAllEventsUserOwns($userModel->getUserId());
		$showOldEvents = $getArgs['old'] ?? false;
		if (!$showOldEvents) {
			$registeredEvents = array_filter($registeredEvents, function($x) {return !$x['old'];});
			$ownedEvents = array_filter($ownedEvents, function($x) {return !$x['old'];});
		}
		$args = [
			"registeredEvents" => $registeredEvents,
			"ownedEvents" => $ownedEvents,
			"showOldEvents" => $showOldEvents
		];
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("my_events", $args);
	}

	private function getEventDetailWithCanRegisterCheck(int $eventId, bool $fromEdit = false): array {
		$userPresenter = $this->componentManager->getByClass(UserPresenter::class);
		$userModel = $userPresenter->getModelAndCheckIfLoggedIn();
		if (!$fromEdit) {
			$event = $this->getEventDetailWithCheck($eventId);
		} else {
			$event = $this->getEventDetailAndRegisteredWorkshopsWithCheck($userModel->getUserId(), $eventId);
		}
		$isUserOwner = $event["owner_user_id"] === $userModel->getUserId();
		if ($isUserOwner) {
			$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Owner can't register for own event!", "error");
			$this->componentManager->getByClass(TemplateView::class)->renderTemplate("403");
		}
		if (!$fromEdit) {
			$eventsModel = $this->componentManager->getByClass(EventsModel::class);
			if ($eventsModel->isUserRegisteredToEvent($userModel->getUserId(), $eventId)) {
				$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Already registered for event!", "error");
				$this->componentManager->getByClass(TemplateView::class)->renderTemplate("403");
			}
		}
		return $event;
	}

	public function showEventRegistration(string $eventId) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCanRegisterCheck($eventId);
		$args = ["event" => $event];
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("event_register", $args);
	}

	public function processEventRegistration(string $eventId, $postArgs) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCanRegisterCheck($eventId);

		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		if (
			!$this->validateWorkshopIds($postArgs["workshop"], $event['workshops'], true)
		) {
			$flashMessageModel->addFlashMessage("Form invalid!", "error");
			header('Location: /events/'.$eventId.'/register');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		$eventsModel->createEventRegistration($userModel->getUserId(), $eventId, $postArgs['workshop'] ?? []);
		$flashMessageModel->addFlashMessage("Successfully registered for event!", "success");
		header('Location: /events/mine');
	}

	public function showEventEditRegistration(string $eventId) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCanRegisterCheck($eventId, true);
		$args = ["event" => $event];
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("event_edit_register", $args);
	}

	public function processEventEditRegistration(string $eventId, $postArgs) {
		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCanRegisterCheck($eventId, true);

		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		if (
			!$this->validateWorkshopIds($postArgs["workshop"], $event['workshops'], true)
		) {
			$flashMessageModel->addFlashMessage("Form invalid!", "error");
			header('Location: /events/'.$eventId.'/register/edit');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		$eventsModel->updateEventRegistration($userModel->getUserId(), $eventId, $postArgs['workshop'] ?? []);
		$flashMessageModel->addFlashMessage("Registration changed!", "success");
		header('Location: /events/mine');
	}

	public function processEventCancelRegistration(string $eventId) {
		$eventId = (int) $eventId;
		$this->getEventDetailWithCanRegisterCheck($eventId, true);
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		$eventsModel->deleteEventRegistration($userModel->getUserId(), $eventId);
		$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Registration canceled!", "success");
		header('Location: /events/mine');
	}
}