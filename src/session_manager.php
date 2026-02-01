<?php

class SessionManager {

	public function __construct(ComponentManager $componentManager)
	{
		session_start();
	}

	public function get(string $key) {
		return $_SESSION[$key] ?? null;
	}

	public function set(string $key, mixed $value) {
		$_SESSION[$key] = $value;
	}

	public function unset(string $key) {
		unset($_SESSION[$key]);
	}

	public function isset(string $key): bool {
		return isset($_SESSION[$key]);
	}
}