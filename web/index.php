<?php

require_once(__DIR__ . '/../vendor/autoload.php');

// New EgfApp.
$app = new \Egf\App();
// todo \Egf\AppDev()?

//$app->get('session')->set('k1', 'qwer');
var_dump($app->get('session')->get('k1'));

$app->get('log')->info('End is here...' . PHP_EOL);


echo '<hr />END IS HERE!';
