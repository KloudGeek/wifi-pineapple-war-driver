<?php 

$pineap_settings = authorized_get('/api/pineap/settings', null, 'Getting PineAP settings');
print_r ($pineap_settings);


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
