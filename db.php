<?php
// db.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'mysql';
$port = 3306;
$user = 'senti_cr_user';
$pass = '4!rAJMr%*3PyAQGD';
$db   = 'senti_cr';

$attempts = 10;
while ($attempts--) {
    try {
        $conn = new mysqli($host, $user, $pass, $db, $port);
        $conn->set_charset('utf8mb4');
        break;
    } catch (mysqli_sql_exception $e) {
        if ($attempts === 0) throw $e;
        usleep(500000); // 0.5s
    }
}
