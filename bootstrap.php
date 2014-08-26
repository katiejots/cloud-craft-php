<?php
require 'vendor/autoload.php';
$secret_key = getenv('SECRET_KEY') ? getenv('SECRET_KEY') : 'Cth4nG3_M3';
$config = getenv('OPENSHIFT_MONGODB_DB_URL') . getenv('OPENSHIFT_APP_NAME');

$client = new MongoClient($config);
$db = $client->selectDB(getenv('OPENSHIFT_APP_NAME'));
$toilets = new MongoCollection($db, 'toilets');

$result = $toilets->ensureIndex(array('geometry.coordinates'=>"2dsphere"));
?>
