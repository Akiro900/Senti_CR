<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $comentario = $_POST['comentario'] ?? '';

    // Formatear el contenido a guardar
    $entrada = "Fecha: $fecha\nEstado: $estado\nComentario: $comentario\n-----------------------\n";

    // Guardar en archivo
    file_put_contents("diario.txt", $entrada, FILE_APPEND);

    // Mostrar confirmación
    echo "<h2>Entrada del Diario Guardada</h2>";
    echo "<p><strong>Fecha:</strong> " . htmlspecialchars($fecha) . "</p>";
    echo "<p><strong>Estado emocional:</strong> " . htmlspecialchars($estado) . "</p>";
    echo "<p><strong>Comentario:</strong> " . nl2br(htmlspecialchars($comentario)) . "</p>";
} else {
    echo "<p>No se ha enviado ningun formulario.</p>";
}

//Volver al menu
echo '<br><br><a href="inicio_usuario.php"><button>Volver al inicio</button></a>';
?>