<?php

class TemplateView
{
    private string $compiledTemplatesFolder;
    private array $storedArgs = [];

	public function __construct(ComponentManager $componentManager, string $compiledTemplatesFolder)
	{
		$this->compiledTemplatesFolder = $compiledTemplatesFolder;
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
		$escapedTemplateName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $templateName);
		foreach ($this->storedArgs as $arg => $value) {
			${$arg} = $value;
		}
		foreach ($args as $arg => $value) {
			${$arg} = $value;
		}
		include($this->compiledTemplatesFolder.$escapedTemplateName.".php");
		exit(0);
	}
}
