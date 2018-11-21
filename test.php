<?php
$store_url = 'http://chops.local.com';
$endpoint = '/wc-auth/v1/authorize';
$params = [
    'app_name' => 'Chopshop',
    'scope' => 'read_write',
    'user_id' => 1,
    'return_url' => 'http://chops.local.com',
    'callback_url' => 'http://chops.local.com'
];
$query_string = http_build_query( $params );

echo $store_url . $endpoint . '?' . $query_string;
?>
