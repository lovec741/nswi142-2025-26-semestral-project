<?php

class SessionManager {

	public function __construct(ComponentManager $componentManager)
	{
		session_start();
	}

	public function loginUser(int $userId) {
		$_SESSION['USER_ID'] = $userId;
	}

	public function logoutUser() {
		unset($_SESSION['USER_ID']);
	}

	public function getCurrentUserId(): ?int {
		return $_SESSION['USER_ID'] ?? null;
	}

	/**
	 * @param string $message
	 * @param string $type can be one of 'success', 'warn', 'error'
	 *
	 * @return void
	 */
	public function addFlashMessage($text, $type) {
		if (!isset($_SESSION['FLASHED_MESSAGES'])) {
			$_SESSION['FLASHED_MESSAGES'] = [];
		}
		array_push($_SESSION['FLASHED_MESSAGES'], ["text" => $text, "type" => $type]);
	}

	public function getFlashMessages(): array {
		if (!isset($_SESSION['FLASHED_MESSAGES'])) {
			return [];
		}
		$msgs = $_SESSION['FLASHED_MESSAGES'];
		unset($_SESSION['FLASHED_MESSAGES']);
		return $msgs;
	}
}