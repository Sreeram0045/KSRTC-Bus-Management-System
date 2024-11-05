<?php
$env = parse_ini_file('../.env');

$servername = $env['SERVERNAME'];
$password = $env['PASSWORD'];
$username = $env['USERNAME'];
$db_name = $env['DB_NAME'];
$connection = new mysqli($servername, $username, $password, $db_name);
if ($connection->connect_error) {
    die("Connection failed. Reason: " . $conn->connect_error);
}
