<?php
// Inicia la sesión del usuario
session_start();

// Incluye la clase Usuariobd para realizar operaciones con la base de datos
include_once '../data/usuariobd.php';

// Instancia de la clase Usuariobd para manejar operaciones de usuario
$usuariobd = new Usuariobd();

/**
 * Redirige a una URL específica con un mensaje de éxito o error.
 * 
 * @param string $url URL de destino.
 * @param string $success Tipo de mensaje, como 'success' o 'error'.
 * @param string $mensaje Mensaje que se mostrará al usuario.
 * 
 * @return void
 */
function redirigirConMensaje($url, $success, $mensaje)
{
    // Almacena el resultado en la sesión para mostrarlo en la redirección
    $_SESSION['success'] = $success;
    $_SESSION['message'] = $mensaje;

    // Redirige a la URL especificada
    header("Location: $url");
    exit();
}

// Registro de usuario
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['registro'])) {
    // Recibe el email y la contraseña enviados desde el formulario
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Llama al método para registrar el usuario y almacena el resultado
    $resultado = $usuariobd->registrarUsuario($email, $password);

    // Redirige al usuario a la página principal con el resultado del registro
    return redirigirConMensaje('../index.php', $resultado['success'], $resultado['message']);
}

// Inicio de sesión
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])) {
    // Recibe el email y la contraseña enviados desde el formulario
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Llama al método de inicio de sesión y almacena el resultado
    $resultado = $usuariobd->inicioSesion($email, $password);

    // Si el inicio de sesión es exitoso, almacena el ID del usuario en la sesión
    if ($resultado['success'] == 'success') {
        $_SESSION['user_id'] = $resultado['id'];
    }

    // Redirige al usuario a la página principal con el resultado del inicio de sesión
    return redirigirConMensaje('../index.php', $resultado['success'], $resultado['message']);
}

// Recuperación de contraseña
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['recuperar'])) {
    // Recibe el email enviado desde el formulario
    $email = $_POST['email'];

    // Llama al método de recuperación de contraseña y almacena el resultado
    $resultado = $usuariobd->recuperarPassword($email);

    // Redirige al usuario a la página principal con el resultado de la recuperación
    return redirigirConMensaje('../index.php', $resultado['success'], $resultado['message']);
}
