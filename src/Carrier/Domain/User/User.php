<?php

namespace Carrier\Domain\User;

use DateTime;
use InvalidArgumentException;
use JsonSerializable;
use Carrier\Common\CommonUtils;
use Carrier\Domain\GenericHumanModel;

/**
 * User
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class User implements JsonSerializable
{

    /**
     * Posibles estados de un usuario
     */
    public const STATUS_INACTIVE    = 'user_status_inactive';
    private const STATUS_INACTIVE_TITLE = 'Usuario desactivado';
    private const STATUS_INACTIVE_DESC = 'El usuario acaba de crearse o ha sido desactivado manualmente, y tiene que activarse a través de un enlace en un mensaje de correo electrónico recibido.';

    public const STATUS_ACTIVE      = 'user_status_active';
    private const STATUS_ACTIVE_TITLE = 'Usuario activado';
    private const STATUS_ACTIVE_DESC = 'El usuario está activado y puede acceder correctamente.';

    public const STATUS_RESET       = 'user_status_reset';
    private const STATUS_RESET_TITLE = 'Usuario en restablecimiento de contraseña';
    private const STATUS_RESET_DESC = 'El usuario ha solicitado un restablecimiento de contraseña y debe procesarlo a través de un enlace en un mensaje de correo electrónico recibido.';

    public const STATUSES = [
        self::STATUS_INACTIVE,
        self::STATUS_ACTIVE,
        self::STATUS_RESET
    ];

    /**
     * Nombres cortos de grupos de permisos nativos (native permission group,
     * NPG).
     */
    public const NPG_APP_MANAGER = 'app-manager';

    /**
     * Identificador interno.
     * 
     * @var int $id
     */
    private $id;

    /**
     * Número de identificación fiscal (NIF) o número de identificación de
     * extranjero (NIE).
     * 
     * El usuario puede no disponer de este identificador, en cuyo caso es null.
     * 
     * @var null|string $govId
     */
    private $govId;

    /**
     * Nombre.
     * 
     * @var string $firstName
     */
    private $firstName;

    /**
     * Apellidos.
     * 
     * @var string $lastName
     */
    private $lastName;

    /**
     * Fecha de nacimiento.
     * 
     * En formato Carrier\Common\CommonUtils::MYSQL_DATE_FORMAT.
     * 
     * @var DateTime $birthDate
     */
    private $birthDate;

    /**
     * Contraseña hasheada.
     * 
     * Por ser un dato sensible, no se almacenará excepto cuando sea necesario.
     * 
     * @var string $hashedPassword
     */
    // private $hashedPassword;

    /**
     * Dirección de correo electrónico.
     * 
     * @var string $emailAddress
     */
    private $emailAddress;

    /**
     * Número de teléfono.
     * 
     * @var string $phoneNumber
     */
    private $phoneNumber;

    /**
     * Identificador del usuario representante.
     * 
     * @var null|int $representativeId
     */
    private $representativeId;

    /**
     * Fecha y hora de registro.
     * 
     * En formato Carrier\Common\CommonUtils::MYSQL_DATETIME_FORMAT.
     * 
     * @var DateTime $registrationDate
     */
    private $registrationDate;

    /**
     * Fecha y hora del último inicio de sesión.
     * 
     * En formato Carrier\Common\CommonUtils::MYSQL_DATETIME_FORMAT.
     * 
     * Puede ser null si el usuario nunca ha iniciado sesión.
     * 
     * @var null|DateTime $lastLoginDate
     */
    private $lastLoginDate;

    /**
     * Token utilizado para la activación y para el restablecimiento de la
     * contraseña.
     * 
     * @var string $token
     */
    private $token;

    /**
     * Estado del usuario.
     * 
     * De self::STATUSES.
     * 
     * @var string $status
     */
    private $status;

    /**
     * Grupos de permisos asociados al usuario, si se ha solicitado su carga.
     * 
     * En caso de existir, es un array de PermissionGroup.
     * 
     * @var null|array $permissionGroups
     */
    private $permissionGroups;

    public function __construct(
        int         $id,
        ?string     $govId,
        string      $firstName,
        string      $lastName,
        DateTime    $birthDate,
        // string   $hashedPassword,
        string      $emailAddress,
        string      $phoneNumber,
        ?int        $representativeId,
        DateTime    $registrationDate,
        ?DateTime   $lastLoginDate,
        ?string     $token,
        string      $status,
        ?array      $permissionGroups
    )
    {
        $this->id               = $id;
        $this->govId            = $govId;
        $this->firstName        = $firstName;
        $this->lastName         = $lastName;
        $this->birthDate        = $birthDate;
        $this->emailAddress     = $emailAddress;
        $this->phoneNumber      = $phoneNumber;
        $this->representativeId = $representativeId;
        $this->registrationDate = $registrationDate;
        $this->lastLoginDate    = $lastLoginDate;
        $this->token            = $token;
        $this->status           = $status;
        $this->permissionGroups = $permissionGroups;
    }

    public static function constructFromMysqliObject(object $mysqli_object): self
    {
        $birthDate = DateTime::createFromFormat(
            CommonUtils::MYSQL_DATE_FORMAT,
            $mysqli_object->birth_date
        );

        $registrationDate = DateTime::createFromFormat(
            CommonUtils::MYSQL_DATETIME_FORMAT,
            $mysqli_object->registration_date
        );

        $lastLoginDate =
            isset($mysqli_object->last_login_date) ?
            DateTime::createFromFormat(
                CommonUtils::MYSQL_DATETIME_FORMAT,
                $mysqli_object->last_login_date
            )
            : null;

        return new self(
            $mysqli_object->id,
            $mysqli_object->gov_id,
            $mysqli_object->first_name,
            $mysqli_object->last_name,
            $birthDate,
            $mysqli_object->email_address,
            $mysqli_object->phone_number,
            $mysqli_object->representative_id,
            $registrationDate,
            $lastLoginDate,
            $mysqli_object->token,
            $mysqli_object->status,
            null
        );
    }

    /*
     *
     * JSON
     * 
     */

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'govId' => $this->govId,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'birthDate' => $this->birthDate,
            'emailAddress' => $this->emailAddress,
            'phoneNumber' => $this->phoneNumber,
            'representativeId' => $this->representativeId,
            'registrationDate' => $this->registrationDate,
            'lastLoginDate' => $this->lastLoginDate,
            'token' => $this->token,
            'status' => $this->status
        ];
    }

    /*
     *
     * Otros
     * 
     */

    /**
     * Comprueba si el usuario tiene un permiso especificado, es decir, si entre
     * sus grupos de permisos, figura uno en concreto.
     * 
     * @param string $testPermissionGroupShortName  Nombre corto del grupo de
     *                                              permisos a comprobar.
     * 
     * @return bool 
     */
    public function hasPermission(string $testPermissionGroupShortName): bool
    {
        if (is_array($this->permissionGroups))
            foreach ($this->permissionGroups as $permissionGroup)
                if ($permissionGroup->getShortName() == $testPermissionGroupShortName)
                    return true;

        return false;
    }
    
    /**
     * Genera un token para activar un usuario o restablecer su contraseña.
     * 
     * @param string $govId NIF o NIE para generar el token único.
     * 
     * @return string Token generado.
     */
    public static function generateToken(string $govId): string
    {
        return md5(uniqid($govId, true));
    }

    /*
     * 
     * Setters
     * 
     */

    public function setPermissionGroups(array $permissionGroups): void
    {
        $this->permissionGroups = $permissionGroups;
    }

    /*
     * 
     * Getters
     * 
     */

    public function getId(): int
    {
        return $this->id;
    }

    public function getGovId(?bool $escaped = false): null|string
    {
        if ($escaped) {
            if ($this->govId) {
                return mb_strtoupper($this->govId);
            } else {
                return '(No especificado)';
            }
        }
        
        return $this->govId;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getBirthDate(): DateTime
    {
        return $this->birthDate;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getRepresentativeId(): null|int
    {
        return $this->representativeId;
    }

    public function getRegistrationDate(): DateTime
    {
        return $this->registrationDate;
    }

    public function getLastLoginDate(): null|DateTime
    {
        return $this->lastLoginDate;
    }

    public function getToken(): null|string
    {
        return $this->token;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPermissionGroups(): null|array
    {
        return $this->permissionGroups;
    }

    /*
     *
     * Estados
     * 
     */

    /**
     * Transforma el estado a una representación humanamente legible.
     * 
     * @param string $status
     * 
     * @return GenericHumanModel
     */
    public static function statusToHuman(string $status): GenericHumanModel
    {
        if (! in_array($status, self::STATUSES))
            throw new InvalidArgumentException('Invalid satus.');

        switch ($status) {
            case self::STATUS_INACTIVE:
                return new GenericHumanModel(
                    self::STATUS_INACTIVE, self::STATUS_INACTIVE_TITLE, self::STATUS_INACTIVE_DESC
                );
            case self::STATUS_ACTIVE:
                return new GenericHumanModel(
                    self::STATUS_ACTIVE, self::STATUS_ACTIVE_TITLE, self::STATUS_ACTIVE_DESC
                );
            case self::STATUS_RESET:
                return new GenericHumanModel(
                    self::STATUS_RESET, self::STATUS_RESET_TITLE, self::STATUS_RESET_DESC
                );
        }
    }
}