<?php

require_once __DIR__ . '/../model.php';

class UserModel implements Model {
	private ComponentManager $componentManager;
	private ?int $userId = null;
	private ?string $fullName = null;
	private ?string $email = null;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function initTables() {
		$this->componentManager->getByName("db_manager")->staticQuery("
			CREATE TABLE IF NOT EXISTS users (
				user_id INT AUTO_INCREMENT,
				email VARCHAR(255) NOT NULL UNIQUE,
				full_name VARCHAR(255) NOT NULL,
				password VARCHAR(255),
				PRIMARY KEY (user_id)
			);
		");
	}

	public function dropTables() {
		$dbManager = $this->componentManager->getByName("db_manager");
		$dbManager->staticQuery("SET FOREIGN_KEY_CHECKS = 0;");
		$dbManager->staticQuery("TRUNCATE users;");
	}

	public function createNewUser($email, $fullName, $password) {
		$this->componentManager->getByName("db_manager")->dynamicQuery("
			INSERT INTO users
			(email, full_name, password) VALUES (?, ?, ?)
		", "sss", $email, $fullName, $password);
		$result = $this->componentManager->getByName("db_manager")->staticQuery("
			SELECT LAST_INSERT_ID();
		");
		if (!$result)
			return false;
		$row = mysqli_fetch_array($result);
		$this->userId = $row[0];
		$this->email = $email;
		$this->fullName = $fullName;
	}

	public function updateCurrentUser($fullName, $password) {
		$this->componentManager->getByName("db_manager")->dynamicQuery("
			UPDATE users
			SET full_name = ?, password = ?
			WHERE user_id = ?
		", "ssi", $fullName, $password, $this->userId);
	}

	public function deleteCurrentUser() {
		$this->componentManager->getByName("db_manager")->dynamicQuery("
			DELETE FROM users
			WHERE user_id = ?
		", "i", $this->userId);
	}

	private function getUserIdFromSession(): ?int {
		$sessionManager = $this->componentManager->getByName("session_manager");
		return $sessionManager->get('USER_ID');
	}

	private function loadFromSession(): bool {
		$userId = $this->getUserIdFromSession();
		if ($userId === null) {
			return false;
		}
		$result = $this->componentManager->getByName("db_manager")->dynamicQuery("
			SELECT email, full_name FROM users
			WHERE user_id = ?
		", "i", $userId);

		if ($result->num_rows === 0)
			return false;

		$row = mysqli_fetch_assoc($result);
		$this->userId = $userId;
		$this->email = $row["email"];
		$this->fullName = $row["full_name"];
		return true;
	}

	public function checkIfUserExistsByEmail(string $email): bool {
		$result = $this->componentManager->getByName("db_manager")->dynamicQuery("
			SELECT * FROM users
			WHERE email = ?
		", "s", $email);
		return $result->num_rows !== 0;
	}

	public function loginUserByEmail(string $email): bool {
		$result = $this->componentManager->getByName("db_manager")->dynamicQuery("
			SELECT user_id, full_name FROM users
			WHERE email = ?
		", "s", $email);

		if ($result->num_rows === 0)
			return false;

		$row = mysqli_fetch_assoc($result);
		$this->userId = $row["user_id"];
		$this->email = $email;
		$this->fullName = $row["full_name"];
		$sessionManager = $this->componentManager->getByName("session_manager");
		$sessionManager->set('USER_ID', $row["user_id"]);
		return true;
	}

	public function countUsers(): int {
		$result = $this->componentManager->getByName("db_manager")->staticQuery("
			SELECT COUNT(*) FROM users
		");

		$row = mysqli_fetch_array($result, MYSQLI_NUM);
		return $row[0];
	}

	public function getEmail(): ?string {
		if ($this->userId !== null) {
			return $this->email;
		}
		$this->loadFromSession();
		return $this->email;
	}

	public function getFullName(): ?string {
		if ($this->userId !== null) {
			return $this->fullName;
		}
		$this->loadFromSession();
		return $this->fullName;
	}

	public function getUserId(): ?int {
		if ($this->userId !== null) {
			return $this->userId;
		}
		$this->loadFromSession();
		return $this->userId;
	}

	public function logoutUser() {
		unset($_SESSION['USER_ID']);
	}

	public function isLoggedIn(): bool {
		return $this->getUserId() !== null;
	}
}