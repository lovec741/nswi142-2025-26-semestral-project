<?php

class DBManager {
	private mysqli|null $connection = null;
	private string $host;
	private string $username;
	private string $password;
	private string $dbName;
	private int $port;

	public function __construct(ComponentManager $componentManager, array $dbConfig)
	{
		$this->host = $dbConfig['HOST'];
		$this->username = $dbConfig['USERNAME'];
		$this->password = $dbConfig['PASSWORD'];
		$this->dbName = $dbConfig['DB_NAME'];
		$this->port = $dbConfig['PORT'];
	}

	private function ensureConnection() {
		if ($this->connection !== null)
			return;

		$result = mysqli_connect(
			$this->host,
			$this->username,
			$this->password,
			$this->dbName,
			$this->port
		);

		if ($result === false)
			throw new Exception("Failed to establish a database connection!");

		$this->connection = $result;
	}

	public function staticQuery(string $query) {
		$this->ensureConnection();
		return $this->connection->query($query);
	}

	public function dynamicQuery(string $query, string $bindParamTypes, ...$bindParams) {
		$this->ensureConnection();
		$stmt = $this->connection->stmt_init();
		$stmt->prepare($query);
		$stmt->bind_param($bindParamTypes, ...$bindParams);
		$stmt->execute();
		return $stmt->get_result();
	}

	public function closeConnection() {
		if ($this->connection === null)
			return;
		$this->connection->close();
		unset($this->connection);
	}
}