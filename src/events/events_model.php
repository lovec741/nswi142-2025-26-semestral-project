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

		$dbManager->staticQuery("
			CREATE TABLE IF NOT EXISTS event_banned_users (
				event_id INT NOT NULL,
				user_id INT NOT NULL,
				FOREIGN KEY (user_id) REFERENCES users(user_id)
					ON DELETE CASCADE,
				FOREIGN KEY (event_id) REFERENCES events(event_id)
					ON DELETE CASCADE
			);
		");
	}

	public function dropTables() {
		$dbManager = $this->componentManager->getByName("db_manager");
		$dbManager->staticQuery("SET FOREIGN_KEY_CHECKS = 0;");
		$dbManager->staticQuery("TRUNCATE event_banned_users;");
		$dbManager->staticQuery("TRUNCATE event_registration_workshops;");
		$dbManager->staticQuery("TRUNCATE event_registrations;");
		$dbManager->staticQuery("TRUNCATE event_workshops;");
		$dbManager->staticQuery("TRUNCATE events;");
	}

	/**
	 * @param array $fileData taken from $_FILES
	 *
	 * @return string|null null if file is not valid or move failed, else returns the name of the uploaded file
	 */
	private function handleFileUpload(array $fileData): ?string {
		$newFileName = getUUID();


		if (!move_uploaded_file(
			$fileData['tmp_name'],
			UPLOAD_DATA_DIR . $newFileName)) {
			return null;
		}

		return $newFileName;
	}

	private function deleteExistingHeroImageForEvent(int $eventId) {
		$dbManager = $this->componentManager->getByName("db_manager");
		$result = $dbManager->dynamicQuery("
			SELECT hero_img_name FROM events
				WHERE event_id = ?
		", "i", $eventId);
		$res = mysqli_fetch_assoc($result);
		if ($res['hero_img_name'] === DEMO_IMAGE_NAME) {
			return;
		}
		unlink(UPLOAD_DATA_DIR . $res['hero_img_name']);
	}

	public function createEvent(int $ownerUserId, string $name, string $description, string $startDate, string $endDate, ?array $heroImgFileData, array $workshops, bool $useDemoImage = false): int {
		if ($useDemoImage){
			$heroImageName = DEMO_IMAGE_NAME;
		} else {
			$heroImageName = $this->handleFileUpload($heroImgFileData);
			if ($heroImageName === null) {
				throw new Exception("file upload failed");
			}
		}

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

	public function createEventRegistration(int $userId, int $eventId, array $workshopIds): int {
		$dbManager = $this->componentManager->getByName("db_manager");
		$dbManager->dynamicQuery("
			INSERT INTO event_registrations
			(user_id, event_id) VALUES (?, ?)
		", "ii", $userId, $eventId);
		$result = $dbManager->staticQuery("
			SELECT LAST_INSERT_ID();
		");
		if (!$result)
			return false;
		$row = mysqli_fetch_array($result);
		$registrationId = $row[0];
		foreach ($workshopIds as $workshopId) {
			$dbManager->dynamicQuery("
				INSERT INTO event_registration_workshops
				(registration_id, workshop_id) VALUES (?, ?)
			", "ii", $registrationId, $workshopId);
		}
		return $registrationId;
	}

	public function updateEvent(int $eventId, string $name, string $description, string $startDate, string $endDate, ?array $heroImgFileData, array $updateWorkshops, array $removeWorkshopIds, array $newWorkshops) {
		$heroImageName = $this->handleFileUpload($heroImgFileData);

		$dbManager = $this->componentManager->getByName("db_manager");
		if ($heroImageName === null) { // dont update image
			$dbManager->dynamicQuery("
				UPDATE events
				SET name = ?, description = ?, start_date = ?, end_date = ?
				WHERE event_id = ?
			", "ssssi", $name, $description, $startDate, $endDate, $eventId);
		} else {
			$this->deleteExistingHeroImageForEvent($eventId);
			$dbManager->dynamicQuery("
				UPDATE events
				SET name = ?, description = ?, start_date = ?, end_date = ?, hero_img_name = ?
				WHERE event_id = ?
			", "sssssi", $name, $description, $startDate, $endDate, $heroImageName, $eventId);
		}

		foreach ($updateWorkshops as $updateWorkshop) {
			$dbManager->dynamicQuery("
				UPDATE event_workshops
				SET name = ?
				WHERE workshop_id = ?
			", "si", $updateWorkshop['name'], $updateWorkshop['workshopId']);
		}
		foreach ($removeWorkshopIds as $removeWorkshopId) {
			$dbManager->dynamicQuery("
				DELETE FROM event_workshops
				WHERE workshop_id = ?
			", "i", $removeWorkshopId);
		}
		foreach ($newWorkshops as $workshop) {
			$dbManager->dynamicQuery("
				INSERT INTO event_workshops
				(event_id, name) VALUES (?, ?)
			", "is", $eventId, $workshop);
		}
	}

	public function deleteEvent(int $eventId) {
		$this->deleteExistingHeroImageForEvent($eventId);
		$dbManager = $this->componentManager->getByName("db_manager");
		$dbManager->dynamicQuery("
			DELETE FROM events
			WHERE event_id = ?
		", "i", $eventId);
	}

	private function getRegistrationId(int $userId, int $eventId): int {
		$dbManager = $this->componentManager->getByName("db_manager");
		$result = $dbManager->dynamicQuery("
			SELECT registration_id FROM event_registrations
			WHERE user_id = ? AND event_id = ?
		", "ii", $userId, $eventId);
		$row = mysqli_fetch_array($result);
		return $row[0];
	}

	public function updateEventRegistration(int $userId, int $eventId, array $selectedWorkshopIds) {
		$dbManager = $this->componentManager->getByName("db_manager");
		$registrationId = $this->getRegistrationId($userId, $eventId);
		$result = $dbManager->dynamicQuery("
			SELECT workshop_id FROM event_registration_workshops
			WHERE registration_id = ?
		", "i", $registrationId);
		$rows = mysqli_fetch_all($result, MYSQLI_NUM);

		$previouslySelectedWorkshopIds = array_map(function ($x) {return $x[0];}, $rows);
		$newlySelectedWorkshop = array_diff($selectedWorkshopIds, $previouslySelectedWorkshopIds);
		$newlyUnselectedWorkshop = array_diff($previouslySelectedWorkshopIds, $selectedWorkshopIds);

		foreach ($newlyUnselectedWorkshop as $removeWorkshopId) {
			$dbManager->dynamicQuery("
				DELETE FROM event_registration_workshops
				WHERE registration_id = ? AND workshop_id = ?
			", "ii", $registrationId, $removeWorkshopId);
		}
		foreach ($newlySelectedWorkshop as $workshopId) {
			$dbManager->dynamicQuery("
				INSERT INTO event_registration_workshops
				(registration_id, workshop_id) VALUES (?, ?)
			", "ii", $registrationId, $workshopId);
		}
	}

	public function deleteEventRegistration(int $userId, int $eventId) {
		$dbManager = $this->componentManager->getByName("db_manager");
		$registrationId = $this->getRegistrationId($userId, $eventId);
		$dbManager->dynamicQuery("
			DELETE FROM event_registrations
			WHERE registration_id = ?
		", "i", $registrationId);
	}

	public function getAllEventsAndOwnerNames(?int $onlyNewestX = null): array {
		$dbManager = $this->componentManager->getByName("db_manager");
		if ($onlyNewestX === null) {
			$result = $dbManager->staticQuery("
				SELECT event_id, full_name as owner_full_name, name, start_date, end_date, end_date < CURDATE() as old FROM events
					INNER JOIN users ON owner_user_id = user_id
					ORDER BY start_date ASC
			");
		} else {
			$result = $dbManager->dynamicQuery("
				SELECT event_id, full_name as owner_full_name, name, start_date, end_date FROM events
					INNER JOIN users ON owner_user_id = user_id
					ORDER BY event_id DESC
					LIMIT ?
			", "i", $onlyNewestX);
		}
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		return $rows;
	}

	public function getEventDetails(int $eventId): array {
		$dbManager = $this->componentManager->getByName("db_manager");

		$result = $dbManager->dynamicQuery("
			SELECT event_id, owner_user_id, full_name as owner_full_name, name, description, start_date, end_date, hero_img_name FROM events
				INNER JOIN users ON owner_user_id = user_id
				WHERE event_id = ?
		", "i", $eventId);
		$eventDetails = mysqli_fetch_assoc($result);
		$result = $dbManager->dynamicQuery("
			SELECT workshop_id, name FROM event_workshops
				WHERE event_id = ?
		", "i", $eventId);
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		$eventDetails["workshops"] = $rows;
		return $eventDetails;
	}

	public function isUserRegisteredToEvent(int $userId, int $eventId): bool {
		$dbManager = $this->componentManager->getByName("db_manager");
		$result = $dbManager->dynamicQuery("
			SELECT 1 FROM event_registrations
				WHERE user_id = ? AND event_id = ?
		", "ii", $userId, $eventId);
		return $result->num_rows !== 0;
	}

	public function getUsersRegisteredToEvent(int $eventId): array {
		$dbManager = $this->componentManager->getByName("db_manager");
		$result = $dbManager->dynamicQuery("
			SELECT users.user_id, full_name FROM users
				INNER JOIN event_registrations ON users.user_id = event_registrations.user_id
				WHERE event_id = ?
		", "i", $eventId);
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		return $rows;
	}

	public function getEventDetailsAndRegisteredWorkshops(int $userId, int $eventId): array {
		$dbManager = $this->componentManager->getByName("db_manager");
		$eventDetails = $this->getEventDetails($eventId);
		$result = $dbManager->dynamicQuery("
			SELECT event_workshops.workshop_id FROM event_workshops
				INNER JOIN event_registration_workshops ON event_workshops.workshop_id = event_registration_workshops.workshop_id
				INNER JOIN event_registrations ON event_registration_workshops.registration_id = event_registrations.registration_id
				WHERE user_id = ? AND event_workshops.event_id = ?
		", "ii", $userId, $eventId);
		$rows = mysqli_fetch_all($result, MYSQLI_NUM);
		$registeredWorkshopIds = array_map(function ($x) {return $x[0];}, $rows);
		$eventDetails["registeredWorkshopIds"] = $registeredWorkshopIds;
		return $eventDetails;
	}

	public function getAllEventsUserIsRegisteredFor(int $userId): array {
		$dbManager = $this->componentManager->getByName("db_manager");
		$result = $dbManager->dynamicQuery("
			SELECT events.event_id, owner_user_id, full_name as owner_full_name, name, description, start_date, end_date, hero_img_name, end_date < CURDATE() as old FROM events
				INNER JOIN users ON owner_user_id = users.user_id
				INNER JOIN event_registrations ON events.event_id = event_registrations.event_id AND event_registrations.user_id = ?
				ORDER BY events.start_date ASC
		", "i", $userId);
		$eventsDetails = mysqli_fetch_all($result, MYSQLI_ASSOC);
		foreach ($eventsDetails as &$eventDetails) {
			$result = $dbManager->dynamicQuery("
				SELECT event_workshops.workshop_id, name FROM event_workshops
					INNER JOIN event_registration_workshops ON event_workshops.workshop_id = event_registration_workshops.workshop_id
					INNER JOIN event_registrations ON event_registration_workshops.registration_id = event_registrations.registration_id
					WHERE user_id = ? AND event_workshops.event_id = ?
			", "ii", $userId, $eventDetails["event_id"]);
			$workshops = mysqli_fetch_all($result, MYSQLI_ASSOC);
			$eventDetails["workshops"] = $workshops;
		}
		return $eventsDetails;
	}

	public function getAllEventsUserOwns(int $userId): array {
		$dbManager = $this->componentManager->getByName("db_manager");
		$result = $dbManager->dynamicQuery("
			SELECT event_id, owner_user_id, full_name as owner_full_name, name, description, start_date, end_date, hero_img_name, end_date < CURDATE() as old FROM events
				INNER JOIN users ON owner_user_id = user_id
				WHERE owner_user_id = ?
				ORDER BY events.start_date ASC
		", "i", $userId);
		$eventsDetails = mysqli_fetch_all($result, MYSQLI_ASSOC);
		foreach ($eventsDetails as &$eventDetails) {
			$result = $dbManager->dynamicQuery("
			SELECT workshop_id, name FROM event_workshops
					WHERE event_id = ?
			", "i", $eventDetails["event_id"]);
			$workshops = mysqli_fetch_all($result, MYSQLI_ASSOC);
			$eventDetails["workshops"] = $workshops;
		}
		return $eventsDetails;
	}

	public function countEvents(): int {
		$result = $this->componentManager->getByName("db_manager")->staticQuery("
			SELECT COUNT(*) FROM events
		");

		$row = mysqli_fetch_array($result, MYSQLI_NUM);
		return $row[0];
	}

	public function countEventRegistrations(): int {
		$result = $this->componentManager->getByName("db_manager")->staticQuery("
			SELECT COUNT(*) FROM event_registrations
		");

		$row = mysqli_fetch_array($result, MYSQLI_NUM);
		return $row[0];
	}

	public function banUserFromEvent(int $userId, int $eventId) {
		$dbManager = $this->componentManager->getByName("db_manager");
		$dbManager->dynamicQuery("
			INSERT INTO event_banned_users
			(user_id, event_id) VALUES (?, ?)
		", "ii", $userId, $eventId);
	}

	public function isUserBannedFromEvent(int $userId, int $eventId) {
		$dbManager = $this->componentManager->getByName("db_manager");
		$result = $dbManager->dynamicQuery("
			SELECT 1 FROM event_banned_users
			WHERE user_id = ? AND event_id = ?
		", "ii", $userId, $eventId);
		return $result->num_rows !== 0;
	}
}