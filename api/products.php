<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/EedClient.php';
$config = require __DIR__ . '/../config.php';
$client = new EedClient($config);
$query = $_GET['q'] ?? $config['default_query'];
$limit = (int)($_GET['limit'] ?? $config['results_limit']);
echo json_encode($client->searchArticles($query, $limit), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
