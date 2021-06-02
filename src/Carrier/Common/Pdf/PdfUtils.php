<?php

namespace Carrier\Domain\Pdf;

use Carrier\Common\App;
use Carrier\Common\TemplateUtils;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;

/**
 * PDF utils
 *
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class PdfUtils
{

    /**
     * @var string Relative path to PDF templates
     */
    private const PDF_RESOURCES_PATH = 'resources/pdf';

	/**
	 * Initializes the mPDF configuration with the application instance's 
	 * settings
	 * 
	 * @return Mpdf
	 */
	public static function initialize(): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'fontDir' => array_merge($fontDirs, [
                App::getSingleton()->getRoot() . self::PDF_RESOURCES_PATH . '/fonts'
            ]),
            'fontdata' => $fontData + [
                'montserrat' => [
                    'R' => 'Montserrat-Regular.ttf',
                    'I' => 'Montserrat-Italic.ttf',
                    'B' => 'Montserrat-Bold.ttf',
                    'BI' => 'Montserrat-BoldItalic.ttf',
                ]
            ],
            'default_font' => 'montserrat',
            'margin-top' => 30
        ]);

        return $mpdf;
    }

    /**
     * Generates a generic PDF document object
     * 
     * @return Mpdf
     */
    private static function generateGenericPdf(): Mpdf
    {
        $app = App::getSingleton();

        $mpdf = self::initialize();
        
        $mpdf->SetHTMLHeader(self::generatePdfTemplateRender(
            'common/part_header',
            array(
                'app-name' => $app->getName(),
                'app-url' => $app->getUrl()
            )
        ));
        
        $mpdf->SetHTMLFooter(self::generatePdfTemplateRender(
            'common/part_footer',
            array(
                'app-name' => $app->getName(),
                'app-url' => $app->getUrl()
            )
        ));
        
        $pdfCss = file_get_contents(realpath(App::getSingleton()->getRoot() . self::PDF_RESOURCES_PATH . '/common/part_styles.css'));

        $mpdf->WriteHTML($pdfCss, HTMLParserMode::HEADER_CSS);
        
        $mpdf->AddPage(mgt: 30, mgb: 20, mgl: 10, mgr: 10);

        return $mpdf;
    }

	/**
	 * Renders a document body from a template file
	 * 
	 * @param string $fileName
	 * @param string $filling
	 * 
	 * @return string
	 */
	private static function generatePdfTemplateRender(
		string $fileName,
		array $filling
	): string
	{
		return TemplateUtils::fillTemplate(
			$fileName,
			$filling,
			realpath(App::getSingleton()->getRoot() . self::PDF_RESOURCES_PATH)
		);
	}
}