<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
session_start();

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

const VERBOSITY = 0;
const PUBLIC_DIRNAME = "public";
const CONFIG_WEBROUTES = "/../routes/web.php"; // like in laravel
const CONFIG_DB = "/../config/db.php";
const ROUTER_VERSION = '0.8.5';

assert_php_version('8.2.0');
assert_path();

try {
    if (!file_exists(realpath($_SERVER['DOCUMENT_ROOT'] . "/../vendor/autoload.php"))) {
        echo "<h1>Abhängigkeiten nicht gefunden</h1><pre>DOCUMENT_ROOT: {$_SERVER['DOCUMENT_ROOT']}</pre><br><p>Datei nicht gefunden: <strong>{$_SERVER['DOCUMENT_ROOT']}/../vendor/autoload.php</strong></p>";
        echo "<p>Häufigste Ursache</p><ul>
            <li>Das Verzeichnis <code>public/</code> ist <em>nicht</em> als Wurzelverzeichnis verwendet worden.</li>
            <li>Die Abhängigkeiten wurden nicht mit <code>composer update</code> installiert.</code></li>
            </ul>";
        exit(1);
    }
    // file exists
    require_once realpath($_SERVER['DOCUMENT_ROOT'] . "/../vendor/autoload.php");

} catch (Exception $ex) {
    echo "<code>DOCUMENT_ROOT</code><br><pre>{$_SERVER['DOCUMENT_ROOT']}</pre><code>Error</code><br><pre>" . $ex->getMessage() . "</pre>";
}

use eftec\bladeone\BladeOne;

/* Routing Script for PHP Dev Server */
$verbosity = VERBOSITY;
if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
    return false;
} else {
    if ($verbosity > 1) {
        echo
            "<pre>Verbosity-Level: <strong>{$verbosity}</strong></pre>" .
            "<pre>" . print_r($_SERVER, 1) . "</pre><hr>";
    }
    FrontController::handleRequest("$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", $_SERVER['REQUEST_METHOD'], VERBOSITY);
}

class RequestData
{
    /**
     * @var array Request Querystring, broken down to key-value pairs
     */
    public array $query;
    /**
     * @var array Request arguments from path, after cutting two segments out for controller and action names
     */
    public array $args;
    /**
     * @var string HTTP Verb used
     */
    public string $method;

    /**
     * returns all GET and POST params merged in one array
     * @return array
     */
    public function getData()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * returns all POST params
     * @return array
     */
    public function getPostData()
    {
        return $_POST;
    }

    /**
     * returns all GET params
     * @return array
     */
    public function getGetData()
    {
        return $_GET;
    }

    /**
     * @return mixed
     */
    public function getRequestUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * returns the uploaded files information
     * @return array
     */
    public function getFiles() {
        return $_FILES;
    }

    /**
     * RequestData is the way the Router will provide information, use it in your Action methods.
     * @param $method string Verb used
     * @param $args array Arguments
     * @param $query array Key-Value Pairs
     */
    public function __construct($method, $args, $query)
    {
        $this->query = $query;
        $this->args = $args;
        $this->method = $method;
    }
}

class FrontController
{

    public static function handleRequest($url, $method = 'GET', $verbosity = 0, $configPath = CONFIG_WEBROUTES)
    {
        assert_blade(); // check if the class is found

        if (!str_contains($url, ':')) $url = $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];

        $scriptPath = dirname(__FILE__, 2) . '/';
        $controllerDirectory = $scriptPath . 'controllers/';

        // get a router/web-like config array to override filebased convention
        $config = FrontController::getConfig($configPath);

        // /Impressum/ --> ImpressumController->index()
        $request = parse_url($url);
        $ctrlName = $request['path'];
        $actionName = 'index';
        $args = array();
        $query = array();
        parse_str($request['query'] ?? "", $query);

        // provide POST data
        if ($method != 'GET')
            $query = array_merge($query, $_REQUEST);


        // check, if route has two levels
        if (strpos($ctrlName, '/', 1) > 0) {
            $path = explode('/', $request['path']); // Werbeseite/Speise/1/mobile?pretty=true&user=marcel
            array_shift($path);                             // skip once
            $ctrlName = array_shift($path);         // Werbeseite
            $actionName = array_shift($path);       // Speise
            $args = $path; // remainder of path-parts     // [1][mobile]
            if ($verbosity > 1) {
                echo "<pre>Request\n", print_r($request), "</pre>";
                echo "<pre>Path\n", print_r($path), "</pre>";
                echo "<pre>Query\n", print_r($query), "</pre>";
            }
        }

        // fix: trim slashes
        $ctrlName = trim($ctrlName, '/');
        $actionName = trim($actionName, '/');

        // $config based renaming of Controller/Action, precedes filebased convention
        // $config values must use syntax <ClassController>@<actionname>
        if (array_key_exists('/' . $ctrlName . '/' . $actionName, $config)) {
            $routingConfig = explode('@', $config['/' . $ctrlName . '/' . $actionName]);
            if ($verbosity > 0) {
                echo "<p>Routing Config matched request for <code>/" . $ctrlName . "/" . $actionName . "</code>:</p><p>routing config is</p><pre>" . print_r($routingConfig, 1) . '</pre>';
            }
            // important: overwriting controller and action name
            $ctrlClass = $routingConfig[0];
            $actionName = $routingConfig[1];
        } elseif (array_key_exists($request['path'], $config)) {
            // exact match on full path, this also means "/"
            $routingConfig = explode('@', $config[$request['path']]);
            if ($verbosity > 0) {
                echo "<p>Routing Config matched for full path <code>" . $request['path'] . "</code>:</p><p>routing config is</p><pre>" . print_r($routingConfig, 1) . '</pre>';
            }
            // important: overwriting controller and action name
            $ctrlClass = $routingConfig[0];
            $actionName = $routingConfig[1];
        } else {
            if ($verbosity > 0) {
                echo "Request $ctrlName/$actionName was not in \$config.";
            }

            // fall back to filebased convention: match controller classes in directory
            $ctrlClass = ucfirst($ctrlName . 'Controller');
        }

        $ctrlFile = ($ctrlClass . '.php');
        $validControllers = FrontController::getValidControllers($controllerDirectory);
        if (!in_array($controllerDirectory . $ctrlFile, $validControllers)) {
            if ($verbosity > 0) {
                echo "<div><p>Controller: $ctrlFile not found in</p><pre>" . print_r($validControllers, 1) . "</pre><p>Config Array:</p><pre>" . print_r($config, 1) . "</pre></div>";
            }
            // #ERROR
            FrontController::showErrorMessage("<h1>Web Software Error</h1><img alt='shrug' src='https://media.tenor.com/On7kvXhzml4AAAAi/loading-gif.gif'>" .
                "<p>Keine entspreche Zuordnung der Route für {$ctrlName}::{$actionName} gefunden. Tippfehler in der Route?" .
                "<p>Es konnte keine Klasse <abbr title='Gesucht im Verzeichnis {$controllerDirectory}'>" . $ctrlFile . "</abbr> gefunden werden! Request fehlgeschlagen.</p>" .
                "<p> Prüfen Sie die Einträge in der Datei <code>config/web.php</code> und gleichen Sie den getätigten Aufruf damit ab.</p>");
        }

        // a file matching has been found, now try to load the class
        try {
            require_once $controllerDirectory . $ctrlFile;
            // instantiate the controller

            $controller = new $ctrlClass();
            $rd = new RequestData($method, $args, $query);

            if ($verbosity > 0) {
                var_dump($controller, $rd);
            }
            // the controller will load model and view and return some html
            print call_user_func_array(array($controller, $actionName), array($rd));
        } catch (Exception $ex) {
            // #ERROR
            FrontController::showErrorMessage(
                "<h2>Fehler in Controller " . get_class($controller) . "!</h2><p>Stellen Sie sicher, dass die Action/der Controller existiert.</p>
                    <p>Das Routing Config-Array hat " . count($config) . " Einträge.</p>
                    <p><strong>Exception text</strong><br>" . $ex->getMessage() . "</p>");
        }
    }

    public static function showErrorMessage($text, $severity = 3, $die = true)
    {
        $styles = array(0 => "background-color: #F08170; border: 2px solid lightgray; padding: 2em; margin: 5em; width: 50%; box-shadow: 0em 0em 1em #F08170;",
            1 => "background-color: #F08170; border: 2px solid lightgray; padding: 2em; margin: 5em; width: 50%; box-shadow: 0em 0em 1em #F08170;",
            2 => "background-color: #F08170; border: 2px solid lightgray; padding: 2em; margin: 5em; width: 50%; box-shadow: 0em 0em 1em #F08170;",
            3 => "background-color: #F08170; border: 2px solid lightgray; padding: 2em; margin: 5em; width: 50%; box-shadow: 0em 0em 1em #F08170;");
        print("<div style=\"{$styles[$severity]}\">{$text}</div>");
        if ($die) exit($severity);
    }

    public static function getConfig($configPath)
    {
        try {
            // load the $config Array from a file given in $configPath
            $path_to_config = realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $configPath);
            // print("Path to config " . $path_to_config);
            $config = include $path_to_config;
        } catch (Exception $e) {
            print_r($e);
            $config = array('/' => 'HomeController@index');
        } finally {
            return $config;
        }
    }

    public static function getValidControllers($path = '')
    {
        if ($path == '') {
            $path = getcwd() . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR;
        }
        return glob($path . '*Controller.php');
    }
}

function connectdb()
{
    $path_to_config_db = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . CONFIG_DB;
    $config = include $path_to_config_db;
    $link = mysqli_connect(     // Daten aus config/db.php
        $config['host'],
        $config['user'],
        $config['password'],
        $config['database'],    // Auswahl der Datenbank
        $config['port'] ?? 3306);
    if (!$link) {
        FrontController::showErrorMessage("<h1>Verbindung mit der Datenbank nicht möglich</h1>
            <p style='margin-bottom: 2em;'>Verbindung fehlgeschlagen: <code>" . mysqli_connect_error() . "</code>.</p>
            <h2>Prüfen Sie</h2><ol><li>die Angaben in der Datei <code>config/db.php</code>:
            ( ist Benutzer <code>{$config['user']}</code> an Datenbank <code>{$config['database']}</code> auf Server <code>{$config['host']}</code> korrekt?)<br>
            </li><li>ob Ihre Datenbank unter der oben gezeigten Adresse läuft </li></ol>");
        exit(1);
    }
    if (mysqli_connect_errno()) {
        FrontController::showErrorMessage("<h1>Verbindung mit der Datenbank nicht möglich</h1>
            <code>Fehlermeldung</code><pre>" . mysqli_connect_error() . "</pre>", 2, true);
        exit(1);
    }

    return $link;
}

function view($viewname, $viewargs = array())
{
    $views = dirname(__DIR__) . '/views';
    $cache = dirname(__DIR__) . '/storage/cache';
    $blade = new BladeOne($views, $cache, BladeOne::MODE_DEBUG);

    return $blade->run($viewname, $viewargs);
}
//aufabge  3 monolog
function logger()
{
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/../storage/logs')) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . '/../storage/logs', 0777, true);
    }

    $log = new Logger('emensa');
    $log->pushHandler(new StreamHandler($_SERVER['DOCUMENT_ROOT'] . '/../storage/logs/emensa.log', Logger::INFO));

    return $log;
}

/**
 * let the script die if the php minimum version is not met.
 * @param $minversion
 * @return void
 */
function assert_php_version($minversion = '8.0.0')
{
    $version_too_low = 0;
    $minver = explode('.', $minversion);
    $version = explode('.', phpversion());

    if (intval($minver[0]) > intval($version[0])) {
        $version_too_low = 1;
    } elseif (intval($minver[1]) > intval($version[1])) {
        $version_too_low = 1;
    } elseif (intval($minver[2]) > intval($version[2])) {
        $version_too_low = 1;
    }

    if ($version_too_low) {
        FrontController::showErrorMessage("Diese PHP-Version wird nicht unterstützt: <strong>Minimum PHP Version " . $minversion . "</strong><br>Sie betreiben gerade PHP Version " . phpversion());
        exit(1);
    }
    // version is okay, go on.
}

/**
 * let the script die if the path contains problematic characters.
 * @return void
 */
function assert_path(): void
{
    static $chars = array("[", "]", "{", "}");
    $charsfound = 0;
    str_ireplace($chars, '', $_SERVER['DOCUMENT_ROOT'], $charsfound);
    if ($charsfound > 0) {
        FrontController::showErrorMessage("<h1>Bitte verwenden Sie einen anderen Ordner für das Projekt!</h1>
        <p>Der Pfad <strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong> enthält <code>" . $charsfound . "</code> problematische Zeichen, die die korrekte Ausführung verhindern.</p>>
        <p>Bekannte problematische Zeichen sind</p>
        <pre> " . implode(" ", $chars) . " </pre>");
        exit(1);
    }

}

function assert_blade(): void
{
    if (!class_exists('eftec\bladeone\BladeOne')) {
        // #ERROR
        FrontController::showErrorMessage("
                <h1>Fehler: Blade wurde nicht gefunden</h1>
                <p>Tipps für die Lösung:</p>
                <ul>
                <li><p>führen Sie im Terminal folgende Zeilen aus.</p>
                <ol>
                    <li><code>php bin/composer.phar update</code> oder <code>php bin/composer.phar reinstall eftec/bladeone</code></li>
                    <li><code>php bin/composer.phar dump-autoload</code></li>
                </ol>
                <li><p>Prüfen Sie im Anschluss: befindet sich in dem Ordner <code>vendor/eftec/bladeone/lib/</code> die Datei <code>BladeOne.php</code> ?</p></li>
                <li><p>Starten Sie dann den Webserver neu.</p></li>
                <li><p>Befragen Sie gerne auch das Forum in Ilias.</p></li>
                </ul>
                
                </div>");
        exit(1);
    }
}