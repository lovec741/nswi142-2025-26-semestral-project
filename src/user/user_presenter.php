<?php

class UserPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function getUserAuthArgs() {
		$session_manager = $this->componentManager->getByName("session_manager");
		$userId = $session_manager->getCurrentUserId();
		$userModel = $this->componentManager->getByName("user.model");
		$userExists = $userId === null ? false : $userModel->loadUserFromId($userId);
		$args = [
			"loggedIn" => $userExists,
			"currentUsername" => $userModel->getFullName()
		];
		return $args;
	}

	public function showTOS() {
		$this->componentManager->getByName("template_view")->renderTemplate("tos");
	}

	public function showLogin() {
		$session_manager = $this->componentManager->getByName("session_manager");
		$userId = $session_manager->getCurrentUserId();
		if (isset($userId)) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Already logged in!", "warn");
			header('Location: /');
			return;
		}
		$this->componentManager->getByName("template_view")->renderTemplate("login");
	}

	public function processLogin($postArgs) {
		$session_manager = $this->componentManager->getByName("session_manager");
		$userId = $session_manager->getCurrentUserId();
		if (isset($userId)) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Already logged in!", "warn");
			header('Location: /');
			return;
		}
		$userModel = $this->componentManager->getByName("user.model");
		if (!$userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("User doesn't exist!", "error");
			header('Location: /login');
			return;
		}

		$userModel->loadUserFromEmail($postArgs["email"]);
		$session_manager->loginUser($userModel->getUserId());
		$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Successfully logged in!", "success");
		header('Location: /');
	}

	public function showRegister() {
		$session_manager = $this->componentManager->getByName("session_manager");
		$userId = $session_manager->getCurrentUserId();
		if (isset($userId)) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Already logged in!", "warn");
			header('Location: /');
			return;
		}
		$this->componentManager->getByName("template_view")->renderTemplate("register");
	}

	public function processRegister($postArgs) {
		$session_manager = $this->componentManager->getByName("session_manager");
		$userId = $session_manager->getCurrentUserId();
		if (isset($userId)) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Already logged in!", "warn");
			header('Location: /');
			return;
		}
		$userModel = $this->componentManager->getByName("user.model");
		if ($userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("You already have an account! Please login.", "warn");
			header('Location: /login');
			return;
		}

		$userModel->createNewUser($postArgs["email"], $postArgs["fullName"], null);
		$userModel->loadUserFromEmail($postArgs["email"]);
		$session_manager->loginUser($userModel->getUserId());
		$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Account successfully registered!", "success");
		header('Location: /');
	}

	public function processLogout() {
		$session_manager = $this->componentManager->getByName("session_manager");
		$userId = $session_manager->getCurrentUserId();
		if (!isset($userId)) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Not logged in!", "error");
			header('Location: /');
			return;
		}
		$session_manager->logoutUser();
		$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("You have been logged out!", "success");
		header('Location: /');
	}
}