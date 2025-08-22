<?php
include "db.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $edad = $_POST['edad'] ?? '';
    $genero = $_POST['genero'] ?? '';

    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre, correo, clave, edad, genero) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $nombre, $correo, $clave_hash, $edad, $genero);


    if ($stmt->execute()) {
    header("Location: registro_exitoso.php");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

    $stmt->close();
}

$conn->close();
?>
