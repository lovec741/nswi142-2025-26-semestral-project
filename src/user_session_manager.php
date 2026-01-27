<?php

class UserSessionManager {

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
		return $_SESSION['USER_ID'];
	}
}