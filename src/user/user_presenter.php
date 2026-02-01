<?php

class UserPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	// /**
	//  * @return ?UserModel UserModel if loaded succesfully else null
	//  */
	// public function loadUserFromSession(): ?UserModel {
	// 	$sessionManager = $this->componentManager->getByClass(SessionManager::class);
	// 	$userId = $sessionManager->getCurrentUserId();
	// 	$userExists = $userId === null ? false : $userModel->loadUserFromId($userId);
	// 	return $userExists ? $userModel : null;
	// }

	public function getUserAuthArgs() {
		$userModel = $this->componentManager->getByClass(UserModel::class);
		$args = [
			"isLoggedIn" => $userModel->isLoggedIn(),
			"currentUserId" => $userModel->getUserId(),
			"currentUsername" => $userModel->getFullName()
		];
		return $args;
	}

	public function showTOS() {
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("tos");
	}

	public function redirectIfLoggedIn(string $redirectTo='/') {
		$userModel = $this->componentManager->getByClass(UserModel::class);
		if ($userModel->isLoggedIn()) {
			$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Already logged in!", "warn");
			header('Location: '.$redirectTo);
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}
	}

	public function getModelAndCheckIfLoggedIn(): UserModel {
		$userModel = $this->componentManager->getByClass(UserModel::class);
		if (!$userModel->isLoggedIn()) {
			$this->componentManager->getByClass(TemplateView::class)->renderTemplate("403");
		}
		return $userModel;
	}

	public function showLogin() {
		$this->redirectIfLoggedIn();
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("login");
	}

	public function processLogin($postArgs) {
		$this->redirectIfLoggedIn();
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		if (!$userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$flashMessageModel->addFlashMessage("User doesn't exist!", "error");
			header('Location: /login');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}
		// TODO validate form

		$userModel->loginUserByEmail($postArgs["email"]);
		$flashMessageModel->addFlashMessage("Successfully logged in!", "success");
		header('Location: /');
	}

	public function showRegister() {
		$this->redirectIfLoggedIn();
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("register");
	}

	public function processRegister($postArgs) {
		$this->redirectIfLoggedIn();
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		$userModel = $this->componentManager->getByClass(UserModel::class);
		if ($userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$flashMessageModel->addFlashMessage("You already have an account! Please login.", "warn");
			header('Location: /login');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}
		// TODO validate form

		$userModel->createNewUser($postArgs["email"], $postArgs["fullName"], $postArgs["password"]);
		$userModel->loginUserByEmail($postArgs["email"]);
		$flashMessageModel->addFlashMessage("Account successfully registered!", "success");
		header('Location: /');
	}

	public function processLogout() {
		$userModel = $this->getModelAndCheckIfLoggedIn();
		$userModel->logoutUser();
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		$flashMessageModel->addFlashMessage("You have been logged out!", "success");
		header('Location: /');
	}

	public function showSettings() {
		$userModel = $this->getModelAndCheckIfLoggedIn();
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate(
			"settings",
			[
				"email" => $userModel->getEmail()
			]
		);
	}

	public function processSettingsChange($postArgs) {
		$userModel = $this->getModelAndCheckIfLoggedIn();
		$userModel->updateCurrentUser($postArgs["fullName"], $postArgs["password"]);
		$this->componentManager->getByClass(FlashMessageModel::class)->addFlashMessage("Settings changed!", "success");
		header('Location: /settings');
	}

	public function processDelete() {
		$userModel = $this->getModelAndCheckIfLoggedIn();
		$userModel->deleteCurrentUser();
		$userModel->logoutUser();
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		$flashMessageModel->addFlashMessage("Account deleted!", "success");
		header('Location: /');
	}
}