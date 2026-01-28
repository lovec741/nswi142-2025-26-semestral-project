<?php

class UserPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	/**
	 * @return ?UserModel UserModel if loaded succesfully else null
	 */
	private function loadUserFromSession(): ?UserModel {
		$sessionManager = $this->componentManager->getByClass(SessionManager::class);
		$userId = $sessionManager->getCurrentUserId();
		$userModel = $this->componentManager->getByName("user.model");
		$userExists = $userId === null ? false : $userModel->loadUserFromId($userId);
		return $userExists ? $userModel : null;
	}

	public function getUserAuthArgs() {
		$userModel = $this->loadUserFromSession();
		$loggedIn = $userModel !== null;
		$args = [
			"loggedIn" => $loggedIn,
			"currentUsername" => $loggedIn ? $userModel->getFullName() : null
		];
		return $args;
	}

	public function showTOS() {
		$this->componentManager->getByName("template_view")->renderTemplate("tos");
	}

	public function redirectIfLoggedIn(string $redirectTo='/') {
		$userModel = $this->loadUserFromSession();
		if ($userModel !== null) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Already logged in!", "warn");
			header('Location: '.$redirectTo);
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}
	}

	public function loadModelAndRedirectIfNotLoggedIn(string $redirectTo='/'): UserModel {
		$userModel = $this->loadUserFromSession();
		if ($userModel === null) {
			$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Not logged in!", "error");
			header('Location: '.$redirectTo);
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}
		return $userModel;
	}

	public function showLogin() {
		$this->redirectIfLoggedIn();
		$this->componentManager->getByName("template_view")->renderTemplate("login");
	}

	public function processLogin($postArgs) {
		$this->redirectIfLoggedIn();
		$sessionManager = $this->componentManager->getByClass(SessionManager::class);
		$userModel = $this->componentManager->getByName("user.model");
		if (!$userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$sessionManager->addFlashMessage("User doesn't exist!", "error");
			header('Location: /login');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$userModel->loadUserFromEmail($postArgs["email"]);
		$sessionManager->loginUser($userModel->getUserId());
		$sessionManager->addFlashMessage("Successfully logged in!", "success");
		header('Location: /');
	}

	public function showRegister() {
		$this->redirectIfLoggedIn();
		$this->componentManager->getByName("template_view")->renderTemplate("register");
	}

	public function processRegister($postArgs) {
		$this->redirectIfLoggedIn();
		$sessionManager = $this->componentManager->getByClass(SessionManager::class);
		$userModel = $this->componentManager->getByName("user.model");
		if ($userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$sessionManager->addFlashMessage("You already have an account! Please login.", "warn");
			header('Location: /login');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$userModel->createNewUserAndLoad($postArgs["email"], $postArgs["fullName"], $postArgs["password"]);
		$sessionManager->loginUser($userModel->getUserId());
		$sessionManager->addFlashMessage("Account successfully registered!", "success");
		header('Location: /');
	}

	public function processLogout() {
		$this->loadModelAndRedirectIfNotLoggedIn();
		$sessionManager = $this->componentManager->getByClass(SessionManager::class);
		$sessionManager->logoutUser();
		$sessionManager->addFlashMessage("You have been logged out!", "success");
		header('Location: /');
	}

	public function showSettings() {
		$userModel = $this->loadModelAndRedirectIfNotLoggedIn();
		$this->componentManager->getByName("template_view")->renderTemplate(
			"settings",
			[
				"email" =>$userModel->getEmail()
			]
		);
	}

	public function processSettingsChange($postArgs) {
		$userModel = $this->loadModelAndRedirectIfNotLoggedIn();
		$userModel->updateCurrentUser($postArgs["fullName"], $postArgs["password"]);
		$this->componentManager->getByClass(SessionManager::class)->addFlashMessage("Settings changed!", "success");
		header('Location: /settings');
	}

	public function processDelete() {
		$userModel = $this->loadModelAndRedirectIfNotLoggedIn();
		$userModel->deleteCurrentUser();
		$sessionManager = $this->componentManager->getByClass(SessionManager::class);
		$sessionManager->logoutUser();
		$sessionManager->addFlashMessage("Account deleted!", "success");
		header('Location: /');
	}
}