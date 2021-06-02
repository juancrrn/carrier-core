<?php 

namespace Carrier\Common;

use Carrier\Common\Api\ApiManager;
use Carrier\Common\SessionManager;
use Carrier\Common\Controller\Controller;
use Carrier\Common\View\ViewManager;
use Exception;
use mysqli;
use mysqli_sql_exception;

/**
 * App initialization and general functionality
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class App
{
    
    /**
     * @var string App instance
     */
    private static $instance;
    
    /**
     * @var SessionManager Session manager instance
     */
    private $sessionManagerInstance;
    
    /**
     * @var Controller Controller instance
     */
    private $controllerInstance;

    /**
     * @var ViewManager View manager instance
     */
    private $viewManagerInstance;

    /**
     * @var ApiManager API manager instance
     */
    private $apiManagerInstance;

    /**
     * @var \mysqli Database connection instance
     */
    private $dbConn;

    /**
     * @var array Database credentials
     */
    private $dbCredentials;

    /**
     * @var string Installation root directory
     */
    private $root;

    /**
     * @var string Public URL
     */
    private $url;

    /**
     * @var string Public URL path base for controller
     */
    private $pathBase;

    /**
     * @var string App name
     */
    private $name;

    /**
     * @var string Class name of the ViewModel header part
     */
    private $headerPartClassName;

    /**
     * @var string Class name of the ViewModel footer part
     */
    private $footerPartClassName;
    
    /**
     * @var bool Dev mode
     */
    private $devMode;

    /**
     * @var array Email settings
     */
    private $emailSettings;

    /**
     * @var array Additional settings
     */
    private $additionalSettings;

    /**
     * Standard constructor (not usable)
     */
    private function __construct()
    {
    }

    /**
     * Prevents from cloning
     */
    public function __clone()
    {
        throw new \Exception("Cloning not allowed.");
    }

    /**
     * Prevents from serializing
     */
    public function __sleep()
    {
        throw new \Exception("Serializing not allowed.");
    }

    /**
     * Prevents from deserializing
     */
    public function __wakeup()
    {
        throw new \Exception("Deserializing not allowed.");
    }

    /**
     * Instantiate the app
     */
    public static function getSingleton(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Initialize the app instance
     */
    public function init(
        array $dbCredentials,

        string $root,
        string $url,
        string $pathBase,
        string $name,

        string $headerPartClassName,
        string $footerPartClassName,

        bool $devMode,

        array $emailSettings,

        array $additionalSettings
    ): void
    {
        $this->dbConn = null;

        $this->dbCredentials = $dbCredentials;

        $this->root = $root;
        $this->url = $url;
        $this->pathBase = $pathBase;
        $this->name = $name;

        $this->headerPartClassName = $headerPartClassName;
        $this->footerPartClassName = $footerPartClassName;

        $this->devMode = $devMode;

        $this->emailSettings = $emailSettings;

        $this->additionalSettings = $additionalSettings;

        $this->sessionManagerInstance = new SessionManager;
        $this->sessionManagerInstance->init();

        $this->controllerInstance = new Controller($pathBase);
        
        $this->viewManagerInstance = new ViewManager;
        
        $this->apiManagerInstance = new ApiManager;
    }

    /**
     * Starts a connection with the database
     */
    public function getDbConn(): mysqli
    {
        if (! $this->dbConn) {
            $host = $this->dbCredentials['host'];
            $user = $this->dbCredentials['user'];
            $password = $this->dbCredentials['password'];
            $name = $this->dbCredentials['name'];

            try {
                $this->dbConn = new mysqli($host, $user, $password, $name);
            } catch (mysqli_sql_exception $e) {
                throw new Exception('Error al conectar con la base de datos.', 0, $e);
            }

            try {
                $this->dbConn->set_charset("utf8mb4");
            } catch (mysqli_sql_exception $e) {
                throw new Exception('Error al configurar la codificación de la base de datos.', 1);
            }
        }

        return $this->dbConn;
    }

    /*
     *
     * Getters
     * 
     */

    public function getSessionManagerInstance(): SessionManager
    {
        return $this->sessionManagerInstance;
    }

    public function getControllerInstance(): Controller
    {
        return $this->controllerInstance;
    }

    public function getViewManagerInstance(): ViewManager
    {
        return $this->viewManagerInstance;
    }

    public function getApiManagerInstance(): ApiManager
    {
        return $this->apiManagerInstance;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPathBase(): string
    {
        return $this->pathBase;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHeaderPartClassName(): string
    {
        return $this->headerPartClassName;
    }

    public function getFooterPartClassName(): string
    {
        return $this->footerPartClassName;
    }

    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    public function getEmailSettings(): array
    {
        return $this->emailSettings;
    }

    public function getAdditionalSettings(): array
    {
        return $this->additionalSettings;
    }

    public function getAdditionalSetting(mixed $key): mixed
    {
        return $this->additionalSettings[$key];
    }
}