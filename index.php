<?php
error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);

require_once $_SERVER['DOCUMENT_ROOT'] . '/loader.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/dumper.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amr/modules/main/tools.php';

\spl_autoload_register([Loader::class, 'autoLoad']);

Loader::switchAutoLoad(true);
Loader::registerNamespace('Amr\Main\Classes', 'main', 'classes' );
Loader::registerNamespace('Amr\Main\Classes\General', 'main', 'classes/general' );
Loader::registerNamespace('Amr\Main\Classes\Mysql', 'main', 'classes/mysql' );
Loader::registerNamespace('Amr\Main\Classes\Tools', 'main', 'classes/tools' );
Loader::registerNamespace('Amr\Main\Lib', 'main', 'lib' );
Loader::registerNamespace('Amr\Main\Lib\Type', 'main', 'lib/type' );
Loader::registerNamespace('Amr\Main\Lib\Config', 'main', 'lib/config' );
Loader::registerNamespace('Amr\Main\Lib\Page', 'main', 'lib/page' );
Loader::registerNamespace('Amr\Main\Lib\Localization', 'main', 'lib/localization' );
Loader::registerNamespace('Amr\Main\Lib\Io', 'main', 'lib/io' );
Loader::registerNamespace('Amr\Main\Lib\Data', 'main', 'lib/data' );

// var_dump(Loader::$customNamespaces);die;

define("LANG",          's1');
define("SITE_ID",       's1');
define("SITE_DIR",      '/');
define("LANG_DIR",      '/');
define("LANGUAGE_ID",   'ru');
define("SITE_TEMPLATE_ID",   'twbs4_1');

$appHttp         =Amr\Main\Lib\HttpApplication::getInstance();
$appHttp        ->initializeBasicKernel();

$app            =Amr\Main\Lib\Application::getInstance();
$app           ->initializeExtendedKernel(array(
                	"get"      => $_GET,
                	"post"     => $_POST,
                	"files"    => $_FILES,
                	"cookie"   => $_COOKIE,
                	"server"   => $_SERVER,
                	"env"      => $_ENV
                ));
            // ->initializeContext($params);

$main = new Amr\Main\Classes\Mysql\CMain;

$context = $app->getContext();
$request = $context->getRequest();

$main->reinitPath();

AddEventHandler("main", "OnAfterEpilog", array("\\Amr\\Main\\Lib\\Data\\ManagedCache", "finalize"));

session_start();

foreach (GetModuleEvents("main", "OnPageStart", true) as $arEvent)
	// d($arEvent);
	ExecuteModuleEventEx($arEvent);


$user = new CUser;

// d($main);die;
