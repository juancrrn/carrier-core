<?php

namespace Carrier\Domain\StaticForm;

use Carrier\Common\App;

/**
 * Static form model
 * 
 * Forms must extend this class, which also provides handling functionality.
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

abstract class StaticFormModel
{

    /**
     * @var string Identifier (printed inside the HTML to check that the form
     *             was sent)
     */
    private $id;

    /**
     * @var string Submit URL (printed to the form action HTML attribute)
     */
    private $actionUrl;

    /**
     * @var string Stores the form's HTML
     */
    private $html = '';

    /**
     * @var string CSRF prefix $_SESSION storing
     */
    private const CSRF_PREFIX = 'carrier_csrf';

    /**
     * @var string Name of the HTML attribute for the CSRF token
     */
    private const CSRF_TOKEN_FIELD = 'csrf-token';

    /**
     * @var bool Force disable CSRF validation
     */
    private $forceDisableCsrfValidation = false;

    /**
     * Standard constructor
     * 
     * @param string $id
     * @param array $options
     */
    public function __construct(string $id, ?array $options = array())
    {
        $this->id = $id;
        
        if (isset($options['action'])) {
            $options['action'] = App::getSingleton()->getUrl() . $options['action'];

            $defaultOptions = array();
        } else {
            $defaultOptions = array('action' => null);
        }

        $options = array_merge($defaultOptions, $options);

        $this->actionUrl   = $options['action'];
        
        if (! $this->actionUrl) {
            $this->actionUrl = htmlentities($_SERVER['PHP_SELF']);
        }
    }
  
    /**
     * Handles the submission of a form
     */
    public function handle()
    {
        if ($this->isSent($_POST)) {
            $submittedCsrfToken = $_POST[self::CSRF_TOKEN_FIELD] ?? null;

            if ($this->CsrfValidateToken($submittedCsrfToken)) {
                $this->process($_POST);
            } else {
                App::getSingleton()
                    ->getViewManagerInstance()
                    ->addErrorMessage('Hubo un fallo en una verificación de seguridad. Por favor, vuelve a intentarlo.');
            }
        }  
    }
  
    /**
     * Checks if the form was actually sent
     *
     * @param array $params Array with the data sent with the form
     *
     * @return bool
     */
    private function isSent(& $params)
    {
        return isset($params["action"]) && $params["action"] == $this->id;
    } 

    /**
     * Generates the HTML with the form fields. Should be overrided.
     *
     * @param array $preloadedData   Initial form data
     * 
     * @return string
     */
    protected function generateFields(array & $preloadedData = array()): string
    {
        return '';
    }

    /**
     * Procesa los datos del formulario.
     * 
     * Durante el procesamiento del formulario pueden producirse errores, que 
     * serán gestionados por el mecanismo de mensajes definido en ViewManager.
     * 
     * En tal caso, es la propia función la que vuelve a generar el formulario
     * con los datos iniciales.
     *
     * @param array $postedData Datos enviado por el usuario (normalmente $_POST).
     */
    protected function process(array & $postedData): void
    {
        return;
    }

    /**
     * Función que genera el HTML necesario para el formulario.
     *
     * @param array $preloadedData   Initial form data
     *
     * @return string
     */
    public function initialize(array & $preloadedData = array()): void
    {
        $campos = $this->generateFields($preloadedData);

        $actionUrl = $this->actionUrl;
        $id = $this->id;

        if ($this->forceDisableCsrfValidation) {
            $csrfInput = '';
        } else {
            $csrfTokenFieldName = self::CSRF_TOKEN_FIELD;
            $csrfToken = $this->CsrfGenerateToken();
            
            $csrfInput = <<< HTML
            <input type="hidden" name="$csrfTokenFieldName" value="$csrfToken">
            HTML;
        }

        $this->html = <<< HTML
        <form method="post" action="$actionUrl" id="$id" class="default-form">
            $csrfInput
            <input type="hidden" name="action" value="$id" />
            $campos
        </form>
        HTML;
    }

    /**
     * Prints the HTML of the form
     */
    public function render(): void
    {
        echo $this->html;
    }

    /**
     * Returns the HTML of the form
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Generates a CSRF token and stores it in $_SESSION
     * 
     * @return string Generated token
     */
    private function CsrfGenerateToken(): string
    {
        if ($this->forceDisableCsrfValidation || App::getSingleton()->isDevMode())
            return '';

        $token = hash('sha512', mt_rand(0, mt_getrandmax()));

        $_SESSION[self::CSRF_PREFIX . '_' . $this->id] = $token;

        return $token;
    }

    /**
     * Validates a CSRF token
     * 
     * @param null|string $token Token to be validated
     * 
     * @return bool True if valid, else false
     */
    private function CsrfValidateToken(null|string $token): bool
    {
        if ($this->forceDisableCsrfValidation || App::getSingleton()->isDevMode())
            return true;

        if (! $token) return false;

        if (isset($_SESSION[self::CSRF_PREFIX . '_' . $this->id])
            && $_SESSION[self::CSRF_PREFIX . '_' . $this->id] === $token) {
            
            unset($_SESSION[self::CSRF_PREFIX . '_' . $this->id]);
                
            return true;
        }

        return false;
    }

    protected function forceDisableCsrfValidation(): void
    {
        $this->forceDisableCsrfValidation = true;
    }
}