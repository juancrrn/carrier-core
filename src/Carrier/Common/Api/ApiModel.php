<?php

namespace Carrier\Common\Api;

/**
 * API model
 * 
 * Forms must extend this class, which also provides handling functionality.
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

abstract class ApiModel
{

    abstract public function consume(object $requestContent): void;
}