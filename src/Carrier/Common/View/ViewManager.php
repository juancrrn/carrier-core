<?php 

namespace Carrier\Common\View;

use InvalidArgumentException;
use Carrier\Common\App;
use Carrier\Common\TemplateUtils;
use Carrier\Common\View\Common\FooterPartView;
use Carrier\Common\View\Common\HeaderPartView;

/**
 * View-related functionality and management
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class ViewManager
{

    /**
     * @var string Current page name
     */
    private $currentPageName;

    /**
     * @var string Current page identifier
     */
    private $currentPageId;

    /**
     * @var null|ViewModel View currently being rendered
     */
    private $currentRenderingView = null;

    /**
     * @var string Relative path to all templates
     */
    private const RESOURCES_PATH = 'resources';

    /**
     * @var string Relative path to HTML template element templates
     */
    private const HTML_TEMPLATE_ELEMENT_PATH = 'html_templates';

    /**
     * @var string Key of $_SESSION where messages are stored
     */
    private const SESSION_MESSAGES = 'carrier_session_messages';

    /**
     * @var array HTML template elements to render with the view
     */
    private $templateElements;

    /**
     * Standard constructor
     */
    public function __construct()
    {
        $this->templateElements = [];
    }

    /*
     * 
     * Current page
     * 
     */

    /**
     * Establece el nombre y el id de la página actual.
     * 
     * @param string $nombre Nombre de la página actual.
     * @param string $id Identificador de la página actual.
     */
    private function setCurrentPage(string $name, string $id): void
    {
        $this->currentPageName = $name;
        $this->currentPageId = $id;
    }

    /**
     * Devuelve el nombre de la página actual.
     * 
     * @return string Nombre de la página actual.
     */
    public function getCurrentPageName(): string
    {
        return $this->currentPageName;
    }

    /**
     * Devuelve el id de la página actual.
     * 
     * @return string Id de la página actual.
     */
    public function getCurrentPageId(): string
    {
        return $this->currentPageId;
    }

    public function getCurrentRenderingView(): null|ViewModel
    {
        return $this->currentRenderingView;
    }

    /*
     * 
     * Cabecera y pie de página
     * 
     */

    /**
     * Generates the HTML header part and prints it
     */
    private function injectHeader(): void
    {
        $headerPartClassName = App::getSingleton()->getHeaderPartClassName();
        (new $headerPartClassName())->processContent();
    }

    /**
     * Generates the HTML footer part and prints it
     */
    private function injectFooter(): void
    {
        $footerPartClassName = App::getSingleton()->getFooterPartClassName();
        (new $footerPartClassName())->processContent();
    }

    /*
     * 
     * User messages
     * 
     */

    /**
     * Añade un mensaje de error a la cola de mensajes para el usuario.
     * 
     * @param string $message Mensaje a mostrar al usuario.
     * @param string $header_location Opcional para redirigir al mismo tiempo 
     * que se encola el mensaje.
     */
    public function addErrorMessage(string $mensaje, string $header_location = null): void
    {
        $_SESSION[self::SESSION_MESSAGES][] = [
            "tipo" => "error",
            "contenido" => $mensaje
        ];

        if ($header_location !== null) {
            header("Location: " . App::getSingleton()->getUrl() . $header_location);
            die();
        }
    }
    
    /**
     * Añade un mensaje de éxito a la cola de mensajes para el usuario.
     * 
     * @param string $message Mensaje a mostrar al usuario.
     * @param string $header_location Opcional para redirigir al mismo tiempo 
     * que se encola el mensaje.
     */
    public function addSuccessMessage(string $mensaje, string $header_location = null): void
    {
        $_SESSION[self::SESSION_MESSAGES][] = [
            "tipo" => "exito",
            "contenido" => $mensaje
        ];

        if ($header_location !== null) {
            header("Location: " . App::getSingleton()->getUrl() . $header_location);
            die();
        }
    }

    /**
     * Comprueba si hay algún mensaje de error en la cola de mensajes.
     */
    public function anyErrorMessages(): bool
    {
        if (! empty($_SESSION[self::SESSION_MESSAGES])) {
            foreach ($_SESSION[self::SESSION_MESSAGES] as $mensaje) {
                if ($mensaje["tipo"] == "error") {
                    return true;
                }
            }
        }

        return false;
    }

    /*
     * 
     * User messages (visible parts: toasts)
     * 
     */

    /**
     * Genera un elemento Bootstrap toast para mostrar un mensaje.
     */
    public function generateToast(string $tipo, string $contenido): string
    {
        $app = App::getSingleton();

        return $this->fillTemplate(
            self::HTML_TEMPLATE_ELEMENT_PATH . DIRECTORY_SEPARATOR . 'template_toast',
            [
                'autohide'  => $app->isDevMode() ? 'false' : 'true',
                'type'      => $tipo,
                'app-name'  => $app->getName(),
                'content'   => $contenido
            ]
        );
    }

    /**
     * Imprime todos los mensajes de la cola de mensajes.
     */
    private function printToasts(): void
    {
        echo '<div id="toasts-container" aria-live="polite" aria-atomic="true">';

        if (! empty($_SESSION[self::SESSION_MESSAGES])) {
            foreach ($_SESSION[self::SESSION_MESSAGES] as $clave => $mensaje) {
                echo $this->generateToast($mensaje['tipo'], $mensaje['contenido']);

                // Eliminar mensaje de la cola tras mostrarlo.
                unset($_SESSION[self::SESSION_MESSAGES][$clave]);
            }
        }

        echo '</div>';
    }

    /**
     * Registra la plantilla de los toasts para el navegador e imprime las
     * que haya disponibles.
     */
    private function addToastTemplateAndPrint(): void
    {
        $this->addTemplateElement(
            'toast',
            'template_toast',
            [
                'autohide'  => '',
                'type'      => '',
                'app-name'  => '',
                'content'   => ''
            ]
        );

        $this->printToasts();
    }

    /*
     * 
     * HTML template elements (<template>)
     * 
     */

    /**
     * Añade un elemento plantilla de HTML (<template>) para que luego sea
     * añadido al código HTML y pueda ser clonado por un script en el navegador.
     */
    public function addTemplateElement(
        string $htmlId,
		string $fileName,
		array $filling
    ): void
    {
        $this->templateElements[] = [
            'html_id' => $htmlId,
            'file_name' => $fileName,
            'filling' => $filling
        ];
    }

    /**
     * Comprueba si hay algún elemento template guardado para inyectar.
     * 
     * @return bool
     */
    public function anyTemplateElements(): bool
    {
        if (! empty($this->templateElements)) 
            return true;

        return false;
    }

    /**
     * Genera un elemento <template> para luego insertarlo en el HTML.
     */
    private function generateTemplateElementRender(
        string $htmlId,
		string $fileName,
		array $filling
    ): string
    {
        $filledTemplate = $this->fillTemplate(
            self::HTML_TEMPLATE_ELEMENT_PATH . DIRECTORY_SEPARATOR . $fileName,
            $filling
        );

        return <<< HTML
        <template id="$htmlId">
            $filledTemplate
        </template>
        HTML;
    }

    /**
     * Imprime todos los elementos <template> precargados.
     */
    private function printTemplateElements(): void
    {
        if ($this->anyTemplateElements()) {
            foreach ($this->templateElements as $element) {
                echo $this->generateTemplateElementRender(
                    $element['html_id'],
                    $element['file_name'],
                    $element['filling']
                );
            }
        }
    }

    /*
     * 
     * Renderizado de vistas
     * 
     */

    /**
     * Dibuja una vista completa y detiene la ejecución del script.
     */
    public function render(ViewModel $vista): void
    {
        $this->currentRenderingView = $vista;

        $this->setCurrentPage($vista->getName(), $vista->getId());
        
        $this->injectHeader();

        $this->addToastTemplateAndPrint();

        echo <<< HTML
        <section id="main-container" class="container my-4 px-4">
            <article>
        HTML;

        $vista->processContent();

        echo <<< HTML
            </article>
        </section>
        HTML;

        $this->printTemplateElements();

        $this->injectFooter();

        //die(); // TODO No necesario
    }

    /*
     * 
     * Elementos de menú
     * 
     */

    /**
     * Genera un item de una lista no ordenada (<li> de una <ul>) para el menú 
     * principal lateral.
     * 
     * Por defecto añade la ruta de la URL principal al principio del enlace.
     * 
     * @param string $url
     * @param string $titulo
     * @param string $paginaId Identificador de la página de destino, para saber
     *                         si es la actual.
     */
    public function generateMainMenuLink(string $viewClass): string
    {
        if (! class_exists($viewClass)) {
            throw new InvalidArgumentException('Specified view class ($viewClass = ' . $viewClass . ') does not exist.');
        }

        if (
            ! defined($viewClass . '::VIEW_ID') ||
            ! defined($viewClass . '::VIEW_NAME') ||
            ! defined($viewClass . '::VIEW_ROUTE')
        ) {
            throw new InvalidArgumentException('Specified view class ($viewClass = ' . $viewClass . ') must have VIEW_ID, VIEW_NAME and VIEW_ROUTE constants defined and public.');
        }

        $viewId = $viewClass::VIEW_ID;
        $viewName = $viewClass::VIEW_NAME;
        $viewRoute = $viewClass::VIEW_ROUTE;

        $appUrl = App::getSingleton()->getUrl();

        $activeClass = $this->getCurrentPageId() === $viewId ? 'active' : '';

        $classAttr = 'class="nav-link ' . $activeClass . '"';
        $hrefAttr = 'href="' . $appUrl . $viewRoute . '"';

        $a = <<< HTML
        <li class="nav-item"><a $classAttr $hrefAttr>$viewName</a></li>
        HTML;

        return $a;
    }

    /**
     * Genera un item de una lista no ordenada (<li> de una <ul>) para el menú 
     * de sesión de usuario.
     * 
     * Por defecto añade la ruta de la URL principal al principio del enlace.
     * 
     * @param string $content
     * @param string|null $paginaId Identificador de la página de destino, para 
     *                              saber si es la actual.
     */
    public function generateUserMenuItem(string $content): string
    {
        return <<< HTML
        <span class="nav-item">$content</span>
        HTML;
    }

    /*
     *
     * Plantillas
     * 
     */

    /**
     * @param string $fileName
     * @param array $filling
     * 
     * @return string
     */
    public function fillTemplate(
		string $fileName,
		array $filling
    ): string
    {
        $app = App::getSingleton();

        return TemplateUtils::fillTemplate(
            $fileName,
            $filling,
            realpath($app->getRoot() . self::RESOURCES_PATH)
        );
    }

    /**
     * Imprime una plantilla rellenada.
     * 
     * Ver ViewManager::fillTemplate().
     */
    public function renderTemplate(
		string $fileName,
		array $filling
	): void
	{
        echo $this->fillTemplate(
            $fileName,
            $filling
        );
	}
}