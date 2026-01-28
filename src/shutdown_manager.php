<?php

class ShutdownManager {
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager)
	{
		$this->componentManager = $componentManager;
	}

	public function shutdown() {
		$this->componentManager->getByName("db_manager")->closeConnection();
		exit(0);
	}
}