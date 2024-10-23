<?php
session_start();

if(isset($_SESSION['success']) && isset($_SESSION['message'])){
    $mensaje = $_SESSION['message'];
    $succes = $_SESSION['success'];

    // Limpia el mensaje de la sesion para que no se muestre de nuevo
    unset($_SESSION['message']);
    unset($_SESSION['success']);

    // Mostramos el mensaje
    echo "<div class='mensaje $succes'>$mensaje</div>";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="container">
        <form method="POST" action="controllers/usuarioController.php">
            <h2>Login</h2>
            <input type="email" required placeholder="Correo electrónico">
            <input type="password" required placeholder="Contraseña">
            <a class="abrir-modal-recuperar">Recuperar Contraseña</a>
            <input type="submit" name="login" value="Iniciar sesión">
        </form>
        <p>¿Necesitas una cuenta? <a class="abrir-modal-registro">Registrarse</a></p>
    </div>

    <div id="miModalRecuperar" class="modal">
        <div class="modal-contenido">
            <span class="cerrarRecuperar">&times;</span>
            <h2>Recuperar Contraseña</h2>
            <form method="POST" action="controllers/usuarioController.php">
                <input type="email" name="email" required placeholder="Correo electrónico">
                <input type="submit" name="recuperar" value="Recuperar contraseña">
            </form>
        </div>
    </div>

    <div id="miModalRegistro" class="modal">
        <div class="modal-contenido">
            <span class="cerrarRegistro">&times;</span>
            <h2>Registro</h2>
            <form method="POST" action="controllers/usuarioController.php">
                <input type="email" name="email" required placeholder="Correo electrónico">
                <input type="password" name="password" required placeholder="Contraseña">
                <input type="submit" name="registro" value="Registrarse">
            </form>
        </div>
    </div>

    <script src="js/index.js"></script>
</body>

</html>