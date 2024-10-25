<?php

require_once "../connection.php";

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$response = array();
$mini_response = array();

if (!isset($data['submit_start_and_end'])) {
    $error_response['error_response'] = "Invalid Submission";
    array_push($response, $error_response);
    echo json_encode($response);
    exit;
}

try {
    $starting = $data["start_point"];
    $ending = $data["end_point"];
    $service_type = $data["service_type"];

    $connection->begin_transaction();

    $query = "SELECT DISTINCT
    b.bus_id,
    st.service_name,
    start_station.station_name AS start_point,
    end_station.station_name AS end_point,
    b.start_scheduled_time,
    b.end_scheduled_time,
    COALESCE(d.delay, 0) AS delay,
    s.state AS status
FROM 
    bus b
    INNER JOIN station start_station ON b.start_point_id = start_station.station_id
    INNER JOIN station end_station ON b.end_point_id = end_station.station_id
    INNER JOIN service_type st ON b.service_id = st.service_id
    LEFT JOIN delay d ON b.bus_id = d.bus_id
    LEFT JOIN status s ON b.bus_id = s.bus_id
    INNER JOIN stop_order so_start ON b.bus_id = so_start.bus_id
    INNER JOIN stop_order so_end ON b.bus_id = so_end.bus_id
    INNER JOIN station station_start ON so_start.station_id = station_start.station_id
    INNER JOIN station station_end ON so_end.station_id = station_end.station_id
WHERE 
    station_start.station_name = ?
    AND station_end.station_name = ?
    AND so_start.stop_order < so_end.stop_order";

    if (!empty($service_type)) {
        $query .= "AND st.service_name = ?;";
    }

    $stmt = $connection->prepare($query);

    if (!empty($service_type)) {
        $stmt->bind_param("sss", $starting, $ending, $service_type);
    } else {
        $stmt->bind_param("ss", $starting, $ending);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error while executing Query");
    }

    $result = $stmt->get_result();

    if ($result->num_rows < 0) {
        $no_response['no_services'] = "No such route";
        array_push($response, $no_response);
    }

    while ($row = $result->fetch_assoc()) {
        $bus_id = $row['bus_id'];
        $service_name = $row['service_name'];
        $start_scheduled_time = new DateTime($row['start_scheduled_time']);
        $end_scheduled_time = new DateTime($row['end_scheduled_time']);
        $start_point = $row['start_point'];
        $end_point = $row['end_point'];
        $delay = $row['delay'];
        $status = $row['status'];

        $mini_response['bus_id'] = $bus_id;
        $mini_response['service_name'] = $service_name;
        $mini_response['start_point'] = $start_point;
        $mini_response['start_scheduled_time'] = $start_scheduled_time->format('h:i:sA');
        $mini_response['end_point'] = $end_point;
        $mini_response['end_scheduled_time'] = $end_scheduled_time->format('h:i:sA');
        $mini_response['status'] = $status;
        $mini_response['delay'] = $delay;

        array_push($response, $mini_response);
    }

    echo json_encode($response);
} catch (Exception $th) {
    //throw $th;
    echo json_encode([
        'status' => false,
        'message' => $th->getMessage()
    ]);
}
// if (isset($data["submit_start_and_end"])) {
//     $starting = $data["start_point"];
//     $ending = $data["end_point"];
//     $service_type = $data["service_type"];

//     $query = "SELECT 
//                 b.bus_id,
//                 b.start_scheduled_time,
//                 b.end_scheduled_time,
//                 st_start.station_name AS start_point,
//                 st_end.station_name AS end_point,
//                 so_start.stop_order AS start_order,
//                 so_end.stop_order AS end_order,
//                 b.total_no_of_stops,
//                 del.delay,
//                 stat.state,
//                 stype.service_name
//             FROM 
//                 bus b
//             JOIN 
//                 stop_order so_start ON b.bus_id = so_start.bus_id
//             JOIN 
//                 stop_order so_end ON b.bus_id = so_end.bus_id
//             JOIN 
//                 station st_start ON so_start.station_id = st_start.station_id
//             JOIN 
//                 station st_end ON so_end.station_id = st_end.station_id
//             JOIN
//                 status stat ON b.bus_id = stat.bus_id
//             JOIN
//                 delay del ON b.bus_id = del.bus_id
//             JOIN
//                 service_type stype ON b.service_id = stype.service_id
//             WHERE 
//                 st_start.station_name = ? 
//                 AND st_end.station_name = ?
//                 AND so_start.stop_order < so_end.stop_order";

//     if (!empty($service_type)) {
//         $query .= " AND stype.service_name = ?";
//     }

//     $query .= " GROUP BY b.bus_id";

//     $stmt = $connection->prepare($query);

//     if (!empty($service_type)) {
//         $stmt->bind_param("sss", $starting, $ending, $service_type);
//     } else {
//         $stmt->bind_param("ss", $starting, $ending);
//     }

//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows < 0) {
//         $no_response['no_services'] = "No such route";
//         array_push($response, $no_response);
//     }

//     while ($row = $result->fetch_assoc()) {
//         $bus_id = $row['bus_id'];
//         $start_scheduled_time = new DateTime($row['start_scheduled_time']);
//         $end_scheduled_time = new DateTime($row['end_scheduled_time']);
//         $avg_time_taken = $row['avg_time_taken'];
//         $start_order = $row['start_order'];
//         $end_order = $row['end_order'];
//         $total_no_of_stops = $row['total_no_of_stops'];
//         $delay = $row['delay'];
//         $status = $row['state'];
//         $service_name = $row['service_name'];

//         if ($total_no_of_stops !== 2) {
//             [$avg_hours, $avg_minutes] = explode(":", $avg_time_taken);
//             $total_avg_minutes = (int)$avg_hours * 60 + (int)$avg_minutes;

//             // Recalibrating the number of stops for calculation
//             $start_order -= 1;
//             $end_order -= 1;

//             // Calculating start and end times
//             $adjusted_start_time = clone $start_scheduled_time;
//             $adjusted_start_time->modify('+' . ($start_order * $total_avg_minutes) . ' minutes');

//             $adjusted_end_time = clone $start_scheduled_time;
//             $adjusted_end_time->modify('+' . ($end_order * $total_avg_minutes) . ' minutes');

//             $mini_response['bus_id'] = $bus_id;
//             $mini_response['service_name'] = $service_name;
//             $mini_response['start_point'] = $starting;
//             $mini_response['start_scheduled_time'] = $adjusted_start_time->format('h:i:sA');
//             $mini_response['end_point'] = $ending;
//             $mini_response['end_scheduled_time'] = $adjusted_end_time->format('h:i:sA');
//             $mini_response['status'] = $status;
//             $mini_response['delay'] = $delay;
//         } else {
//             $mini_response['bus_id'] = $bus_id;
//             $mini_response['service_name'] = $service_name;
//             $mini_response['start_point'] = $starting;
//             $mini_response['start_scheduled_time'] = $start_scheduled_time->format('h:i:sA');
//             $mini_response['end_point'] = $ending;
//             $mini_response['end_scheduled_time'] = $end_scheduled_time->format('h:i:sA');
//             $mini_response['status'] = $status;
//             $mini_response['delay'] = $delay;
//         }
//         array_push($response, $mini_response);
//     }
// } else {
//     $error_response['error_response'] = "Invalid Submission";
//     array_push($response, $error_response);
// }

// echo json_encode($response);



// SELECT DISTINCT
//     b.bus_id,
//     st.service_name,
//     start_station.station_name AS start_point,
//     end_station.station_name AS end_point,
//     b.start_scheduled_time,
//     b.end_scheduled_time,
//     COALESCE(d.delay, 0) AS delay,
//     s.state AS status
// FROM 
//     bus b
//     INNER JOIN station start_station ON b.start_point_id = start_station.station_id
//     INNER JOIN station end_station ON b.end_point_id = end_station.station_id
//     INNER JOIN service_type st ON b.service_id = st.service_id
//     LEFT JOIN delay d ON b.bus_id = d.bus_id
//     LEFT JOIN status s ON b.bus_id = s.bus_id
//     INNER JOIN stop_order so_start ON b.bus_id = so_start.bus_id
//     INNER JOIN stop_order so_end ON b.bus_id = so_end.bus_id
//     INNER JOIN station station_start ON so_start.station_id = station_start.station_id
//     INNER JOIN station station_end ON so_end.station_id = station_end.station_id
// WHERE 
//     station_start.station_name = 'Thiruvananthapuram'
//     AND station_end.station_name = 'Ernakulam'
//     AND so_start.stop_order < so_end.stop_order;
