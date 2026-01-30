<?php

class EventsPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function showLanding() {
		$eventsModel = $this->componentManager->getByName("events.model");
		$userModel = $this->componentManager->getByName("user.model");
		$args = [
			"events" => $eventsModel->getAllEventsAndOwnerNames(3),
			"eventsCount" => $eventsModel->countEvents(),
			"eventRegistrationsCount" => $eventsModel->countEventRegistrations(),
			"usersCount" => $userModel->countUsers(),

		];
		$this->componentManager->getByName("template_view")->renderTemplate("landing", $args);
	}

	public function showAllEvents() {
		$eventsModel = $this->componentManager->getByName("events.model");
		$args = [
			"events" => $eventsModel->getAllEventsAndOwnerNames(),
		];
		$this->componentManager->getByName("template_view")->renderTemplate("all_events", $args);
	}

	public function showCreateEvent() {
		$userPresenter = $this->componentManager->getByName("user.presenter");
		$userPresenter->loadModelAndRedirectIfNotLoggedIn();
		$this->componentManager->getByName("template_view")->renderTemplate("create_event");
	}
}