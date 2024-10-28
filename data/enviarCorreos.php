<?php
// Incluye la configuración necesaria desde config.php
include_once 'config.php';

// Importa las clases PHPMailer y Exception del paquete PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requiere los archivos necesarios de PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Define la clase Correo para manejar el envío de correos electrónicos
class Correo
{

    // Método estático para enviar un correo electrónico
    public static function enviarCorreo($forEmail, $forName, $asunto, $body)
    {

        // Crea una instancia de PHPMailer en modo "true" para permitir excepciones
        $mail = new PHPMailer(true);

        try {
            // Configuración de depuración detallada (nivel 2)
            $mail->SMTPDebug = 2; // Habilita la salida de depuración detallada
            $mail->Debugoutput = function ($str, $level) {
                error_log("PHPMailer: $str");
            };

            // Configuración del servidor SMTP
            $mail->isSMTP();                // Utiliza SMTP para enviar
            $mail->Host = MAIL_HOST;  // Especifica el servidor SMTP
            $mail->SMTPAuth = true;       // Habilita la autenticación SMTP
            $mail->Username = MAIL_USER;  // Nombre de usuario de SMTP
            $mail->Password = MAIL_PASS;  // Contraseña de SMTP
            $mail->From = MAIL_USER;  // Dirección de correo del remitente
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Habilita encriptación TLS
            $mail->Port = 587;        // Puerto para SMTP seguro

            // Configuración de codificación
            $mail->CharSet = 'UTF-8';       // Codificación UTF-8 para caracteres especiales
            $mail->Encoding = 'base64';     // Codificación base64 para el cuerpo del mensaje

            // Configuración del remitente y destinatario
            $mail->setFrom(MAIL_USER, '=?UTF-8?B?' . base64_encode('Administración') . '?='); // Remitente
            $mail->addAddress($forEmail, '=?UTF-8?B?' . base64_encode($forName) . '?=');       // Destinatario

            // Configuración del contenido del correo
            $mail->isHTML(true); // Permite el formato HTML en el cuerpo del correo
            $mail->Subject = '=?UTF-8?B?' . base64_encode($asunto) . '?='; // Asunto en UTF-8 y codificado en base64
            $mail->Body = '
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            </head>
            <body>
                ' . nl2br(htmlspecialchars($body)) . '
            </body>
            </html>'; // Cuerpo en HTML del correo, con el contenido escapado y los saltos de línea convertidos

            $mail->AltBody = $body; // Versión en texto plano del cuerpo del correo

            // Envía el correo y verifica el resultado
            if (!$mail->send()) {
                error_log("Error al enviar correo: " . $mail->ErrorInfo); // Log del error en caso de fallo
                return ["success" => false, "message" => 'El correo no pudo ser enviado: ' . $mail->ErrorInfo];
            } else {
                error_log("Correo enviado exitosamente a: $forEmail"); // Log de éxito
                return ["success" => true, "message" => "Registro exitoso. Por favor, verifica tu correo."];
            }
        } catch (Exception $e) {
            // Captura errores durante el envío y devuelve un mensaje de error
            return ["success" => false, "message" => 'Error al enviar el formulario'];
        }
    }
}
