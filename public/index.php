<?php

try {

    $app = require __DIR__ . '/../bootstrap.php';

    $app->serve();

    $app->terminate(true);

} catch (Exception $e) {
    throw $e;
}
