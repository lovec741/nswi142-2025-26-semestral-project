<?php

require_once __DIR__ . '/../src/templator.php';
require_once __DIR__ . '/../src/constants.php';

$templator = new Templator(TEMPLATES_DIR, COMPILED_TEMPLATES_DIR);
$templator->compile();

// print_r($_SERVER['PATH_INFO']);

$path = $_SERVER['PATH_INFO'];
$loggedIn = true;
$currentUsername = "Test Test";
include(COMPILED_TEMPLATES_DIR.$_SERVER['PATH_INFO'].'.php');