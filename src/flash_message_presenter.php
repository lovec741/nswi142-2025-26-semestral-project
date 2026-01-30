<?php

class FlashMessagePresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function getFlashMessageArgs() {
		$sessionManager = $this->componentManager->getByName("session_manager");
		$flashMessages = $sessionManager->getFlashMessages();
		$args = [
			"flashMessages" => $flashMessages
		];
		return $args;
	}
}