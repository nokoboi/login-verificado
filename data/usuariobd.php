<?php
include_once 'config.php';
include_once 'enviarCorreos.php';

/**
 * Clase Usuariobd
 * 
 * Esta clase gestiona las operaciones relacionadas con usuarios en la base de datos,
 * incluyendo registro, verificación, inicio de sesión y recuperación de contraseñas.
 */
class Usuariobd
{
    private $conn; // Conexión a la base de datos
    private $url = 'http://localhost/login-verificado'; // URL base para verificar y recuperar cuentas

    /**
     * Constructor que inicializa la conexión a la base de datos.
     * Termina la ejecución si la conexión falla.
     */
    public function __construct()
    {
        $this->conn = new mYsqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            die("Error en la conexion: " . $this->conn->connect_error);
        }
    }

    /**
     * Simula el envío de un correo guardando el mensaje en un archivo de log.
     * 
     * @param string $destinatario Dirección de correo del destinatario.
     * @param string $asunto Asunto del correo.
     * @param string $mensaje Contenido del mensaje.
     * @return array Resultado de la simulación del envío del correo.
     */
    public function enviarCorreoSimulado($destinatario, $asunto, $mensaje)
    {
        $archivo_log = __DIR__ . '/correos_simulados.log';
        $contenido = "Fecha: " . date('Y-m-d H:i:s' . "\n");
        $contenido .= "Para: $destinatario\n";
        $contenido .= "Asunto: $asunto";
        $contenido .= "Mensaje: \n$mensaje\n";
        $contenido .= "__________________________________\n\n";

        file_put_contents($archivo_log, $contenido, FILE_APPEND);

        return ["success" => true, "message" => "Registro con éxito, por favor verifica tu correo."];
    }

    /**
     * Genera un token aleatorio para acciones de verificación y recuperación.
     * 
     * @return string Token generado.
     */
    public function generarToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Registra un nuevo usuario en la base de datos.
     * 
     * @param string $email Email del usuario.
     * @param string $password Contraseña del usuario.
     * @param int $verificado Estado de verificación del usuario (0 no verificado, 1 verificado).
     * @return array Resultado del registro, incluyendo éxito o error y mensaje.
     */
    public function registrarUsuario($email, $password, $verificado = 0)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = $this->generarToken();

        // Verificar si el correo ya existe
        $existe = $this->existeEmail($email);

        $sql = "INSERT INTO usuarios (email, password, token, verificado) VALUES(?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $email, $password, $token, $verificado);

        if (!$existe) {
            if ($stmt->execute()) {
                $mensaje = "Por favor, verifica tu cuenta haciendo clic en este enlace: $this->url/verificar.php?token=$token";
                $mensaje = Correo::enviarCorreo($email, "Cliente", "Verificación de cuenta", $mensaje);
            } else {
                $mensaje = ["success" => false, "message" => "Error en el registro: " . $stmt->error];
            }
        } else {
            $mensaje = ["success" => false, "message" => "Ya existe una cuenta con ese email"];
        }

        return $mensaje;
    }

    /**
     * Verifica el token de un usuario para confirmar su cuenta.
     * 
     * @param string $token Token de verificación.
     * @return array Resultado de la verificación, incluyendo éxito o error y mensaje.
     */
    public function verificarToken($token)
    {
        $sql = "SELECT id FROM usuarios WHERE token = ? AND verificado = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            $update_sql_token = "UPDATE usuarios SET verificado=1, token=null WHERE id=?";
            $updtate_stmt = $this->conn->prepare($update_sql_token);
            $updtate_stmt->bind_param("i", $user_id);

            $resultado = ["succes" => 'error', "message" => "Hubo un error al verificar tu cuenta por favor, intentalo de nuevo"];

            if ($updtate_stmt->execute()) {
                $resultado = ["succes" => 'success', "message" => "Tu cuenta ha sido verificada con éxito. Ahora puedes iniciar sesión."];
            }
        } else {
            $resultado = ["success" => 'error', "message" => "Token no válido"];
        }
        return $resultado;
    }

    /**
     * Inicia sesión de un usuario comprobando su email y contraseña.
     * 
     * @param string $email Email del usuario.
     * @param string $password Contraseña del usuario.
     * @return array Resultado del inicio de sesión, incluyendo éxito o error y mensaje.
     */
    public function inicioSesion($email, $password)
    {
        $sql = "SELECT id, email, password, verificado FROM usuarios WHERE email=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultado = ['success' => 'info', "message" => 'Usuario no encontrado'];

        if ($row = $result->fetch_assoc()) {
            if ($row['verificado'] == 1 && password_verify($password, $row['password'])) {
                $resultado = ["success" => "success", "message" => "Has iniciado sesión con " . $email, "id" => $row['id']];
                $sql = "UPDATE usuarios SET ultima_conexion=CURRENT_TIMESTAMP WHERE id=?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $row['id']);
                $stmt->execute();
            }
        } else {
            $resultado = ['success' => 'error', 'message' => 'Credenciales inválidas o cuenta no verificada'];
        }

        return $resultado;
    }

    /**
     * Verifica si un email ya existe en la base de datos.
     * 
     * @param string $email Email a verificar.
     * @return bool True si el email existe, False en caso contrario.
     */
    public function existeEmail($email)
    {
        $check_sql = "SELECT id FROM usuarios WHERE email = ?";
        $check_stmt = $this->conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();

        $result = $check_stmt->get_result();

        return $result->num_rows > 0;
    }

    /**
     * Inicia el proceso de recuperación de contraseña generando y enviando un token.
     * 
     * @param string $email Email del usuario.
     * @return array Resultado de la solicitud de recuperación, con mensaje de éxito o error.
     */
    public function recuperarPassword($email)
    {
        $existe = $this->existeEmail($email);

        $resultado = ["success" => 'info', "message" => "El correo electrónico proporcionado no corresponde a ningún usuario registrado."];

        if ($existe) {
            $token = $this->generarToken();

            $sql = "UPDATE usuarios SET token_recuperacion = ? WHERE email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $token, $email);

            if ($stmt->execute()) {
                $mensaje = "Para restablecer tu contraseña, haz clic en este enlace: $this->url/restablecer.php?token=$token";
                $mensaje = Correo::enviarCorreo($email, "Cliente", "Restablecer Contraseña", $mensaje);
                $resultado = ["success" => 'success', "message" => "Se ha enviado un enlace de recuperación a tu correo"];
            } else {
                $resultado = ["success" => 'error', "message" => "Error al procesar la solicitud"];
            }
        }
        return $resultado;
    }

    /**
     * Restablece la contraseña del usuario utilizando un token de recuperación.
     * 
     * @param string $token Token de recuperación.
     * @param string $nuevaPassword Nueva contraseña del usuario.
     * @return array Resultado de la operación, incluyendo éxito o error y mensaje.
     */
    public function restablecerPassword($token, $nuevaPassword)
    {
        $password = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $sql = "SELECT id FROM usuarios WHERE token_recuperacion=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            $sql = "UPDATE usuarios SET password=?, token_recuperacion=null WHERE id=?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $password, $user_id);

            if ($stmt->execute()) {
                $resultado = ["success" => 'success', "message" => "Contraseña restablecida con éxito. Ahora puedes iniciar sesión."];
            } else {
                $resultado = ["success" => 'error', "message" => "Hubo un problema al restablecer la contraseña. Inténtalo de nuevo más tarde."];
            }
        } else {
            $resultado = ["success" => 'error', "message" => "El token no es válido o ha expirado."];
        }
        return $resultado;
    }
}
