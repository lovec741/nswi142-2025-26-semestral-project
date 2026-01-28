<?php

$componentManager = require_once __DIR__ . '/../src/bootstrap.php';

/// DEBUG this is here only for debugging and should be called only in build during prod
$templator = $componentManager->getByClass(Templator::class);
$templator->compile();
///

$router = $componentManager->getByClass(Router::class);
$router->handleRequest();

