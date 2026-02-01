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

	public function processCreateEvent($postArgs, $_, $files) {
		$userPresenter = $this->componentManager->getByClass(UserPresenter::class);
		$userModel = $userPresenter->getModelAndCheckIfLoggedIn();

		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		// TODO validate form
		$eventId = $eventsModel->createEvent($userModel->getUserId(), $postArgs['name'], $postArgs['description'], $postArgs['startDate'], $postArgs['endDate'], $files["heroImage"], $postArgs['workshop']);

		$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Creation was succesful!", "success");
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
		var_export($files);

		$eventId = (int) $eventId;
		$event = $this->getEventDetailWithCheck($eventId);
		$this->checkUserLoggedInAndIsOwner($event);

		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$eventsModel->updateEvent($eventId, $postArgs['name'], $postArgs['description'], $postArgs['startDate'], $postArgs['endDate'], $files["heroImage"], $postArgs['updateWorkshop'], $postArgs['removeWorkshopId'] ?? [], $postArgs['addWorkshop'] ?? []);

		$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Changes were saved!", "success");
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

	public function showMyEvents() {
		$userPresenter = $this->componentManager->getByClass(UserPresenter::class);
		$userModel = $userPresenter->getModelAndCheckIfLoggedIn();
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$registeredEvents = $eventsModel->getAllEventsUserIsRegisteredFor($userModel->getUserId());
		$ownedEvents = $eventsModel->getAllEventsUserOwns($userModel->getUserId());
		$args = [
			"registeredEvents" => $registeredEvents,
			"ownedEvents" => $ownedEvents
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
		$this->getEventDetailWithCanRegisterCheck($eventId);
		// TODO validate form
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		$eventsModel->createEventRegistration($userModel->getUserId(), $eventId, $postArgs['workshop'] ?? []);
		$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Successfully registered for event!", "success");
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
		$this->getEventDetailWithCanRegisterCheck($eventId, true);
		// TODO validate form
		$eventsModel = $this->componentManager->getByClass(EventsModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		$eventsModel->updateEventRegistration($userModel->getUserId(), $eventId, $postArgs['workshop'] ?? []);
		$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Registration changed!", "success");
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