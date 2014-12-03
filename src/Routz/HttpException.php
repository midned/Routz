<?php

namespace Routz;

/**
 * Represents an Http error to the Routz library
 *
 * @author Alvaro Carneiro <alv.ccbb@gmail.com>
 * @package Routz
 * @license MIT License
 */
class HttpException extends \Exception {
	
	public function __construct($message = null, $code = 500)
	{
		parent::__construct($message, $code);
	}
	
}