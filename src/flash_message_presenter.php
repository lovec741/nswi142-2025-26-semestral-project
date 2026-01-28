<?php

class FlashMessagePresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function getFlashMessageArgs() {
		$session_manager = $this->componentManager->getByName("session_manager");
		$flashMessages = $session_manager->getFlashMessages();
		$args = [
			"flashMessages" => $flashMessages
		];
		return $args;
	}
}