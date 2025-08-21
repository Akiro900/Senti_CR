<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$database = "senti_cr";
$port = "3307";


$conn = new mysqli($servername, $username, $password, $database, $port);


if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
} else {
    echo "¡Conexión exitosa a la base de datos jijiij!";
}
?>
