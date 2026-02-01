<?php

class FlashMessagePresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function getFlashMessageArgs() {
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		$flashMessages = $flashMessageModel->getFlashMessages();
		$args = [
			"flashMessages" => $flashMessages
		];
		return $args;
	}
}