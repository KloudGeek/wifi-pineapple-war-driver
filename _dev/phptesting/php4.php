<?php

#
#	Auth
#
	echo "> Authentication\n";
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
	$result = json_decode ($result);
	$token = ($result->token);
	echo "	" . $token . "\n";
	$auth_bearer_string = "Authorization: Bearer " . $token;




#
#	Stop Scan
#
	echo "> Stop scan\n";
	$url="http://10.10.1.66:1471/api/recon/stop";
	$ch = curl_init();
	$curlConfig = array(
		CURLOPT_URL		=> $url,
		CURLOPT_HTTPHEADER	=> array('Content-Type: application/json', $auth_bearer_string),
		CURLOPT_POST		=> true,
		CURLOPT_RETURNTRANSFER 	=> true
	);
	curl_setopt_array($ch, $curlConfig);
	$result = curl_exec($ch);
	curl_close($ch);
	$result = json_decode ($result);
	print_r($result);




#
#	Start Scan
#
	echo "\nStart Scan\n";
	$url="http://10.10.1.66:1471/api/recon/start";
	$data=['live'=>true,'scan_time'=>0,'band'=>"2"];
	$data = json_encode($data);
	$ch = curl_init();
	$curlConfig = array(
		CURLOPT_URL		=> $url,
		CURLOPT_HTTPHEADER	=> array('Content-Type: application/json', $auth_bearer_string),
		CURLOPT_POST		=> true,
		CURLOPT_RETURNTRANSFER 	=> true,
		CURLOPT_POSTFIELDS	=> $data
	);
	curl_setopt_array($ch, $curlConfig);
	$result = curl_exec($ch);
	curl_close($ch);
	$result = json_decode ($result);
	print_r($result);

?>
