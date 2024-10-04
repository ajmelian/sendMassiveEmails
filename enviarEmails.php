<?php

/**
 * Script para el envío masivo de correos electrónicos en lotes a direcciones con dominio específico.
 * Utiliza SMTP para el envío y registra el resultado en un archivo CSV.
 *
 * Forma de uso:
 * 1. Crear un archivo .env en el mismo directorio del script con las siguientes variables:
 * 2. Ejecutar el script desde la línea de comandos: php enviarEmails.php
 *
 * Este script procesa un archivo de texto con emails (db/emails.txt), filtra los dominios específicos
 * y envía correos en lotes para evitar el bloqueo del servidor SMTP.
 *
 * Autor: Aythami Melian Perdomo
 * Fecha de creación: 2024-10-04
 * Licencia: GPL-3.0
 */

define('EMAIL_FILE', 'db/emails.txt'); // Cambiado a la ruta db
define('EMAIL_DOMAIN', 'gmail.com');
define('EMAIL_TEMPLATE_FILE', 'cuerpo-email-marketing.html');
define('SEND_INTERVAL', 2);
define('BATCH_SIZE', 1000);
define('OUTPUT_LOG', 'log/' . date('Ymd') . '_log_envios.csv'); // Cambiado a la ruta log con fecha

// Cargar las librerías de PHPMailer y Dotenv desde el directorio "ext"
require 'ext/PHPMailer/src/PHPMailer.php';
require 'ext/PHPMailer/src/SMTP.php';
require 'ext/PHPMailer/src/Exception.php';
require 'ext/Dotenv/src/Dotenv.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Procesa el archivo de emails y ejecuta una función callback para cada email válido del dominio especificado.
 *
 * @param string $filename Nombre del archivo que contiene los emails.
 * @param callable $callback Función callback a ejecutar por cada email válido.
 */
function processEmails(string $filename, callable $callback): void {
    $file = fopen($filename, 'r');
    if (!$file) {
        echo "Error al abrir el archivo de emails.\n";
        return;
    }

    while (($line = fgets($file)) !== false) {
        $email = extractEmail(trim($line));
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && hasDomain($email, EMAIL_DOMAIN)) {
            $callback($email);
        }
    }
    fclose($file);
}

/**
 * Extrae el email de una línea de texto, ignorando cualquier texto después de ':'.
 *
 * @param string $line Línea de texto a procesar.
 * @return ?string Email extraído o null si no se encuentra.
 */
function extractEmail(string $line): ?string {
    $parts = explode(':', $line);
    return $parts[0] ?? null;
}

/**
 * Verifica si un email pertenece al dominio especificado.
 *
 * @param string $email Dirección de correo electrónico a verificar.
 * @param string $domain Dominio a buscar en el email.
 * @return bool Verdadero si el email pertenece al dominio; falso en caso contrario.
 */
function hasDomain(string $email, string $domain): bool {
    return stripos($email, $domain) !== false;
}

/**
 * Envía un correo electrónico a una dirección dada usando SMTP y registra el intento.
 *
 * @param string $recipient Email del destinatario.
 * @param string $htmlContent Contenido del mensaje en formato HTML.
 * @param int $maxRetries Número máximo de intentos de envío en caso de error.
 * @return array Resultados del envío (email, estado, debug).
 */
function sendEmail(string $recipient, string $htmlContent, int $maxRetries = 3): array {
    $mailer = new PHPMailer(true);
    $result = ["email" => $recipient, "enviado" => "NO", "debug" => ""];

    while ($maxRetries > 0) {
        try {
            configureMailer($mailer, $recipient, $htmlContent);
            $mailer->send();
            $result["enviado"] = "SI";
            $result["debug"] = "Correo enviado exitosamente";
            break;
        } catch (Exception $e) {
            $result["debug"] = "Error: " . $mailer->ErrorInfo;
            $maxRetries--;
            sleep(1); // Espera antes de reintentar
        }
    }
    return $result;
}

/**
 * Configura los parámetros necesarios para el envío de email a través de PHPMailer utilizando las variables del archivo .env.
 *
 * @param PHPMailer $mailer Instancia de PHPMailer.
 * @param string $recipient Email del destinatario.
 * @param string $htmlContent Contenido del mensaje en formato HTML.
 */
function configureMailer(PHPMailer $mailer, string $recipient, string $htmlContent): void {
    $mailer->isSMTP();
    $mailer->Host = getenv('SMTP_HOST');
    $mailer->SMTPAuth = true;
    $mailer->Username = getenv('SMTP_USERNAME');
    $mailer->Password = getenv('SMTP_PASSWORD');
    $mailer->SMTPSecure = getenv('SMTP_SECURE') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mailer->Port = (int) getenv('SMTP_PORT');

    $mailer->setFrom(getenv('SMTP_USERNAME'), 'Nombre');
    $mailer->addAddress($recipient);
    $mailer->isHTML(true);
    $mailer->Subject = 'Asunto';
    $mailer->Body = $htmlContent;
}

/**
 * Registra el resultado de un intento de envío en un archivo CSV.
 *
 * @param array $result Array con los detalles del resultado (email, estado, debug).
 * @param string $filename Nombre del archivo CSV de salida.
 */
function logResult(array $result, string $filename): void {
    $file = fopen($filename, 'a');
    if ($file) {
        fputcsv($file, $result);
        fclose($file);
    } else {
        echo "Error al abrir el archivo CSV de salida.\n";
    }
}

/**
 * Envío y registro de un lote de emails en el archivo log.
 *
 * @param array $emails Lista de correos electrónicos a enviar.
 * @param string $htmlContent Contenido del mensaje en formato HTML.
 */
function sendAndLogBatch(array $emails, string $htmlContent): void {
    foreach ($emails as $email) {
        $result = sendEmail($email, $htmlContent);
        logResult([$result["email"], $result["enviado"], $result["debug"]], OUTPUT_LOG);
    }
}

/**
 * Carga el contenido HTML de la plantilla de email.
 *
 * @param string $filename Nombre del archivo de plantilla HTML.
 * @return string Contenido del mensaje o error si no se carga.
 */
function loadHtmlTemplate(string $filename): string {
    $content = file_get_contents($filename);
    if ($content === false) {
        die("Error al cargar el archivo de plantilla de email.\n");
    }
    return $content;
}

// Cargar el contenido del mensaje
$emailHtmlContent = loadHtmlTemplate(EMAIL_TEMPLATE_FILE);

// Procesar y enviar emails en lotes
$emailBatch = [];
$counter = 0;

processEmails(EMAIL_FILE, function(string $email) use (&$emailBatch, &$counter, $emailHtmlContent) {
    $emailBatch[] = $email;
    $counter++;

    if (count($emailBatch) >= BATCH_SIZE) {
        sendAndLogBatch($emailBatch, $emailHtmlContent);
        $emailBatch = [];
        sleep(SEND_INTERVAL);
    }
});

// Procesar el último lote si contiene emails
if (!empty($emailBatch)) {
    sendAndLogBatch($emailBatch, $emailHtmlContent);
}
