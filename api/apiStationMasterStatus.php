<?php

require_once "../connection.php";

session_start();

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$response = [];

$bus_id = $data['busId'];
$status = $data['status'];

$status_lowercase = strtolower($status);
// UPDATE `status` SET state="inactive" WHERE bus_id="10001";

$query = "UPDATE `status` SET state=? WHERE bus_id=?";

$stmt = $connection->prepare($query);
$stmt->bind_param("ss", $status_lowercase, $bus_id);

if ($stmt->execute()) {
    $response['success'] = 'true';
} else {
    $response['success'] = 'false';
}

echo json_encode($response);
