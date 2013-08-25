<?php
require_once 'vendor/autoload.php';

session_cache_limiter(false);
session_start();

$app = new App();
$app->run();