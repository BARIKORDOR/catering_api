<?php

/** @var Bramus\Router\Router $router */

// Define routes here
$router->get('/test', App\Controllers\IndexController::class . '@test');
$router->get('/', App\Controllers\IndexController::class . '@test');
$router->get('/facility', App\Controllers\FacilityController::class . '@index');
$router->post('/facility', App\Controllers\FacilityController::class . '@create');

