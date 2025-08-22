<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html"); 
    exit;
}

$nombre = $_SESSION['nombre'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sentí CR - Inicio</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <header>
        <h1>Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h1>
        <h2>Plataforma digital para el apoyo de la salud mental en Costa Rica</h2>
    </header>

    <main>
        <section>
            <p><strong>Sentí CR es un sitio seguro donde su bienestar es nuestra prioridad, a continuacion seleccione la herramienta que desee usar como la autoevaluacion emocional,
             seguimiento de estado de ánimo con el diario, acceso a profesionales y foros anónimos con el fin del apoyo a la
              salud mental en Costa Rica.</strong></p>
            <nav id="boton">
                <a href="test.html">Test Emocional</a> |
                <a href="diario.html">Diario</a> |
                <a href="datos.html">Datos</a> |
                <a href="recursos.html">Recursos</a> |        
                <a href="foro/foro.html">Foro</a>
                <a href="logout.php">Cerrar sesión</a>
            </nav>
        </section>

        <section>
            <h3>¿Qué encontrarás aquí?</h3>
            <ul id="lista">
                <li>Autoevaluaciones emocionales</li>
                <li>Bitácora de estado de ánimo</li>
                <li>Acceso a especialistas</li>
                <li>Foros virtuales anónimos</li>
                <li>Recomendaciones personalizadas</li>
            </ul>
        </section>
    </main>
</body>
</html>

