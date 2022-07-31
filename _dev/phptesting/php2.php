<?php

$post = [
	'username' => 'root',
];

$ch = curl_init('http://10.10.1.66:1471/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_POST, true);

$response = curl_exec($ch);
curl_close($ch);
var_dump($response);

?>
