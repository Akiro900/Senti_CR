<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'] ?? '';
    $edad = $_POST['edad'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $genero = $_POST['genero'] ?? '';

    // Formatear el contenido a guardar
    $datos = "Nombre: $nombre\nEdad: $edad\nCorreo: $correo\nGénero: $genero\n-----------------------\n";

    // Guardar en archivo
    file_put_contents("usuarios.txt", $datos, FILE_APPEND);

    // Mostrar confirmación
    echo "<h2>Datos del Usuario Guardados</h2>";
    echo "<p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>";
    echo "<p><strong>Edad:</strong> " . htmlspecialchars($edad) . "</p>";
    echo "<p><strong>Correo:</strong> " . htmlspecialchars($correo) . "</p>";
    echo "<p><strong>Género:</strong> " . htmlspecialchars($genero) . "</p>";
} else {
    echo "<p>No se ha enviado ningun formulario.</p>";
}

//Volver al Menu
echo '<br><br><a href="index.html"><button>Volver al inicio</button></a>';
?>