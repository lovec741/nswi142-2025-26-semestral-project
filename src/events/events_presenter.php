<?php

class EventsPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function showLandingPage($getArgs) {
		$this->componentManager->getByName("template_view")->renderTemplate("events");
	}
}