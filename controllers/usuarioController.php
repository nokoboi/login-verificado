<?php

session_start();

include_once '../data/usuariobd.php';

$usuariobd = new Usuariobd();

function redirigirConMensaje($url, $success, $mensaje){
    // Almacena el resultado en la sesion
    $_SESSION['success'] = $success;
    $_SESSION['message'] = $mensaje;

    // Realizar la redireccion al index
    header("Location: $url");
    exit();

}

// Registro usuario
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['registro'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $resultado = $usuariobd->registrarUsuario($email,$password);

    return redirigirConMensaje('../index.php', $resultado['success'],$resultado['message']);
}

// Inicio de sesión
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $resultado = $usuariobd->inicioSesion($email,$password);
    if($resultado['success'] == 'success'){
        $_SESSION['user_id'] = $resultado['id'];
    }

    return redirigirConMensaje('../index.php', $resultado['success'],$resultado['message']);    
}

// Recuperacion de contraseña
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['recuperar'])){
    $email = $_POST['email'];

    $resultado = $usuariobd->recuperarPassword($email);
    return redirigirConMensaje('../index.php', $resultado['success'],$resultado['message']);
}
