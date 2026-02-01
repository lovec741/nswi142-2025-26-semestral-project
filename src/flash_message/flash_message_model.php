<?php

require_once __DIR__ . '/../model.php';

class FlashMessageModel implements Model {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function initTables()
	{
	}

	public function dropTables()
	{
	}

	/**
	 * @param string $message
	 * @param string $type can be one of 'success', 'warn', 'error'
	 *
	 * @return void
	 */
	public function addFlashMessage($text, $type) {
		$sessionManager = $this->componentManager->getByName("session_manager");
		if (!$sessionManager->isset('FLASHED_MESSAGES')) {
			$sessionManager->set('FLASHED_MESSAGES', []);
		}
		$currentFlashedMessages = $sessionManager->get('FLASHED_MESSAGES');
		array_push($currentFlashedMessages, ["text" => $text, "type" => $type]);
		$sessionManager->set('FLASHED_MESSAGES', $currentFlashedMessages);
	}

	public function getFlashMessages(): array {
		$sessionManager = $this->componentManager->getByName("session_manager");
		if (!$sessionManager->isset('FLASHED_MESSAGES')) {
			return [];
		}
		$msgs = $sessionManager->get('FLASHED_MESSAGES');
		$sessionManager->unset('FLASHED_MESSAGES');
		return $msgs;
	}
}