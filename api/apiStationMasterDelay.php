<?php

require_once "../connection.php";

session_start();

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$bus_id = $data['busId'];
$delay = $data['delay'];
$station_id = $_SESSION['station_master_station_id'];

$response = [];
// UPDATE delay SET delay="0",current_station_id="1" WHERE bus_id="10001";
if (empty($bus_id) || empty($delay) || !is_numeric($delay)) {
    $response['invalid'] = "true";
    echo json_encode($response);
} else {
    $query = "UPDATE `delay` SET delay = ?,current_station_id = ?  WHERE bus_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("sss", $delay, $station_id, $bus_id);

    if ($stmt->execute()) {
        $response['valid'] = "true";
        echo json_encode($response);
    } else {
        $response['valid'] = "false";
        echo json_encode($response);
    }
}
