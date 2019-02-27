<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Request-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Request-Method: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    return true;
}

try {

    $app = require __DIR__ . '/../bootstrap.php';
    $app->serve();

    $app->terminate(true);

} catch (Exception $e) {
    throw $e;
}
