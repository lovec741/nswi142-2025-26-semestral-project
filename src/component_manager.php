<?php

class ComponentManager {

	private array $components = [];

	public function __construct(array $componentInitialisers) {
		$this->initialiseComponents($componentInitialisers);
	}

	private function initialiseComponents(array $componentInitialisers) {
		foreach ($componentInitialisers as $componentName => $componentInitialiser) {
			$componentClass = array_shift($componentInitialiser);
			$args = $componentInitialiser;
			$this->components[$componentName] = new $componentClass($this, ...$args);
		}
	}

	public function getByName($name) {
		return $this->components[$name];
	}

	/**
	 * @template T
	 * @param class-string<T> $class
	 * @return T|null
	 */
	public function getByClass($class) {
		foreach($this->components as $component) {
			if ($component instanceof $class) {
				return $component;
			}
		}
	}
}
