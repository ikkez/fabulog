<?php
/** @var Base $f3 */
$f3 = require('lib/base.php');
$f3->set('APP_VERSION', '0.2.0');

//ini_set('display_errors', 1);
//error_reporting(-1);

//$f3->set('JAR.expire', time()+(60*60*2));

// preflight system check
if (!is_dir($f3->get('TEMP')) || !is_writable($f3->get('TEMP')))
	$preErr[] = sprintf('please make sure that the \'%s\' directory is existing and writable.',$f3->get('TEMP'));
if (!is_writable('res/'))
	$preErr[] = sprintf('please make sure that the \'%s\' directory is writable.','res/');
if (!is_writable('app/data/'))
	$preErr[] = sprintf('please make sure that the \'%s\' directory is writable.','app/data/');
if (!is_writable('app/data/config.json'))
	$preErr[] = sprintf('please make sure that the \'%s\' file is writable.','app/data/config.json');

if(isset($preErr)) {
	header('Content-Type: text;');
	die(implode("\n",$preErr));
}

$f3->config('app/config.ini');

## DB Setup
$cfg = Config::instance();
if ($cfg->ACTIVE_DB)
    $f3->set('DB', storage::instance()->get($cfg->ACTIVE_DB));
else {
    $f3->error(500,'Sorry, but there is no active DB setup.');
}

$f3->set('CONFIG', $cfg);
$f3->set('FLASH', Flash::instance());

\Template::instance()->extend('image','\Template\Tags\Image::render');
\Template::instance()->extend('pagebrowser','\Pagination::renderTag');
// Handles all <form> data
\Template\FooForms::init();

## POSTS
// view list
$f3->route(array(
    'GET /',
    'GET /page/@page'
   ),'Controller\Post->getList');
// view single
$f3->route(array(
    'GET /@slug',
    'GET /post/@id'
   ), 'Controller\Post->getSingle');
// post comment
$f3->route('POST /@slug', 'Controller\Post->addComment');

## TAGS
$f3->route(array(
    'GET /tag [ajax]',
   ),'Controller\Tag->getList');

$f3->route('GET /tag/@slug','Controller\Post->getListByTag');


///////////////
//  backend  //
///////////////

if (\Controller\Auth::isLoggedIn()) {

    // specific routes
    // comments
    $f3->route(array(
        'GET /admin/comment/list/@viewtype',
        'GET /admin/comment/list/@viewtype/@page',
    ), 'Controller\Comment->getList');

    // general CRUD operations
    $f3->route('GET|POST /admin/@module', 'Controller\Backend->getList');
    $f3->route('GET|POST /admin/@module/@page', 'Controller\Backend->getList');
//    $f3->route('GET|POST /admin/@module/@action', 'Controller\Backend->@action');
    $f3->route('GET|POST /admin/@module/@action/@id', 'Controller\Backend->@action');
    // some method reroutes
    $f3->route('GET /admin/@module/create', 'Controller\Backend->getSingle');
    $f3->route('POST /admin/@module/create', 'Controller\Backend->post');
    $f3->route('GET /admin/@module/edit/@id', 'Controller\Backend->getSingle');
    $f3->route('POST /admin/@module/edit/@id', 'Controller\Backend->post');

    // backend home - dashboard
    $f3->route('GET /admin', 'Controller\Dashboard->main');

    // settings panel
    $f3->route('GET|POST /admin/settings', 'Controller\Settings->general');
    $f3->route('GET|POST /admin/settings/@type', 'Controller\Settings->@type');

    // no auth again
    $f3->redirect('GET|POST /login', '/admin', false);

    // upload file
    $f3->route('POST /admin/file [ajax]', function ($f3) {
        $result = \Web::instance()->receive(function ($file) {
                $allowed_types = array('image/png', 'image/jpeg', 'image/gif', 'image/bmp');
                return in_array($file['type'], $allowed_types);
            },
            true, // overwrite
            true // rename to UTF-8 save filename
        );
        echo json_encode($result);
    });

} else {
    // login
    $f3->redirect(array('GET|POST /admin/*','GET|POST /admin'), '/login', false);
    $f3->route('GET|POST /login','Controller\Auth->login');
}

$f3->route('GET /logout', 'Controller\Auth->logout');


// let's cross the finger
$f3->run();
//var_dump($f3->get('DB')->log());