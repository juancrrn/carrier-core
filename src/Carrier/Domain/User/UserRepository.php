<?php

namespace Carrier\Domain\User;

use DateTime;
use Carrier\Common\App;
use Carrier\Common\CommonUtils;
use Carrier\Domain\PermissionGroup\PermissionGroupRepository;
use Carrier\Domain\Repository;

/**
 * User repository
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class UserRepository implements Repository
{

    /**
     * @var \mysqli $db     Conexión a la base de datos.
     */
    protected $db;

    /**
     * Constructor
     * 
     * @param \mysqli $db   Conexión a la base de datos.
     */
    public function __construct(\mysqli $db)
    {
        $this->db = App::getSingleton()->getDbConn();
    }

    /**
     * Inserta un elemento a la base de datos.
     * 
     * @param User $user
     * @param string|null $hashedPassword
     * 
     * @return bool|int
     */
    public function insert(User $user, ?string $hashedPassword = null): bool|int
    {
        $query = <<< SQL
        INSERT INTO
            users
            (
                id,
                gov_id,
                first_name,
                last_name,
                birth_date,
                hashed_password,
                email_address,
                phone_number,
                representative_id,
                registration_date,
                last_login_date,
                token,
                status
            )
        VALUES
            ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )
        SQL;

        $stmt = $this->db->prepare($query);
        
        $id = $user->getId();
        $govId = $user->getGovId(); // Nullable
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $birthDate = $user->getBirthDate()
            ->format(CommonUtils::MYSQL_DATE_FORMAT);
        //$hashedPassword = ...;
        $emailAddress = $user->getEmailAddress();
        $phoneNumber = $user->getPhoneNumber();
        $representativeId = $user->getRepresentativeId(); // Nullable
        $registrationDate = $user->getRegistrationDate()
            ->format(CommonUtils::MYSQL_DATETIME_FORMAT);
        $lastLoginDate = $user->getLastLoginDate() ? // Nullable
            $user->getLastLoginDate()
            ->format(CommonUtils::MYSQL_DATETIME_FORMAT) :
            null;
        $token = $user->getToken(); // Nullable
        $status = $user->getStatus();

        $stmt->bind_param(
            'isssssssissss',
            $id,
            $govId,
            $firstName,
            $lastName,
            $birthDate,
            $hashedPassword,
            $emailAddress,
            $phoneNumber,
            $representativeId,
            $registrationDate,
            $lastLoginDate,
            $token,
            $status
        );
        
        $result = $stmt->execute();

        $id = $this->db->insert_id;

        $stmt->close();

        if ($result) {
            return $id;
        } else {
            return false;
        }
    }

    public function update(): bool|int
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Actualiza la fecha de último inicio de sesión.
     * 
     * @requires    Existe un usuario en la base de datos con el identificador
     *              especificado.
     * 
     * @param int $id
     * @param DateTime $newLastLoginDate
     * 
     * @return bool
     */
    public function updateLastLoginDateById(int $id, ?DateTime $newLastLoginDate = null): bool
    {
        if (! $newLastLoginDate)
            $newLastLoginDate = new DateTime;

        $newLastLoginDate = $newLastLoginDate->format(CommonUtils::MYSQL_DATETIME_FORMAT);

        $query = <<< SQL
        UPDATE
            users
        SET
            last_login_date = ?
        WHERE
            id = ?
        SQL;

        $stmt = $this->db->prepare($query);
        
        $stmt->bind_param('si', $newLastLoginDate, $id);
        
        $result = $stmt->execute();

        $stmt->close();

        return $result;
    }

    /**
     * Genera un token de recuperación para un usuario, lo guarda en su fila
     * de la base de datos y lo devuelve.
     * 
     * Además, si el usuario estaba en User::STATUS_ACTIVE, lo modifica a
     * User::STATUS_RESET. Si estaba en User::STATUS_INACTIVE, continúa igual.
     * 
     * @requires    Existe un usuario en la base de datos con el identificador
     *              especificado.
     * 
     * @param int $id
     * 
     * @return bool|string  False si no se pudo completar la operación o el
     *                      token en caso positivo.
     */
    public function generateAndUpdateTokenAndStatusById(int $id): bool|string
    {
        $user = $this->retrieveById($id);

        $token = User::generateToken($user->getGovId());

        $status = $user->getStatus() == User::STATUS_INACTIVE ?
            User::STATUS_INACTIVE : User::STATUS_RESET;

        $query = <<< SQL
        UPDATE
            users
        SET
            token = ?,
            status = ?
        WHERE
            id = ?
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssi', $token, $status, $id);
        
        $result = $stmt->execute();

        $stmt->close();

        if ($result == true) {
            return $token;
        } else {
            return false;
        }
    }

    /**
     * Termina el proceso de activación o de restablecimiento de contraseña con
     * token, dejando al usuario en User::STATUS_ACTIVE y con su nueva
     * contraseña.
     * 
     * @requires    Existe un usuario en la base de datos con el identificador
     *              especificado.
     * 
     * @param int $id
     * @param string $hashedPassword
     * 
     * @return bool Resultado de la operación.
     */
    public function finalizeTokenProcessById(int $id, string $hashedPassword): bool
    {
        $status = User::STATUS_ACTIVE;
        
        $query = <<< SQL
        UPDATE
            users
        SET
            hashed_password = ?,
            token = null,
            status = ?
        WHERE
            id = ?
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssi', $hashedPassword, $status, $id);
        
        $result = $stmt->execute();

        $stmt->close();

        return $result;
    }

    public function search(string $keyword, ?bool $loadModel = false): array
    {
        $keyword = '%' . $keyword . '%';

        $query = <<< SQL
        SELECT 
            id
        FROM
            users        
        WHERE
            gov_id LIKE ?
        OR
            first_name LIKE ?
        OR
            last_name LIKE ?
        OR
            email_address LIKE ?
        OR
            phone_number LIKE ?
        LIMIT 8
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            'sssss',
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword
        );
        $stmt->execute();

        $result = $stmt->get_result();

        $items = [];

        while ($object = $result->fetch_object()) {
            $items[] = $object->id;
        }

        if ($loadModel) {
            $return = [];

            foreach ($items as $userId) {
                $return[] = $this->retrieveById($userId);
            }
        } else {
            $return = $items;
        }

        $stmt->close();

        return $return;
    }

    public function findById(int $testId): bool
    {
        $query = <<< SQL
        SELECT 
            id
        FROM
            users
        WHERE
            id = ?
        LIMIT 1
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $testId);
        $stmt->execute();

        $result = $stmt->get_result();

        $return = $result->num_rows == 1;

        $stmt->close();

        return $return;
    }

    /**
     * Comprueba si existe un usuario en base a un token.
     * 
     * @param string $testToken
     * 
     * @return bool|int El identificador en caso de existir o false en otro
     *                  caso.
     */
    public function findByToken(string $testToken): bool|int
    {
        $query = <<< SQL
        SELECT 
            id
        FROM
            users
        WHERE
            token = ?
        LIMIT 1
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $testToken);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows != 1) {
            $return = false;
        } else {
            $return = $result->fetch_object()->id;
        }

        $stmt->close();

        return $return;
    }

    /**
     * Comprueba si existe un usuario en base a un NIF o NIE.
     * 
     * @param string $testGovId
     * 
     * @return bool|int El identificador en caso de existir o false en otro
     *                  caso.
     */
    public function findByGovId(string $testGovId): bool|int
    {
        $testGovId = mb_strtolower($testGovId);

        $query = <<< SQL
        SELECT 
            id
        FROM
            users
        WHERE
            gov_id = ?
        LIMIT 1
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $testGovId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows != 1) {
            $return = false;
        } else {
            $return = $result->fetch_object()->id;
        }

        $stmt->close();

        return $return;
    }

    public function retrieveById(int $id, ?bool $loadPermissionGroups = false): User
    {
        $query = <<< SQL
        SELECT
            id,
            gov_id,
            first_name,
            last_name,
            birth_date,
            hashed_password,
            email_address,
            phone_number,
            representative_id,
            registration_date,
            last_login_date,
            token,
            status
        FROM
            users
        WHERE
            id = ?
        LIMIT 1
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $mysqli_object = $result->fetch_object();

        $element = User::constructFromMysqliObject($mysqli_object);

        if ($loadPermissionGroups)
            $element->setPermissionGroups($this->retrievePermissionGroupsById($element->getId()));

        $stmt->close();

        return $element;
    }

    public function retrieveJustHashedPasswordById(int $id): string
    {
        var_dump($id);
        $query = <<< SQL
        SELECT
            hashed_password
        FROM
            users
        WHERE
            id = ?
        LIMIT 1
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        
        $element = $result->fetch_object()->hashed_password;

        $stmt->close();

        return $element;
    }

    public function retrieveAll(?bool $loadPermissionGroups = false): array
    {
        $query = <<< SQL
        SELECT
            id,
            gov_id,
            first_name,
            last_name,
            birth_date,
            hashed_password,
            email_address,
            phone_number,
            representative_id,
            registration_date,
            last_login_date,
            token,
            status
        FROM
            users
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $elements = [];

        while ($mysqli_object = $result->fetch_object()) {
            $currentElement = User::constructFromMysqliObject($mysqli_object);

            if ($loadPermissionGroups)
                $currentElement->setPermissionGroups($this->retrievePermissionGroupsById($currentElement->getId()));

            $elements[] = $currentElement;
        }

        $stmt->close();

        return $elements;
    }

    public function verifyConstraintsById(int $id): bool|array
    {
        throw new \Exception('Not implemented');
    }

    public function deleteById(int $id): void
    {
        throw new \Exception('Not implemented');
    }

    /*
     *
     * Grupos de permisos
     * 
     */

    public function retrievePermissionGroupsById(int $userId): array
    {
        $query = <<< SQL
        SELECT
            permission_group_id
        FROM
            user_permission_group_links
        WHERE
            user_id = ?
        SQL;

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $elements = [];

        $permissionGroupRepository = new PermissionGroupRepository($this->db);

        while($mysqli_object = $result->fetch_object()) {
            $elements[] = $permissionGroupRepository->retrieveById($mysqli_object->permission_group_id);
        }

        $stmt->close();

        return $elements;
    }

    /**
     * Crea un enlace entre un usuario y un grupo de permisos.
     * 
     * @requires Existe un usuario con el identificador especificado.
     * @requires Existe un grupo de permisos con el identificador especificado.
     * 
     * @param int $userId
     * @param int $permissionGroupId
     * 
     * @return bool Resultado de la operación.
     */
    public function createPermissionGroupLink(int $userId, int $permissionGroupId): bool
    {
        $query = <<< SQL
        INSERT INTO
            user_permission_group_links
            (
                user_id,
                permission_group_id
            )
        VALUES
            ( ?, ? )
        SQL;

        $stmt = $this->db->prepare($query);

        $stmt->bind_param(
            'ii',
            $userId,
            $permissionGroupId
        );
        
        $result = $stmt->execute();

        $stmt->close();

        return $result;
    }
}