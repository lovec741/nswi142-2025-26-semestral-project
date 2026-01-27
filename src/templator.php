<?php

/**
 * Represents the template compiler.
 */
class Templator
{
    private string $templatesFolder;
    private string $compiledTemplatesFolder;
    private string $loadedTemplate;
    private array $openMarkers;
    private const MARKERS = ["if", "for", "foreach"];

	public function __construct(string $templatesFolder, string $compiledTemplatesFolder)
	{
		$this->templatesFolder = $templatesFolder;
		$this->compiledTemplatesFolder = $compiledTemplatesFolder;
	}

	public function compile() {
		$files = array_diff(scandir($this->templatesFolder), array('.', '..'));
		foreach ($files as $file) {
			$this->loadTemplate($this->templatesFolder.$file);
			$this->compileAndSave($this->compiledTemplatesFolder.$this->templateFilenameToCompiledFilename($file));
		}
	}

	private function validateIfFilenameIsTemplate(string $fileName) {
		$bothFileExt = substr($fileName, strrpos($fileName, '.', -(strlen($fileName)-(strrpos($fileName, '.'))+1)));
		if ($bothFileExt !== ".tpl.html") {
            throw new Exception("Template '$fileName' has invalid extension '$bothFileExt' only '.tpl.html' supported");
		}
	}

	private function templateFilenameToCompiledFilename(string $fileName) {
		$fileNameWithoutBothExt = substr($fileName, 0, strrpos($fileName, '.', -(strlen($fileName)-(strrpos($fileName, '.'))+1)));
		return $fileNameWithoutBothExt . ".php";
	}

    /**
     * Load a template file into memory.
     * @param string $fileName Path to the template file to be loaded.
     */
    private function loadTemplate(string $fileName)
    {
		$this->validateIfFilenameIsTemplate($fileName);
        $result = @file_get_contents($fileName);
        if ($result === false) {
            throw new Exception("Failed to open file '$fileName'!");
        } else {
            $this->loadedTemplate = $result;
        }
    }

    private function validateExpression(string $markerName, string $expr): string {
        if (empty($expr)) {
            throw new Exception("Expression or condition of marker '$markerName' cannot be empty");
        }
        // we only check for '{' character since any character after '}' would be considered outside of the expression and ignored
        if (str_contains($expr, "{")) {
            throw new Exception("Expression or condition of marker '$markerName' cannot contain the character '{'. Expression: `$expr`");
        }
        return $expr;
    }

    private function formatInsertExpressionMarker(string $expr): string {
        $expr = $this->validateExpression("=", $expr);
        return "<?= htmlspecialchars($expr) ?>";
    }

	private function formatIncludeExpressionMarker(string $expr): string {
        $expr = $this->validateExpression("include", $expr);
		$this->validateIfFilenameIsTemplate($expr);
		$compiledFileName = $this->compiledTemplatesFolder.$this->templateFilenameToCompiledFilename($expr);
        return "<?php include(\"$compiledFileName\") ?>";
    }

    private function formatStartMarker(string $markerName, string $expr): string {
        $expr = $this->validateExpression($markerName, $expr);
        array_push($this->openMarkers, $markerName);
        return "<?php $markerName ($expr) { ?>";
    }

    private function formatEndMarker(string $markerName): string {
        if (empty($this->openMarkers) || end($this->openMarkers) != $markerName) {
            throw new Exception("Closing marker '$markerName' doesn't have a matching opening marker!");
        } else {
            array_pop($this->openMarkers);
        }
        return "<?php } ?>";
    }

    /**
     * Compile the loaded template (transpill it into interleaved-PHP) and save the result in a file.
     * @param string $fileName Path where the result should be saved.
     */
    private function compileAndSave(string $fileName)
    {
        if (!isset($this->loadedTemplate)) {
            throw new Exception("No template is currently loaded!");
        }
        $this->openMarkers = [];
        $compiledTemplate = preg_replace_callback_array(
            [
                '/{= ([^}]*)}/' => function ($match) {
                    return $this->formatInsertExpressionMarker(trim($match[1]));
                },
				'/{include ([^}]*)}/' => function ($match) {
                    return $this->formatIncludeExpressionMarker(trim($match[1]));
                },
                '/(?:{([a-z]+) ([^}]*)})|(?:{\/([a-z]+)})/' => function ($match) {
                    if (isset($match[1]) && !empty($match[1])) {
                        if (!in_array($match[1], Templator::MARKERS)) {
                            return $match[0];
                        }
                        return $this->formatStartMarker($match[1], trim($match[2]));
                    } elseif (isset($match[3]) && !empty($match[3])) {
                        if (!in_array($match[3], Templator::MARKERS)) {
                            return $match[0];
                        }
                        return $this->formatEndMarker($match[3]);
                    } else {
                        return $match[0];
                    }
                },
            ],
            $this->loadedTemplate
        );
        if (!empty($this->openMarkers)) {
            $formattedArray = implode("', '", $this->openMarkers);
            throw new Exception("There are opening markers without matching closing markers: '$formattedArray'");
        }
        $result = @file_put_contents($fileName, data: $compiledTemplate);
        if ($result === false) {
            throw new Exception("Failed to write to file '$fileName'!");
        }
    }
}
