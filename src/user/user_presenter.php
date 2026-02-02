<?php

class UserPresenter {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

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

		if (
			!filter_var($postArgs["email"], FILTER_VALIDATE_EMAIL)  || strlen($postArgs["email"]) > 255
			|| !isset($postArgs["password"]) || strlen($postArgs["password"]) > 255
		) {
			$flashMessageModel->addFlashMessage("Form invalid!", "error");
			header('Location: '.BASE_URL.'/login');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		if (!$userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$flashMessageModel->addFlashMessage("User doesn't exist!", "error");
			header('Location: '.BASE_URL.'/login');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$userModel->loginUserByEmail($postArgs["email"]);
		$flashMessageModel->addFlashMessage("Successfully logged in!", "success");
		header('Location: '.BASE_URL.'/');
	}

	public function showRegister() {
		$this->redirectIfLoggedIn();
		$this->componentManager->getByClass(TemplateView::class)->renderTemplate("register");
	}

	public function processRegister($postArgs) {
		$this->redirectIfLoggedIn();
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		if (
			!filter_var($postArgs["email"], FILTER_VALIDATE_EMAIL)  || strlen($postArgs["email"]) > 255
			|| isNullOrEmptyString($postArgs["fullName"]) || strlen($postArgs["fullName"]) > 255
			|| !isset($postArgs["password"]) || strlen($postArgs["password"]) > 255
			|| !isset($postArgs["tosAgree"])
		) {
			$flashMessageModel->addFlashMessage("Form invalid!", "error");
			header('Location: '.BASE_URL.'/register');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$userModel = $this->componentManager->getByClass(UserModel::class);
		if ($userModel->checkIfUserExistsByEmail($postArgs["email"])) {
			$flashMessageModel->addFlashMessage("You already have an account! Please login.", "warn");
			header('Location: '.BASE_URL.'/login');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}

		$userModel->createNewUser($postArgs["email"], $postArgs["fullName"], $postArgs["password"]);
		$userModel->loginUserByEmail($postArgs["email"]);
		$flashMessageModel->addFlashMessage("Account successfully registered!", "success");
		header('Location: '.BASE_URL.'/');
	}

	public function processLogout() {
		$userModel = $this->getModelAndCheckIfLoggedIn();
		$userModel->logoutUser();
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		$flashMessageModel->addFlashMessage("You have been logged out!", "success");
		header('Location: '.BASE_URL.'/');
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
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		if (
			isNullOrEmptyString($postArgs["fullName"]) || strlen($postArgs["fullName"]) > 255
			|| !isset($postArgs["password"]) || strlen($postArgs["password"]) > 255
		) {
			$flashMessageModel->addFlashMessage("Form invalid!", "error");
			header('Location: '.BASE_URL.'/settings');
			$this->componentManager->getByName("shutdown_manager")->shutdown();
		}
		$userModel->updateCurrentUser($postArgs["fullName"], $postArgs["password"]);
		$flashMessageModel->addFlashMessage("Settings changed!", "success");
		header('Location: '.BASE_URL.'/settings');
	}

	public function processDelete() {
		$userModel = $this->getModelAndCheckIfLoggedIn();
		$userModel->deleteCurrentUser();
		$userModel->logoutUser();
		$flashMessageModel = $this->componentManager->getByClass(FlashMessageModel::class);
		$flashMessageModel->addFlashMessage("Account deleted!", "success");
		header('Location: '.BASE_URL.'/');
	}
}