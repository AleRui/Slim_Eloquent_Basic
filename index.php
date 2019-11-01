<?php

use Kint\Kint;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

// Configuration
$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
        'debug' => true,
        'db' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test_slim',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],
    ],
];

$app = new \Slim\App($configuration);

//Add Dependencies Injector (DI)
$container = $app->getContainer();

// Log
$container['logger'] = function ($container) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('./app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

// DB
$container['db'] = function ($container) {
    $db = $container['settings']['db'];
    $pdo = new PDO(
        $db['driver'].':host=' . $db['host'] . ';dbname=' . $db['database'],
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// Eloquent
$container['dbEloquent'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db']);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};

// Route
$app->get('/test', function (Request $request, Response $response, array $args) {
    //
    // Dependencies
    //
    // LOG
    if ($this->has('logger')) {
        //d($this->logger);
        $this->logger->info('My logger is now ready');
    }
    //
    // Dependencie DB
    if ($this->has('db')) {
        $sql = "SELECT * FROM clientes";
        $connectionDB = $this->db;
        $query = $connectionDB->prepare($sql);
        $query->execute();
        $clientes = $query->fetch();
        d($clientes);
        $connectionDB = null;
    }
    //
    // Dependencie Eloquent
    if ($this->has('dbEloquent')) {
        $eloquent = $this->dbEloquent;
        $table = $eloquent->table('clientes');
;       d($table->get());
    }
    //
    // PSR-7 Interfaz
    //
    // Request
    $myRequest = $request->getHeader('Accept');
    //
    // Response
    $status = $response->getStatusCode();
    //
    $myResponse = 'Respuesta<br>';
    $myResponse .= '<pre>' . print_r($myRequest) . '</pre><br>';
    $myResponse .= $status.'<br>';
    //
    $response->getBody()->write($myResponse);
    //
    return $response;
});

$app->run();
