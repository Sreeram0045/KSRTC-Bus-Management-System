<?php
// connection is required
require_once "../connection.php";
// session started
session_start();

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$username = $data['username'];
$password = $data['password'];
$response = [];

if ($username === "root") {
    $query = "SELECT * FROM credentials WHERE username = ? AND password = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // set session variable
        $_SESSION['admin'] = $row['username'];
        $_SESSION['logged_in'] = "true";
        // response set
        $response['success'] = 'true';
        $response['role'] = 'admin';
        $response['message'] = 'Login successful';
    } else {
        // response set
        $response['success'] = 'false';
        $response['message'] = "Invalid Credentails";
    }
    echo json_encode($response);
} else {

    $query = "SELECT credentials.station_id, credentials.username, station.station_name, credentials.user_id FROM credentials JOIN station ON credentials.station_id = station.station_id WHERE credentials.username = ? AND credentials.password = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Set session variables
        $_SESSION['station_master_id'] = $row['user_id'];
        $_SESSION['station_master_station_id'] = $row['station_id'];
        $_SESSION['station_master_username'] = $row['username'];
        $_SESSION['station_master_station'] = $row['station_name'];
        $_SESSION['logged_in'] = "true";
        // Update response
        $response['success'] = 'true';
        $response['role'] = 'station_master';
        $response['message'] = 'Login successful';
    } else {
        $response['success'] = 'false';
        $response['message'] = "Invalid Credentails";
    }

    echo json_encode($response);
}
