<?php

#	$ch = curl_init('http://10.10.1.66:1471/api/login');
#	$fields = ['username' => 'root', 'password' => 'SophieMuffin921!'];
#	$options = [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $fields, CURLOPT_RETURNTRANSFER => true];
#	curl_setopt_array ($ch, $options);

	$ch = curl_init();
	$url = "http://10.10.1.66:1471/api/login/";
	$postData = array (
		'username' => 'root',
	);
	curl_setopt_array ($ch, array (
		CURLOPT_URL => $url,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $postData,
		CURLOPT_RETURNTRANSFER => true
		)
	);

	$data = curl_exec ($ch);
	curl_close ($ch);
	echo $data;

?>

