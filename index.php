<?php

require 'Slim/Slim.php';
require 'plugins/NotORM.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'crossfit';
$dbmethod = 'mysql:dbname=';

$dsn = $dbmethod . $dbname;
$pdo = new PDO($dsn, $dbuser, $dbpass);
$db = new NotORM($pdo);

$app->hook('slim.before.dispatch', function () use ($app) {


    $headers = request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    if (array_key_exists("X-API-KEY", $headers)) {
       $api_key = $headers['X-API-KEY'];
    }
    else{
    $api_key = null;
    }
 
 
    
  //  $api_key = $headers['X-API-KEY'];
    // this could be a MYSQL query that parses an API Key table, for example
    if ($api_key == '612e648bf9594adb50844cad6895f2cf') {
        $authorized = true;
    } else if ($api_key == NULL) {
        $response["error"] = true;
        $response["message"] = '{"error":{"text": "api key not sent" }}';
        $app->response->headers['X-Authenticated'] = 'False';
        $authorized = false;
        $app->halt(401, $response['message']);
    } else {
        $response["error"] = true;
        $response["message"] = '{"error":{"text": "api key invalid" }}';
        $app->response->headers['X-Authenticated'] = 'False';
        $authorized = false;
    }

    if (!$authorized) { //key is false
        // dont return 403 if you request the home page
        $req = $_SERVER['REQUEST_URI'];
        if ($req != "/") {
            $app->halt('403', $response['message']); // or redirect, or other something
        }
    }
});

function request_headers() {
    $arh = array();
    $rx_http = '/\AHTTP_/';
    foreach ($_SERVER as $key => $val) {
        if (preg_match($rx_http, $key)) {
            $arh_key = preg_replace($rx_http, '', $key);
            $rx_matches = array();
            // do string manipulations to restore the original letter case
            $rx_matches = explode('_', $arh_key);
            if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                foreach ($rx_matches as $ak_key => $ak_val)
                    $rx_matches[$ak_key] = ucfirst($ak_val);
                $arh_key = implode('-', $rx_matches);
            }
            $arh[$arh_key] = $val;
        }
    }
    return( $arh );
}

$app->get('/', function() {
    echo 'Home - My Slim Application';
});

$app->post('/login', function() use($app, $db) {
    $app->response()->header("Content-Type", "application/json");
    $login = $app->request()->post();
    $result = $db->logins->insert($login);
    echo json_encode(array('id' => $result['id']));
});

/* Run the application */
$app->run();
