<?php

class UsersPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function getUserAuthArgs() {
		$session_manager = $this->componentManager->getByName("user_session_manager");
		$userId = $session_manager->getCurrentUserId();
		$args = [
			"loggedIn" => true,
			"currentUsername" => "XXX"
		];
		return $args;
	}

	public function showTOS() {
		$this->componentManager->getByName("template_view")->renderTemplate("tos");
	}
}