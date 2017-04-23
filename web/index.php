<?php

require_once(__DIR__ . '/../vendor/autoload.php');

// New EgfApp.
$app = new \Egf\App(true);

//var_dump($app->getParam('secret'));

//$app->get('session')->set('k1', 'qwer2');
var_dump($app->get('session')->get('k1'));

$app->get('log')->info('End is here...' . PHP_EOL);

echo '<hr />END IS HERE!';


/**
 * todo list
 *
 * tempCache
 * multiple myDb connection
 * old services
 * session use myDb
 * templates/views
 * translations
 * user login
 * controllers/routing
 * myDb insert/update/delete together
 * .htaccess files (gitignore... in vendor)
 * commands
 * command: generate egf db schema (session data length from config)
 * session length from config
 * renamedServices.json
 * test on unix
 */

/**
 * @url http://phpenthusiast.com/blog/how-to-autoload-with-composer
 * composer dump-autoload -o
 */