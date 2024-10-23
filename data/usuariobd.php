<?php
include_once 'config.php';

class Usuariobd{
    private $conn;
    private $url = 'http://localhost/login-verificado';

    public function __construct(){
        $this->conn = new mYsqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

        if($this->conn->connect_error){
            die("Error en la conexion: ".$this->conn->connect_error);
        }
    }

    // funcion para enviar correo simulado
    public function enviarCorreoSimulado($destinatario, $asunto, $mensaje){
        $archivo_log = __DIR__ . '/correos_simulados.log';
        $contenido = "Fecha: ".date('Y-m-d H:i:s' . "\n");
        $contenido .= "Para: $destinatario\n";
        $contenido .= "Asunto: $asunto";
        $contenido .= "Mensaje: \n$mensaje\n";
        $contenido .= "__________________________________\n\n";

        file_put_contents($archivo_log, $contenido, FILE_APPEND);

        return ["success"=>true, "message" => "Registro con éxito, por favor verifica tu correo."];
    }

    // Generar un token aleatorio
    public function generarToken(){
        return bin2hex(random_bytes(32));
    }

    public function registrarUsuario($email, $password, $verificado = 0){
        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = $this->generarToken();

        $sql = "INSERT INTO usuarios (email,password,token,verificado) values (?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        //los tipos tienen que coincidir, s de string, i de int y asi con los demás tipos
        $stmt->bind_param("sssi",$email,$password,$token,$verificado);

        if($stmt->execute()){
            $mensaje = "Por favor, verifica tu cuenta haciendo click en este enlace: $this->url/verificar.php?token=$token";
            return $this->enviarCorreoSimulado($email, "Verificacion de cuenta", $mensaje);
        }else{
            return ["success"=>false, "message" => "Error en el registro: ".$stmt->error];
        }
    }

    public function verificarToken($token){
        // Buscar al usuario con el token recibido
        $sql = "SELECT id FROM usuarios where token = ? and verificado = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s",$token);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            // token es valido actualizamos el estado de verificacion
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            $update_sql_token = "UPDATE usuarios set verificado=1, token=null where id=?";
            $updtate_stmt = $this->conn->prepare($update_sql_token);
            $updtate_stmt->bind_param("i",$user_id);

            $resultado = ["succes"=>'error', "message" => "Hubo un error al verificar tu cuenta por favor, intentalo de nuevo"];

            if($updtate_stmt->execute()){
                $resultado = ["succes"=>'success', "message" => "Tu cuenta ha sido verificada con éxito. Ahora puedes iniciar sesión."];
            }            
        }
        return $resultado;

    }
}