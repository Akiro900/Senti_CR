<?php
session_start(); 
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = $_POST['correo'] ?? '';
    $clave = $_POST['clave'] ?? '';

    $sql = "SELECT id, nombre, clave FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
      
        if (password_verify($clave, $fila['clave'])) {
            $_SESSION['usuario_id'] = $fila['id'];
            $_SESSION['nombre'] = $fila['nombre'];
            //header("Location: index.html"); 
            header("Location: inicio_usuario.php");
            exit;
        } else {
            echo "Contraseña incorrecta";
        }
    } else {
        echo "Usuario no encontrado";
    }

    $stmt->close();
}

$conn->close();
?>
