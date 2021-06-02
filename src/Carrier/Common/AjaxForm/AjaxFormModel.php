<?php

namespace Carrier\Common\AjaxForm;

use Carrier\Common\Http;
use stdClass;

/**
 * AJAX form model
 * 
 * Forms must extend this class, which also provides handling functionality.
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

abstract class AjaxFormModel
{

    /**
     * @var string Identifier
     */
    private $id = null;

    /**
     * @var string HTML attribute for the identifier
     */
    private const FORM_ID_FIELD = 'form-id';

    /**
     * @var string Name
     */
    private $formName = null;

    /**
     * @var string Name of the object modified by the form
     */
    private $targetObjectName = null;

    /**
     * @var string HTML attribute for the name of the object modified by the
     *             form
     */
    private const TARGET_CLASS_NAME_FIELD = 'ajax-target-object-name';

    /**
     * @var string Read only
     */
    private $readOnly = false;

    /**
     * @var string HTML attribute for read only
     */
    private const READ_ONLY_FIELD = 'ajax-read-only';

    /**
     * @var string Subit URL
     */
    private $submitUrl = null;

    /**
     * @var string HTML attribute for the submit URL
     */
    private const SUBMIT_URL_FIELD = 'ajax-submit-url';

    /**
     * @var string Name of the event fired on AJAX success response
     */
    private $onSuccessEventName = null;

    /**
     * @var string HTML attribute for the name of the event fired on AJAX
     *             success response
     */
    private const ON_SUCCESS_EVENT_NAME_FIELD = 'ajax-on-success-event-name';

    /**
     * @var string Name of the target object of the event
     */
    private $onSuccessEventTarget = null;

    /**
     * @var string HTML attribute for the name of the target object of the event
     */
    private const ON_SUCCESS_EVENT_TARGET_FIELD = 'ajax-on-success-event-target';

    /**
     * @var string Expected submit method
     */
    private $expectedSubmitMethod = null;

    /**
     * @var string HTML attribute for the expected submit method
     */
    private const EXPECTED_SUBMIT_METHOD_FIELD = 'ajax-submit-method';

    /**
     * @var string CSRF prefix for $_SESSION storing
     */
    private const CSRF_PREFIX = 'csrf';

    /**
     * @var string HTML attribute for the CSRF token
     */
    private const CSRF_TOKEN_FIELD = 'csrf-token';

    /**
     * @var string Admitted content type JSON
     */
    private const JSON_ADMITTED_CONTENT_TYPE = 'application/json; charset=utf-8';

    /**
     * Standard constructor
     */
    public function __construct(
        string $formId,
        string $formName,
        ?string $targetObjectName,
        ?string $submitUrl,
        ?string $expectedSubmitMethod
    )
    {
        $this->id = $formId;
        $this->targetObjectName = $targetObjectName;
        $this->formName = $formName;
        $this->submitUrl = $submitUrl;
        
        if ($expectedSubmitMethod && ! in_array($expectedSubmitMethod, Http::METHODS)) {
            throw new \Exception("Unsupported submit method \"$expectedSubmitMethod\".");
        }

        $this->expectedSubmitMethod = $expectedSubmitMethod;
    }

    /*
     *
     * Getters and setters
     * 
     */

    public function setOnSuccess(
        string $onSuccessEventName,
        string $onSuccessEventTarget
    ): void
    {
        $this->onSuccessEventName = $onSuccessEventName;
        $this->onSuccessEventTarget = $onSuccessEventTarget;
    }

    public function setReadOnlyTrue(): void
    {
        $this->readOnly = true;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Handles HTTP requests
     */
    public function handle(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? null;

        if (mb_strtolower($contentType) !=
            mb_strtolower(self::JSON_ADMITTED_CONTENT_TYPE)) {
            $this->respondJsonErrorWithCsrf(400, array(
                'Content type not supported'
            ));
        }

        // Check request method
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        
        if ($httpMethod === 'GET') {
            // Check form submit is valid
            $submittedFormId = $_GET[self::FORM_ID_FIELD] ?? null;

            if ($submittedFormId == $this->id) {
                // Generate default form data.
                $this->processInitialData($_GET);
            }

        // Check form is not read-only and method is the one expected
        } elseif (! $this->isReadOnly()
            && $httpMethod === $this->expectedSubmitMethod) {

            // Get request data as associative array
            $dataInput = file_get_contents('php://input');
            $data = json_decode($dataInput, true);

            // Check form submit is valid
            $submittedFormId = $data[self::FORM_ID_FIELD] ?? null;

            if ($submittedFormId == $this->id) {
                $submittedCsrfToken = $data[self::CSRF_TOKEN_FIELD] ?? null;

                if ($this->CsrfValidateToken($submittedCsrfToken)) {
                    $this->processSubmit($data);
                } else {
                    $errorMessages = array('La validación CSRF ha fallado. Por favor, vuelve a cargar el formulario.');

                    $this->respondJsonErrorWithCsrf(400, $errorMessages);
                }
            }
        } else {
            $this->respondJsonErrorWithCsrf(400, // Bad request
                array('Method not supported')
            );
        }

        // End script execution.
        die();
    }

    /**
     * Responds with an HTTP 4XX error and message and sends a new CSRF token.
     * 
     * @param int   $httpCode HTTP error code
     * @param array $messages Error messages
     */
    public function respondJsonErrorWithCsrf(int $httpErrorCode, array $messages): void
    {
        // Generate a new CSRF token.
        $newCsrfToken = $this->CsrfGenerateToken();

        $errorData = array(
            'status' => 'error',
            self::FORM_ID_FIELD => $this->id,
            self::CSRF_TOKEN_FIELD => $newCsrfToken,
            'error' => $httpErrorCode,
            'messages' => $messages
        );

        Http::respondJson($httpErrorCode, $errorData);
    }

    /**
     * Loads the default form data (i. e. for reading, updating and deleting) 
     * and returns it; should be overriden if necessary
     * 
     * Defauld data keys must be mapped to form input names in
     * generateFormInputs()
     *
     * @param array $requestData Data sent in the request; may contain a 
     * uniqueId
     *
     * @return array Set of default data for the form, as "key" => "value"; must
     * include a "status" field with either "ok" or "error"
     */
    protected function getDefaultData(array $requestData) : array
    {
        return array();
    }

    /**
     * Sends a JSON response generated with the default form data to fill the
     * placeholders
     *
     * @param array $requestData Data sent in the initial request (i. e. 
     * $uniqueId)
     */
    public function processInitialData(array $requestData): void
    {
        $defaultData = $this->getDefaultData($requestData);

        // Check that default data is OK
        if ($defaultData['status'] === 'error') {
            $this->respondJsonErrorWithCsrf(
                $defaultData['error'],
                $defaultData['messages']
            );
        } else {
            $csrfToken = $this->CsrfGenerateToken();

            $formHiddenData = array(
                self::FORM_ID_FIELD => $this->id,
                self::CSRF_TOKEN_FIELD => $csrfToken
            );

            $all = array_merge($formHiddenData, $defaultData);

            Http::respondJsonOk($all);
        }
    }

    /**
     * Processes a submitted form and sends a JSON response if necessary
     * 
     * @param array $data Data sent in form submission
     */
    abstract public function processSubmit(array $data = array()): void;

    /**
     * Generates specific form inputs as placeholders for AJAX preloading
     * 
     * @return string HTML containing the inputs
     */
    abstract protected function generateFormInputs(): string;

    /**
     * Generates the default HTML Bootstrap modal
     *
     * @return string HTML containing the modal
     */
    public function generateModal(): string
    {
        $inputs = $this->generateFormInputs();

        $formId = $this->id;
        $formName = $this->formName;
        $formIdField = self::FORM_ID_FIELD;
        $csrfTokenField = self::CSRF_TOKEN_FIELD;

        $targetObjectNameData = 
            'data-' . self::TARGET_CLASS_NAME_FIELD .
            '="' . $this->targetObjectName . '"';

        $readOnlyData = 'data-' . self::READ_ONLY_FIELD .
        '="' . ($this->isReadOnly() ? 'true' : 'false') . '"';

        // Optional on success event name
        $onSuccessEventNameData = $this->onSuccessEventName ?
            'data-' . self::ON_SUCCESS_EVENT_NAME_FIELD .
            '="' . $this->onSuccessEventName . '"' : '';

        // Optional on success event target
        $onSuccessEventTargetData = $this->onSuccessEventTarget ?
            'data-' . self::ON_SUCCESS_EVENT_TARGET_FIELD .
            '="' . $this->onSuccessEventTarget . '"' : '';

        $submitUrlData =
            'data-' . self::SUBMIT_URL_FIELD .
            '="' . $this->submitUrl . '"';

        $expectedSubmitMethodData =
            'data-' . self::EXPECTED_SUBMIT_METHOD_FIELD .
            '="' . $this->expectedSubmitMethod . '"';

        if (! $this->isReadOnly()) {
            $footer = <<< HTML
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Continue</button>
            </div>
            HTML;
        } else {
            $footer = '';
        }

        $html = <<< HTML
        <div class="modal fade ajax-modal" data-ajax-form-id="$formId" $readOnlyData $onSuccessEventNameData $onSuccessEventTargetData $submitUrlData $expectedSubmitMethodData $targetObjectNameData tabindex="-1" role="dialog" aria-labelledby="$formId-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" id="$formId">
                    <input type="hidden" name="$formIdField">
                    <input type="hidden" name="$csrfTokenField">
                    <div class="modal-header">
                        <h5 class="modal-title" id="$formId-modal-label">$formName</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        $inputs
                    </div>
                    $footer
                </form>
            </div>
        </div>
        HTML;

        return $html;
    }

    /**
     * Generates a CSRF token and stores it in $_SESSION
     * 
     * @return string Generated token
     */
    private function CsrfGenerateToken(): string
    {
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
        if (! $token) return false;

        if (isset($_SESSION[self::CSRF_PREFIX . '_' . $this->id])
            && $_SESSION[self::CSRF_PREFIX . '_' . $this->id] === $token) {
            
            unset($_SESSION[self::CSRF_PREFIX . '_' . $this->id]);

            return true;
        }

        return false;
    }

    /**
     * Generates a JSON link formalization based in HATEOAS link specification.
     * 
     * @param string $rel
     * @param string $selectType 'multi' for multiple select, 'single' for 
     *                           single select (interpreted in Bootstrap modal
     *                           handling).
     * @param mixed $data
     * 
     * @return stdClass Object ready for JSON serialization.
     */
    public static function generateHateoasSelectLink(string $rel, string $selectType, $data) : stdClass
    {
        $link = new stdClass();

        $link->rel = $rel;
        $link->selectType = $selectType;

        if (! is_array($data)) $data = array($data); // Ensure it is an array

        $link->data = array_values($data); // Ensure array is unkeyed

        return $link;
    }

    /**
     * Generates an Bootstrap button to fire the modal.
     * 
     * @param string|null $content Content of the button.
     * @param int|null $uniqueId
     * @param bool $small
     */
    public function generateButton($content = null, $uniqueId = null, $small = false): string
    {
        $formId = $this->id;
        $buttonContent = $content ? $content : $this->formName;

        $uniqueIdData = $uniqueId ? 'data-ajax-unique-id="' . $uniqueId . '"' : '';

        $smallClass = $small ? 'btn-sm' : '';

        $button = <<< HTML
        <button class="btn-ajax-modal-fire btn $smallClass btn-primary mb-1 mx-1" data-ajax-form-id="$formId" $uniqueIdData>$buttonContent</button>
        HTML;

        return $button;
    }
}