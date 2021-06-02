<?php

namespace Carrier\Common;

/**
 * Validation utils
 * 
 * @package carrier-core
 * 
 * @author juancrrn
 * 
 * @version 0.0.1
 */

class ValidationUtils
{

	/*
	 *
	 * Regular expressiones
	 * 
	 */

	/**
	 * @var string Regex for UTF-8 extended lowercase range
	 */
	private const UPPER_EXT_REGEX = 'A-Z\x{00C0}-\x{00D6}\x{00D8}-\x{00DD}';

	/**
	 * @var string Regex for UTF-8 extended uppercase range
	 */
	private const LOWER_EXT_REGEX = 'a-z\x{00E0}-\x{00F6}\x{00F8}-\x{00FD}';

	/**
	 * @var string Regex for token
	 */
	private const TOKEN_REGEX = '/^[0-9a-z]{32}$/';

	/**
	 * @var string Regex for password
	 */
	private const PASSWORD_REGEX =
		'/^' .
		'(?=.*[' . self::LOWER_EXT_REGEX . '])' . // At least, 1 extended uppercase letter
		'(?=.*[' . self::UPPER_EXT_REGEX . '])' . // At least, 1 extended lowercase letter
		'(?=.*\d)' . // At least, 1 number
		'.{12,}' . // Any other character to complete a minimum of 12
		'$/u';

	/**
	 * @var string Regex for Spanish id validation digits
	 */
	private const NIF_NIE_DIGITS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    /*
     *
     * Encoding
     * 
     */

	/**
	 * Comprueba si una cadena está codificada como UTF-8.
	 * 
	 * @see https://www.php.net/manual/en/function.mb-check-encoding.php
	 * @see https://tools.ietf.org/html/rfc3629
	 * 
	 * @param string $string
	 * 
	 * @return string
	 */
	public static function checkUtf8(string $string): bool
	{
		$len = strlen($string);
		for ($i = 0; $i < $len; $i++) {
			$c = ord($string[$i]);
			if ($c > 128) {
				if (($c > 247)) return false;
				elseif ($c > 239) $bytes = 4;
				elseif ($c > 223) $bytes = 3;
				elseif ($c > 191) $bytes = 2;
				else return false;
				if (($i + $bytes) > $len) return false;
				while ($bytes > 1) {
					$i++;
					$b = ord($string[$i]);
					if ($b < 128 || $b > 191) return false;
					$bytes--;
				}
			}
		}

		return true;
	}

	/**
	 * Verifica que una cadena está codificada en UTF-8 y, en caso contrario,
     * la convierte.
	 * 
	 * @param null|string $string
	 * 
	 * @return null|string
	 */
	public static function ensureUtf8(null|string $string): null|string
	{
		if (is_null($string)) {
			return null;
		} elseif (self::checkUtf8($string)) {
			return $string;
		} else {
			return mb_convert_encoding($string, mb_detect_encoding(($string)));
		}
	}

	/*
	 * 
	 * Standard formats
	 * 
	 */

	private static function validateNif(string $testString): bool
	{
		$testString = strtoupper($testString);
		
		if (preg_match('/(^[0-9]{8}[A-Z]{1}$)/', $testString)) {
			if ($testString[8] == substr(self::NIF_NIE_DIGITS, substr($testString, 0, 8) % 23, 1)) {				
				return true;
			}
		}

		return false;
	}

	private static function validateNie(string $testString): bool
	{
		$testString = strtoupper($testString);
		
		if (preg_match('/(^[XYZ]{1}[0-9]{7}[A-Z]{1}$)/', $testString)) {
			if ($testString[8] == substr(self::NIF_NIE_DIGITS, substr(str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $testString), 0, 8) % 23, 1)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Valida un identificador gubernamental de tipo NIF o NIE.
	 * 
	 * @param mixed $testItem
	 * 
	 * @return bool
	 */
	public static function validateGovId(mixed $testItem): bool
	{
		if (! is_string($testItem))
			return false;

		return self::validateNif($testItem) || self::validateNie($testItem);
	}

	/*
	 * 
	 * App-specific formats
	 * 
	 */

	/**
	 * Valida un token de recuperación o activación
	 * 
	 * @param mixed $testItem
	 * 
	 * @return bool
	 */
	public static function validateToken(mixed $testItem): bool
	{
		if (! is_string($testItem))
			return false;

		if (! preg_match(self::TOKEN_REGEX, $testItem))
			return false;

		return true;
	}

	/**
	 * Valida una contraseña.
	 * 
	 * Debe tener:
	 * - Al menos, 12 caracteres.
	 * - Al menos, una letra mayúscula (rango extendido).
	 * - Al menos, una letra minúscula (rango extendido).
	 * - Al menos, un número.
	 * - No puede contener otro tipo de símbolos.
	 * 
	 * @param mixed $testItem
	 * 
	 * @return bool
	 */
	public static function validatePassword(mixed $testItem): bool
	{
		if (! is_string($testItem))
			return false;

		if (! preg_match(self::PASSWORD_REGEX, $testItem, $unused, PREG_OFFSET_CAPTURE, 0))
			return false;

		return true;
	}
}