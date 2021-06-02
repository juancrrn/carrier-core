<?php

namespace Carrier\Common;

use Carrier\Domain\User\User;

/**
 * Session management functionality
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class SessionManager
{

    /**
     * @var string Key of $_SESSION where session data is stored
     */
    const SESSION_NAME = "carrier_session";

    /**
     * @var null|User Logged-in user
     */
    private $loggedInUser = null;

    /**
     * Initialize user session handling
     */
    public function init(): void
    {
        session_start();
        
        if (
            isset($_SESSION[self::SESSION_NAME]) &&
            is_object($_SESSION[self::SESSION_NAME]) &&
            $_SESSION[self::SESSION_NAME] instanceof User
        ) {
            $this->loggedInUser = $_SESSION[self::SESSION_NAME];
        }
    }

    /**
     * Do a user login
     */
    public function doLogIn(User $user): void
    {
        // $user->updateLastSession();

        session_regenerate_id(true);

        $this->loggedInUser = $user;
        $_SESSION[self::SESSION_NAME] = $user;
    }

    /**
     * Do a user logout
     */
    public function doLogOut(): void
    {
        $this->loggedInUser = null;
        unset($_SESSION[self::SESSION_NAME]);

        session_destroy();

        session_start();
    }

    /**
     * Checks if a user session was started
     */
    public function isLoggedIn(): bool
    {
        return ! is_null($this->loggedInUser);
    }

    /**
     * Returns the logged-in user
     */
    public function getLoggedInUser() : User
    {
        return $this->loggedInUser;
    }

    /**
     * Requiere que haya una sesión iniciada para acceder al contenido.
     * En caso de que no haya ninguna sesión iniciada, redirige al inicio de
     * sesión.
     * 
     * @param bool $api Indica si se está utilizano el método en la API, por lo 
     *                  que, en lugar de redirigir, debería mostrar un error 
     *                  HTTP.
     */
    public function requireLoggedIn($api = false): void
    {
        if (! $this->isLoggedIn()) {
            if (! $api) {
                CommonUtils::ddl(null, null);
                //Vista::encolaMensajeError('Necesitas haber iniciado sesión para acceder a este contenido.', '/sesion/iniciar/');
            } else {
                CommonUtils::ddl(null, null);
                //HTTP::apiRespondError(401, ['No autenticado.']); // HTTP 401 Unauthorized (unauthenticated).
            }
        }
    }

    /**
     * Requiere que NO haya una sesión iniciada para acceder al contenido.
     * En caso de que haya alguna sesión iniciada, redirige a inicio.
     * 
     * @param bool $api Indica si se está utilizano el método en la API, por lo 
     *                  que, en lugar de redirigir, debería mostrar un error 
     *                  HTTP.
     */
    public function requireNotLoggedIn($api = false): void
    {
        if ($this->isLoggedIn()) {
            $viewManager = App::getSingleton()->getViewManagerInstance();

            if (! $api) {
                $viewManager->addErrorMessage('No puedes acceder a esta página habiendo iniciado sesión.', '');
            } else {
                CommonUtils::ddl(null, null);
                //HTTP::apiRespondError(409, ['No debería estar autenticado.']); // HTTP 409 Conflict.
            }
        }
    }

    /**
     * Requiere que haya una sesión iniciada de un usuario de tipo específico.
     * 
     * @param string $testType  Tipo de usuario especificado. Se deben utilizar
     *                          las constantes de tipo definidas en la parte
     *                          superior de la clase Domain\User\User.
     * @param bool $negate      Permite negar el tipo de usuario especificado.
     * @param bool $api         Indica si se está utilizano el método en la API,
     *                          por lo que, en lugar de redirigir, debería
     *                          mostrar un error HTTP.
     */
    public function requirePermissionGroups(array $testPermissionGroups, ?bool $negate = false, ?bool $api = false): void
    {
        $this->requireLoggedIn($api);

        $missingPermissionGroups = [];

        foreach ($testPermissionGroups as $testPermissionGroup)
            if (! $this->getLoggedInUser()->hasPermission($testPermissionGroup))
                $missingPermissionGroups[] = $testPermissionGroup;

        if (! empty($missingPermissionGroups)) {
            $app = App::getSingleton();

            if (! $api) {
                $app->getViewManagerInstance()
                    ->addErrorMessage('No tienes permiso para acceder a este contenido.', '');
            } else {
                $app->getApiManagerInstance()
                    ->apiRespond(403, ['No autorizado.']);
            }
        }
    }
}