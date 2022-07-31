<?php

	$url="http://10.10.1.66:1471/api/login";
	$data=['username'=>'root','password'=>''];
	$data = json_encode($data);

	$ch = curl_init();
	$curlConfig = array(
		CURLOPT_URL		=> $url,
		CURLOPT_HTTPHEADER	=> array('Content-Type: application/json'),
		CURLOPT_POST		=> true,
		CURLOPT_RETURNTRANSFER 	=> true,
		CURLOPT_POSTFIELDS	=> $data
	);
	curl_setopt_array($ch, $curlConfig);
	$result = curl_exec($ch);
	curl_close($ch);
	echo $result;

?>
