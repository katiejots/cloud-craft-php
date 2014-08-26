<?php
//Allow PHP's built-in server to serve our static content in local dev:
if (php_sapi_name() === 'cli-server' && is_file(__DIR__.'/static'.preg_replace('#(\?.*)$#','', $_SERVER['REQUEST_URI']))
   ) {
  return false;
}

require 'vendor/autoload.php';
use Symfony\Component\HttpFoundation\Response;
$app = new \Silex\Application();

$app->get('/', function () use ($app) {
  return $app->sendFile('static/index.html');
});

$app->get('/toilets', function () use ($app) {
  $db_connection = getenv('OPENSHIFT_MONGODB_DB_URL') ? getenv('OPENSHIFT_MONGODB_DB_URL') . getenv('OPENSHIFT_APP_NAME') : "mongodb://localhost:27017/";
  $client = new MongoClient($db_connection);
  $db = getenv('OPENSHIFT_APP_NAME') ? $client->selectDB(getenv('OPENSHIFT_APP_NAME')) : $client->selectDB('findaloo');
  $toilets = new MongoCollection($db, 'toilets');
  $result = $toilets->find();

  $response = "[";
  foreach ($result as $toilet){
    $response .= json_encode($toilet);
    if( $result->hasNext()){ $response .= ","; }
  }
  $response .= "]";
  return $app->json(json_decode($response));
});

$app->get('/toilets/within', function () use ($app) {
  $db_connection = getenv('OPENSHIFT_MONGODB_DB_URL') ? getenv('OPENSHIFT_MONGODB_DB_URL') . getenv('OPENSHIFT_APP_NAME') : "mongodb://localhost:27017/";
  $client = new MongoClient($db_connection);
  $db = getenv('OPENSHIFT_APP_NAME') ? $client->selectDB(getenv('OPENSHIFT_APP_NAME')) : $client->selectDB('findaloo');
  $toilets = new MongoCollection($db, 'toilets');

  #clean these input variables:
  $lat1 = floatval($app->escape($_GET['lat1']));
  $lat2 = floatval($app->escape($_GET['lat2']));
  $lon1 = floatval($app->escape($_GET['lon1']));
  $lon2 = floatval($app->escape($_GET['lon2']));
  
  if(!(is_float($lat1) && is_float($lat2) &&
       is_float($lon1) && is_float($lon2))){
    $app->json(array("error"=>"lon1,lat1,lon2,lat2 must be numeric values"), 500);
  }else{
    $result = $toilets->find( 
      array( 'geometry.coordinates' =>
        array( '$geoWithin' => 
          array( '$box' =>
            array(
              array( $lon1, $lat1),
              array( $lon2, $lat2)
    )))));
  }
  try{ 
    $response = "[";
    foreach ($result as $toilet){
      $response .= json_encode($toilet);
      if( $result->hasNext()){ $response .= ","; }
    }
    $response .= "]";
    return $app->json(json_decode($response));
  } catch (Exception $e) {
    return $app->json(array("error"=>json_encode($e)), 500);
  }
});

$app->run();
?>
