<?php

const REQUEST_PARAMS_ERROR_CODE = 400;
const SERVER_ERROR_CODE = 500;
const NOT_FOUND_ERROR_CODE = 404;

const TEMPLATES_FOLDER = "templates";
const PARAMETERS_FOLDER = "parameters";

const HEADER_FILE = __DIR__."/".TEMPLATES_FOLDER."/_header.php";
const FOOTER_FILE = __DIR__."/".TEMPLATES_FOLDER."/_footer.php";

function endWithHttpCode(int $code) {
    http_response_code($code);
    die();
}

function renderTemplateWithParams(string $templatePath, array $parameters, array $queryParams) {
    foreach ($parameters as $paramKey => $validationInfo) {
        if (!isset($queryParams[$paramKey])) {
            endWithHttpCode(REQUEST_PARAMS_ERROR_CODE);
        }
        $value = $queryParams[$paramKey];
        if ($validationInfo == "int" && is_numeric($value)) {
            $value = (int)$value;
        }
        if (
            (gettype($value) == "string" && $validationInfo == "string")
            || (gettype($value) == "integer" && $validationInfo == "int")
            || (gettype($validationInfo) == "array" && in_array($value, $validationInfo))
        ) {
            ${$paramKey} = $value;
        } else {
            endWithHttpCode(REQUEST_PARAMS_ERROR_CODE);
        }
    }
    include HEADER_FILE;
    include $templatePath;
    include FOOTER_FILE;
}

function handleRequest($queryParams) {
    try {
        if (!isset($queryParams["page"])) {
            endWithHttpCode(REQUEST_PARAMS_ERROR_CODE);
        }

        $page = $queryParams["page"];
        if (!preg_match("/^[a-z]+(\/[a-z]+)*$/i", $page)) {
            endWithHttpCode(REQUEST_PARAMS_ERROR_CODE);
        }

        if (is_dir(TEMPLATES_FOLDER."/".$page)) {
            $relativePath = $page."/index.php";
        } else {
            $relativePath = $page.".php";
        }
        $templatePath = __DIR__."/".TEMPLATES_FOLDER."/".$relativePath;
        $parametersPath = __DIR__."/".PARAMETERS_FOLDER."/".$relativePath;
        if (!is_file($templatePath)) {
            endWithHttpCode(NOT_FOUND_ERROR_CODE);
        }

        if (is_file($parametersPath)) {
            $parameters = include $parametersPath;
        } else {
            $parameters = [];
        }

        renderTemplateWithParams($templatePath, $parameters, $queryParams);
    } catch (Exception $e) {
        endWithHttpCode(SERVER_ERROR_CODE);
    }
}

handleRequest($_GET);