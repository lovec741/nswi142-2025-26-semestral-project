<?php

require_once __DIR__ . '/../model.php';

class EventsModel implements Model {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function initTables() {
		$dbManager = $this->componentManager->getByName("db_manager");
		$dbManager->staticQuery("
			CREATE TABLE IF NOT EXISTS events (
				event_id INT AUTO_INCREMENT,
				owner_user_id INT NOT NULL,
				name VARCHAR(64) NOT NULL,
				description VARCHAR(1024) NOT NULL,
				start_date DATE NOT NULL,
				end_date DATE NOT NULL,
				hero_img_name VARCHAR(64) NOT NULL,
				PRIMARY KEY (event_id),
				FOREIGN KEY (owner_user_id) REFERENCES users(user_id)
					ON DELETE CASCADE
			);
		");

		$dbManager->staticQuery("
			CREATE TABLE IF NOT EXISTS event_workshops (
				workshop_id INT AUTO_INCREMENT,
				event_id INT NOT NULL,
				name VARCHAR(255) NOT NULL,
				PRIMARY KEY (workshop_id),
				FOREIGN KEY (event_id) REFERENCES events(event_id)
					ON DELETE CASCADE
			);
		");

		$dbManager->staticQuery("
			CREATE TABLE IF NOT EXISTS event_registrations (
				registration_id INT AUTO_INCREMENT,
				event_id INT NOT NULL,
				user_id INT NOT NULL,
				PRIMARY KEY (registration_id),
				FOREIGN KEY (user_id) REFERENCES users(user_id)
					ON DELETE CASCADE,
				FOREIGN KEY (event_id) REFERENCES events(event_id)
					ON DELETE CASCADE
			);
		");

		$dbManager->staticQuery("
			CREATE TABLE IF NOT EXISTS event_registration_workshops (
				registration_id INT NOT NULL,
				workshop_id INT NOT NULL,
				FOREIGN KEY (registration_id) REFERENCES event_registrations(registration_id)
					ON DELETE CASCADE,
				FOREIGN KEY (workshop_id) REFERENCES event_workshops(workshop_id)
					ON DELETE CASCADE
			);
		");
	}

	public function createNewEvent(int $ownerUserId, string $name, string $description, string $startDate, string $endDate, string $heroImageName, array $workshops): int {
		$dbManager = $this->componentManager->getByName("db_manager");
		$dbManager->dynamicQuery("
			INSERT INTO events
			(owner_user_id, name, description, start_date, end_date, hero_img_name) VALUES (?, ?, ?, ?, ?, ?)
		", "isssss", $ownerUserId, $name, $description, $startDate, $endDate, $heroImageName);
		$result = $dbManager->staticQuery("
			SELECT LAST_INSERT_ID();
		");
		if (!$result)
			return false;
		$row = mysqli_fetch_array($result);
		$eventId = $row[0];
		foreach ($workshops as $workshop) {
			$dbManager->dynamicQuery("
				INSERT INTO event_workshops
				(event_id, name) VALUES (?, ?)
			", "is", $eventId, $workshop);
		}
		return $eventId;
	}


}