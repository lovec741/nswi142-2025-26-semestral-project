<?php

class Router {
	private array $routes;
	private array $beforeRequestCallbacks;
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager, array $routes, array $beforeRequestCallbacks)
	{
		$this->componentManager = $componentManager;
		$this->routes = $routes;
		$this->beforeRequestCallbacks = $beforeRequestCallbacks;
	}

	public function handleRequest() {
		$templateView = $this->componentManager->getByName("template_view");
		$path = $_SERVER['PATH_INFO'] ?? "/";
		$requestMethod = $_SERVER['REQUEST_METHOD'];
		foreach ($this->beforeRequestCallbacks as $beforeRequestCallback) {
			[$presenterName, $methodName] = $beforeRequestCallback;
			$presenter = $this->componentManager->getByName($presenterName);
			$templateView->addStoredArgs($presenter->$methodName());
		}
		$templateView->addStoredArgs([
			"path" => $path
		]);
		if (!isset($this->routes[$path])) {
			$templateView->renderTemplate("404");
		}
		$routeMethodInfo = $this->routes[$path];
		if (!isset($routeMethodInfo[$requestMethod])) {
			$templateView->renderTemplate("405");
		}
		[$presenterName, $methodName] = $routeMethodInfo[$requestMethod];
		$presenter = $this->componentManager->getByName($presenterName);
		try {
			if ($requestMethod === "POST") {
				$presenter->$methodName($_POST, $_GET);
			} else {
				$presenter->$methodName($_GET);
			}
		} catch (Exception $e) {
			var_export($e); // DEBUG
			$templateView->renderTemplate("500");
		}
	}
}