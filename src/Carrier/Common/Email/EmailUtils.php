<?php

namespace Carrier\Domain\Email;

use Carrier\Common\App;
use Carrier\Common\TemplateUtils;
use Carrier\Common\ValidationUtils;
use Carrier\Domain\User\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Email utils
 *
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class EmailUtils
{

    /**
     * @var string Relative path to email templates
     */
    private const EMAIL_RESOURCES_PATH = 'resources/email';

	/**
	 * @var string X-Mailer header value
	 */
	private const X_MAILER_HEADER_VALUE = 'Carrier by Juan Carrion (juancrrn/carrier)';

	/**
	 * Initializes the PHPMailer configuration with the application instance's 
	 * settings
	 * 
	 * @return PHPMailer
	 */
	public static function initialize()
	{
		$app = App::getSingleton();
        $emailSettings = $app->getEmailSettings();

		$mail = new PHPMailer();

        $mail->CharSet = 'UTF-8';

		if ($app->isDevMode()) {
			$mail->SMTPDebug  = SMTP::DEBUG_CONNECTION;
		} else {
			$mail->SMTPDebug  = SMTP::DEBUG_OFF;
		}

        $mail->CharSet = 'utf-8';
        $mail->XMailer = self::X_MAILER_HEADER_VALUE;
		
		$mail->isSMTP();

		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->Host = $emailSettings['smtp_host'];
		$mail->Port = $emailSettings['smtp_port'];
		$mail->SMTPAuth = true;
		$mail->Username = $emailSettings['smtp_user'];
		$mail->Password = $emailSettings['smtp_password'];

		$mail->setFrom($emailSettings['no_reply'], ValidationUtils::ensureUtf8($app->getName()));
		$mail->addReplyTo($emailSettings['reply_to']);

        $mail->DKIM_domain = $emailSettings['dkim_domain'];
        $mail->DKIM_selector = $emailSettings['dkim_selector'];
        $mail->DKIM_private = $emailSettings['dkim_private_key'];
        $mail->DKIM_passphrase = $emailSettings['dkim_private_key_passphrase'];
		$mail->DKIM_identity = $mail->From;
		$mail->DKIM_copyHeaderFields = false;

		return $mail;
	}

	/**
	 * Sends a generic message, with the commonly used values
	 * 
	 * @param User $recipient
	 * @param string $subject
	 * @param string $templateFileName
	 * @param array $templateFilling
	 * 
	 * @return bool
	 */
	private static function sendGenericMessage(
		User 	$recipient,
		string 	$subject,
		string 	$templateFileName,
		array 	$templateFilling
	): bool
	{
		$app = App::getSingleton();

		$mail = self::initialize();

		$mail->addAddress($recipient->getEmailAddress());
		$mail->isHTML(true);
		$mail->Subject = ValidationUtils::ensureUtf8($subject);

		$basicFilling = array(
			'app-name' => $app->getName(),
			'app-url' => $app->getUrl(),
			'user-first-name' => $recipient->getFirstName()
		);

		$mail->Body = self::generateMailTemplateRender(
			$templateFileName,
			array_merge($basicFilling, $templateFilling)
		);

		$mail->AltBody = self::generateMailTemplateRender(
			$templateFileName . '_plain',
			array_merge($basicFilling, $templateFilling)
		);

		if (! $app->isDevMode()) {
			return $mail->send();
		} else {
			echo 'Requested sending email but app instance is in developement mode.';
			$app->getViewManagerInstance()->addErrorMessage('Se solicitó el envío de un mensaje de correo electrónico, pero la instancia de la aplicación tiene activado el modo de depuración.');

			var_dump($mail);

			return true;
		}
	}

	/**
	 * Renders a mail message body from a template file
	 * 
	 * @param string $fileName
	 * @param string $filling
	 * 
	 * @return string
	 */
	private static function generateMailTemplateRender(
		string $fileName,
		array $filling
	): string
	{
		return TemplateUtils::fillTemplate(
			$fileName,
			$filling,
			realpath(App::getSingleton()->getRoot() . self::EMAIL_RESOURCES_PATH)
		);
	}
}