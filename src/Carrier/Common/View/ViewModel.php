<?php

namespace Carrier\Common\View;

/**
 * View model
 * 
 * Each view class must contain the following public constants:
 * - VIEW_ID
 * - VIEW_NAME
 * - VIEW_ROUTE
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

abstract class ViewModel
{
	protected $name;
	protected $id;

	/**
	 * Procesa la lógica de la vista en el elemento <article>, que deberá 
	 * imprimir HTML y realizar lo que sea conveniente.
	 */
	abstract public function processContent(): void;

	public function getName(): string
	{
		return $this->name;
	}

	public function getId(): string
	{
		return $this->id;
	}
}