<?php

$componentManager = require_once __DIR__ . '/../src/bootstrap.php';

$templator = $componentManager->getByClass(Templator::class);
$templator->compile();

$models = $componentManager->getAllByClass(Model::class);
foreach ($models as $model) {
	$model->initTables();
}