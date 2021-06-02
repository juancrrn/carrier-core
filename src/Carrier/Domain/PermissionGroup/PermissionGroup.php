<?php

namespace Carrier\Domain\PermissionGroup;

use DateTime;
use Carrier\Common\CommonUtils;

/**
 * Permission group
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class PermissionGroup
{

    /**
     * Posibles tipos de grupo de permisos
     */
    public const TYPE_DEFAULT = 'permission_group_type_default';

    public const TYPES = array(
        self::TYPE_DEFAULT
    );

    /**
     * @var int $id
     */
    private $id;

    /**
     * De self::TYPES.
     * 
     * @var string $type
     */
    private $type;

    /**
     * @var string $shortName
     */
    private $shortName;

    /**
     * @var string $fullName
     */
    private $fullName;

    /**
     * @var string $description
     */
    private $description;

    /**
     * @var null|int $parent
     */
    private $parent;

    /**
     * @var DateTime $creationDate
     */
    private $creationDate;

    /**
     * @var int $creatorId
     */
    private $creatorId;

    public function __construct(
        int         $id,
        string      $type,
        string      $shortName,
        string      $fullName,
        string      $description,
        ?int        $parent,
        DateTime    $creationDate,
        int         $creatorId
    )
    {
        $this->id           = $id;
        $this->type         = $type;
        $this->shortName    = $shortName;
        $this->fullName     = $fullName;
        $this->description  = $description;
        $this->parent       = $parent;
        $this->creationDate = $creationDate;
        $this->creatorId    = $creatorId;
    }

    public static function constructFromMysqliObject(object $mysqli_object): self
    {
        $creationDate = DateTime::createFromFormat(
            CommonUtils::MYSQL_DATETIME_FORMAT,
            $mysqli_object->creation_date
        );

        return new self(
            $mysqli_object->id,
            $mysqli_object->type,
            $mysqli_object->short_name,
            $mysqli_object->full_name,
            $mysqli_object->description,
            $mysqli_object->parent,
            $creationDate,
            $mysqli_object->creator_id
        );
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParent(): int
    {
        return $this->parent;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function getCreatorId(): int
    {
        return $this->creatorId;
    }
}