<?php
include_once 'config.php';

class Usuariobd
{
    private $conn;
    private $url = 'http://localhost/login-verificado';

    public function __construct()
    {
        $this->conn = new mYsqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            die("Error en la conexion: " . $this->conn->connect_error);
        }
    }

    // funcion para enviar correo simulado
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

    // Generar un token aleatorio
    public function generarToken()
    {
        return bin2hex(random_bytes(32));
    }

    public function registrarUsuario($email, $password, $verificado = 0)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = $this->generarToken();

        $sql = "INSERT INTO usuarios (email,password,token,verificado) values (?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        //los tipos tienen que coincidir, s de string, i de int y asi con los demás tipos
        $stmt->bind_param("sssi", $email, $password, $token, $verificado);

        if ($stmt->execute()) {
            $mensaje = "Por favor, verifica tu cuenta haciendo click en este enlace: $this->url/verificar.php?token=$token";
            return $this->enviarCorreoSimulado($email, "Verificacion de cuenta", $mensaje);
        } else {
            return ["success" => false, "message" => "Error en el registro: " . $stmt->error];
        }
    }

    public function verificarToken($token)
    {
        // Buscar al usuario con el token recibido
        $sql = "SELECT id FROM usuarios where token = ? and verificado = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // token es valido actualizamos el estado de verificacion
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            $update_sql_token = "UPDATE usuarios set verificado=1, token=null where id=?";
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

    public function inicioSesion($email, $password)
    {
        $sql = "SELECT id, email, password, verificado from usuarios where email=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultado = ['success' => 'info', "message" => 'Usuario no encontrado'];

        if ($row = $result->fetch_assoc()) {
            if ($row['verificado'] == 1 && password_verify($password, $row['password'])) {
                $resultado = ["success" => "success", "message" => "Has iniciado sesión con " . $email, "id" => $row['id']];
                // Actualiza la fecha del ultimo inicio de sesión
                $sql = "UPDATE usuarios set ultima_conexion=CURRENT_TIMESTAMP where id=?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $row['id']);
                $stmt->execute();
            }
        } else {
            // El correo existe pero la contraseña no es válida
            $resultado = ['success' => 'error', 'message' => 'Credenciales inválida o cuenta no verificada'];
        }

        return $resultado;
    }

    public function recuperarPassword($email)
    {
        // Verificamos si existe el correo en la base da datos
        $check_sql = "SELECT id from usuarios where email=?";
        $check_stmt = $this->conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();

        $result = $check_stmt->get_result();

        $resultado = ['success' => 'info', "message" => "El correo electrónico no existe."];

        // Si el correo existe en la base de datos generamos un token
        if ($result->num_rows > 0) {
            $token = $this->generarToken();
            $sql_update = "UPDATE usuarios set token_recuperacion=? where email=?";
            $update_stmt = $this->conn->prepare($sql_update);
            $update_stmt->bind_param("ss",$token, $email);
            
            if($update_stmt->execute()){
                $mensaje = "Para restablecer la contraseña, haz click en este enlace: $this->url/restablecer.php?token=$token";
                $this->enviarCorreoSimulado($email, "Recuperacion de contraseña",$mensaje);
                $resultado = ["success"=>"success", "message"=>"Se ha enviado un enlace a tu correo"];
            }else{
                $resultado = ["success"=>"error", "message"=>"Ha habido un error al procesar la solicitud"];
            }
        }
        return $resultado;
    }

    public function restablecerPassword($token,$nuevaPassword){
        // Encriptamos la contraseña
        $password = password_hash($nuevaPassword,PASSWORD_DEFAULT);
        // Buscamos al usuario con el token proporcionado
        $sql = "SELECT id from usuarios where token_recuperacion=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s",$token);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultado = ["success"=>"info", "message"=>"El token de recuperacion no es válido o ya ha sido utilizado"];

        if($result->num_rows===1){
            $row=$result->fetch_assoc();
            $user_id = $row['id'];

            // Actualizamos la contraseña y quitamos el token de recuperacion
            $update_sql = "UPDATE usuarios set password=?, token_recuperacion=null where id=?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("si",$password,$user_id);

            if($update_stmt->execute()){
                $resultado = ["success"=>"success", "message"=>"La contraseña se ha actualizado correctamente"];
            }else{
                $resultado = ["success"=>"error", "message"=>"Hubo un error al actualizar la contraseña, inténtelo más tarde."];
            }
        }

        return $resultado;
    }
}