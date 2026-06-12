<?php
/**
 * Product search configuration.
 * Replace EED_ID with your assigned EED identifier before production use.
 */
return [
    'eed_base_url' => 'https://shop.euras.com/eed.php',
    'eed_id' => getenv('EED_ID') ?: 'AUDs4BRTdG2KJMGkv9U3hcQZ8NUxLdZy',
    'shop_url' => getenv('SHOP_URL') ?: 'http://localhost/eed-product-search/',
    'currency' => '€',
    'default_query' => 'REMOTE',
    'results_limit' => 18,
    'request_timeout' => 30,
    'use_mock_on_api_failure' => true,
    'mock_only' => filter_var(getenv('MOCK_ONLY') ?: 'false', FILTER_VALIDATE_BOOLEAN),
];
