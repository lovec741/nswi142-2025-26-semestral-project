<?php

$componentManager = require_once __DIR__ . '/../src/bootstrap.php';

if (DEBUG_MODE) {
    $templator = $componentManager->getByClass(Templator::class);
    $templator->compile();
}

$router = $componentManager->getByClass(Router::class);
$router->handleRequest();

