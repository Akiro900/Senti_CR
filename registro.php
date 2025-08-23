<?php
include "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    // edad: NULL si viene vacía
    $edad = (isset($_POST['edad']) && $_POST['edad'] !== '') ? (int)$_POST['edad'] : null;

    // genero: NO VIENE en tu form -> ponlo como NULL
    $genero = null;

    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO usuarios (nombre, correo, clave, edad, genero)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // pasar NULL es válido; MySQL guardará NULL
        $stmt->bind_param("sssis", $nombre, $correo, $clave_hash, $edad, $genero);
        $stmt->execute();

        header("Location: registro_exitoso.php");
        exit;
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            echo "Ese correo ya está registrado.";
        } else {
            echo "Error al registrar: " . $e->getMessage();
        }
    }
}
$conn->close();
