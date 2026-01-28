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

	public function createNewUser($email, $full_name, $password): bool {
		$result = $this->componentManager->getByName("db_manager")->dynamicQuery("
			INSERT INTO users
			(email, full_name, password) VALUES (?, ?, ?)
		", "sss", $email, $full_name, $password);
		return $result;
	}

	public function loadUserFromId(int $userId): bool {
		$result = $this->componentManager->getByName("db_manager")->dynamicQuery("
			SELECT email, full_name FROM users
			WHERE user_id = ?
		", "i", $userId);

		if ($result->num_rows === 0)
			return false;

		$row = mysqli_fetch_array($result);
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

	public function loadUserFromEmail(string $email): bool {
		$result = $this->componentManager->getByName("db_manager")->dynamicQuery("
			SELECT user_id, full_name FROM users
			WHERE email = ?
		", "s", $email);

		if ($result->num_rows === 0)
			return false;

		$row = mysqli_fetch_array($result);
		$this->userId = $row["user_id"];
		$this->email = $email;
		$this->fullName = $row["full_name"];
		return true;
	}

	public function getEmail(): ?string {
		return $this->email;
	}

	public function getFullName(): ?string {
		return $this->fullName;
	}

	public function getUserId(): ?int {
		return $this->userId;
	}
}