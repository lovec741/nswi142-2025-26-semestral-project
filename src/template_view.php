<?php

class TemplateView
{
    private string $compiledTemplatesFolder;
    private array $storedArgs = [];
	private array $injectBeforeRenderCallbacks;
	private ComponentManager $componentManager;

	public function __construct(ComponentManager $componentManager, string $compiledTemplatesFolder, array $injectBeforeRenderCallbacks)
	{
		$this->componentManager = $componentManager;
		$this->compiledTemplatesFolder = $compiledTemplatesFolder;
		$this->injectBeforeRenderCallbacks = $injectBeforeRenderCallbacks;
	}

	public function addStoredArgs(array $args) {
		foreach ($args as $arg => $value) {
			$this->storedArgs[$arg] = $value;
		}
	}

	/**
     * Renders a specific template by name and ends the script
     * @param string $templateName Name of template - for 'example.tpl.html' it should be 'example'
     */
	public function renderTemplate(string $templateName, array $args = []) {
		foreach ($this->injectBeforeRenderCallbacks as $injectBeforeRenderCallback) {
			[$presenterName, $methodName] = $injectBeforeRenderCallback;
			$presenter = $this->componentManager->getByName($presenterName);
			$this->addStoredArgs($presenter->$methodName());
		}

		$escapedTemplateName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $templateName);
		foreach ($this->storedArgs as $arg => $value) {
			${$arg} = $value;
		}
		foreach ($args as $arg => $value) {
			${$arg} = $value;
		}
		include($this->compiledTemplatesFolder.$escapedTemplateName.".php");
		$this->componentManager->getByName("shutdown_manager")->shutdown();
	}
}
