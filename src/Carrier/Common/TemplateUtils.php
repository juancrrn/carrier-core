<?php

namespace Carrier\Common;

use RuntimeException;

/**
 * Template utils
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class TemplateUtils
{

    /**
     * @var string HTML template files extension
     */
    private const TEMPLATE_FILE_EXTENSION = '.html';

    /**
     * Genera un contenido visual final a partir de un fichero de plantilla y
     * algunos valores.
     * 
     * @param string $fileName  Nombre del fichero, dentro del directorio de
     *                          recursos.
     * @param array $filling    Array clave-valor con los nombres de los
     *                          placeholders y sus valores.
     * 
     *                          Se recomiendan nombres de placeholders de tipo
     *                          #nombre-compuesto#.
     * 
     *                          Solo pueden darse valores de tipo cadena de
     *                          texto.
     */
    public static function fillTemplate(
		string $fileName,
		array $filling,
        string $resourcesPath
	): string
	{
        $fullPath = $resourcesPath . DIRECTORY_SEPARATOR . $fileName . self::TEMPLATE_FILE_EXTENSION;
		
		if (! file_exists($fullPath)) {
            throw new RuntimeException('Template file not found: ' . $fullPath . '.');
        }
        
        $file = file_get_contents($fullPath);
        
        if (empty($file)) {
            throw new RuntimeException('Empty template file: ' . realpath($fullPath) . '.');
		} else {
            // Preparar los nombres de los placeholders.

            $names = array_keys($filling);

            for ($i = 0; $i < count($names); $i++) {
                $names[$i] = '#' . $names[$i] . '#';
            }

			return str_replace($names, array_values($filling), $file);
		}
	}

    /**
     * Genera una cadena HTMl con etiquetas option para un select.
     * 
     * @param array $valueContentArray  Array clave-valor con valor-contenido
     *                                  para las option.
     * @param string $selectedValue     Valor seleccionado por defecto.
     */
    public static function generateSelectOptions(
        array $valueContentArray,
        ?string $selectedValue = null
    ): string
    {
        $html = '';

        foreach ($valueContentArray as $value => $content) {
            $selected = $value == $selectedValue ? ' selected="selected"' : '';

            $html .= <<< HTML
            <option value="$value"$selected>$content</option>
            HTML;
        }

        return $html;
    }
}