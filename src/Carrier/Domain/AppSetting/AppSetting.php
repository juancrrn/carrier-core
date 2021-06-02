<?php

namespace Carrier\Domain\AppSetting;

class AppSetting
{

    /**
     * @var int $id
     */
    private $id;

    /**
     * @var string $key
     */
    private $key;

    /**
     * @var string $value
     */
    private $value;

    public function __construct(
        int     $id,
        string  $key,
        string  $value
    )
    {
        $this->id       = $id;
        $this->key      = $key;
        $this->value    = $value;
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

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}