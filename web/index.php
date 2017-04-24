<?php

require_once(__DIR__ . '/../vendor/autoload.php');

// New EgfApp.
$app = new \Egf\App(true);

//$app->get('session')->set('k1', 'qwer2');
//var_dump($app->get('session')->get('k1'));

//if (!$app->get('permCache')->has('qwer/asdf')) {
//    $app->get('permCache')->set('qwer/asdf', 'zxcv3');
//}
//var_dump($app->get('permCache')->get('qwer/asdf'));

//echo $app->get('template')->render('Test/One:tpl', [
//	'a' => '1234',
//]);

$a = $app->get('myDb')->query('SELECT * FROM qwer WHERE id = ?', [1]);
var_dump($a);
echo "<br />";
foreach ($a as $b) {
	var_dump($b);
	echo "<br />";
}
echo '<hr />';

$app->get('log')->info('End is here...' . PHP_EOL);
echo '<hr />END IS HERE!';


/**
 * todo list
 *
 * repository / dbWhere
 * old services
 * session use myDb
 * controllers/routing
 * forms
 * validation
 * user login
 * myDb insert/update/delete together
 * tempCache
 * translations
 * commands
 * command: generate egf db schema (session data length from config)
 * session length from config
 * .htaccess files (gitignore... in vendor)
 * renamedServices.json
 * trait use egf\Services (example getMyDb())
 * test on unix
 */

/**
 * @url http://phpenthusiast.com/blog/how-to-autoload-with-composer
 * composer dump-autoload -o
 */