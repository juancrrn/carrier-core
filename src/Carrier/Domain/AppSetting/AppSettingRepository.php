<?php

namespace Carrier\Domain\PermissionGroup;

use Carrier\Common\App;
use Carrier\Domain\AppSetting\AppSetting;
use Carrier\Domain\Repository;
use mysqli;

/**
 * App setting repository
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class AppSettingRepository implements Repository
{

    /**
     * @var mysqli $db     Conexión a la base de datos.
     */
    protected $db;

    /**
     * Constructor
     * 
     * @param mysqli $db   Conexión a la base de datos.
     */
    public function __construct(mysqli $db)
    {
        $this->db = App::getSingleton()->getDbConn();
    }

    public function insert(): int
    {
        throw new \Exception('Not implemented');
    }

    public function update(): void
    {
        throw new \Exception('Not implemented');
    }

    public function findById(int $id): bool
    {
        throw new \Exception('Not implemented');
    }

    public function retrieveById(int $id): AppSetting
    {
        throw new \Exception('Not implemented');
    }

    public function retrieveAll(): array
    {
        throw new \Exception('Not implemented');
    }

    public function verifyConstraintsById(int $id): bool|array
    {
        throw new \Exception('Not implemented');
    }

    public function deleteById(int $id): void
    {
        throw new \Exception('Not implemented');
    }
}