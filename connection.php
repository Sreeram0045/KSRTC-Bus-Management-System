<?php
    $env=parse_ini_file('.env');

    $servername=$env['SERVERNAME'];
    $password=$env['PASSWORD'];
    $username=$env['USERNAME'];
    // $db_name;
    $connection=new mysqli($servername,$username,$password);
    if ($connection->connect_error) {
        die("Connection failed. Reason: " . $conn->connect_error);
    }
    echo "Connected!\n";
?>