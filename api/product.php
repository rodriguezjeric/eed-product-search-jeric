<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/EedClient.php';
$config = require __DIR__ . '/../config.php';
$client = new EedClient($config);
echo json_encode($client->articleDetails($_GET['id'] ?? ''), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
