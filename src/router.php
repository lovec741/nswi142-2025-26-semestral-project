<?php

class Router {
	private array $routes;
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager, array $routes)
	{
		$this->componentManager = $componentManager;
		$this->routes = $routes;
	}

	private function matchPath(string $requestPath, string $matchPath): ?array {
		$pattern = preg_replace('/{}/', '([^\/]*)', "/^".preg_replace('/\//', '\\/', $matchPath)."$/");
		$result = preg_match_all($pattern, $requestPath, $matches);
		if ($result === 0) {
			return null;
		}
		$pathArgs = array_map(function($x) {return $x[0];}, array_slice($matches, 1));
		return $pathArgs;
	}

	public function handleRequest() {
		$templateView = $this->componentManager->getByClass(TemplateView::class);
		$requestPath = $_SERVER['PATH_INFO'] ?? "/";
		$requestMethod = $_SERVER['REQUEST_METHOD'];

		$matchedPath = null;
		$pathArgs = null;
		// NOTE: this is not performant in the slightest, but in our small webapp it will work just fine
		foreach ($this->routes as $matchPath => $_) {
			$result = $this->matchPath($requestPath, $matchPath);
			if ($result !== null && ($pathArgs === null || count($result) < count($pathArgs))) { // we want the most specific match = with the least placeholders
				$matchedPath = $matchPath;
				$pathArgs = $result;
			}
		}
		if ($matchedPath === null) {
			$templateView->renderTemplate("404");
		}

		$templateView->addStoredArgs([
			"path" => $matchedPath
		]);
		$routeMethodInfo = $this->routes[$matchedPath];
		if (!isset($routeMethodInfo[$requestMethod])) {
			$templateView->renderTemplate("405");
		}
		[$presenterName, $methodName] = $routeMethodInfo[$requestMethod];
		$presenter = $this->componentManager->getByName($presenterName);
		$args = [...$pathArgs];
		try {
			if ($requestMethod === "POST") {
				array_push($args, $_POST, $_GET, $_FILES);
				$presenter->$methodName(...$args);
			} else {
				array_push($args, $_GET);
				$presenter->$methodName(...$args);
			}
		} catch (Exception $e) {
			var_export($e); // DEBUG
			$templateView->renderTemplate("500");
		}
		$this->componentManager->getByName("shutdown_manager")->shutdown();
	}
}