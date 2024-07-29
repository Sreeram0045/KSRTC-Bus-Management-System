<?php
require_once "../connection.php";
if(isset($_POST["submit_start_and_end"])) {
    // Check if the starting point and end point are the same
    if ($_POST["start_point"] === $_POST["end_point"]) {
        die("The start point and the end point are the same. No services can be found.");
    }

    $starting = $_POST["start_point"];
    $ending = $_POST["end_point"];

    $query = "SELECT 
                b.bus_id,
                b.start_scheduled_time,
                b.end_scheduled_time,
                b.avg_time_taken,
                st_start.station_name AS start_point,
                st_end.station_name AS end_point,
                so_start.stop_order AS start_order,
                so_end.stop_order AS end_order,
                b.total_no_of_stops,
                del.delay,
                stat.state,
                stype.service_name
            FROM 
                bus b
            JOIN 
                stop_order so_start ON b.bus_id = so_start.bus_id
            JOIN 
                stop_order so_end ON b.bus_id = so_end.bus_id
            JOIN 
                station st_start ON so_start.station_id = st_start.station_id
            JOIN 
                station st_end ON so_end.station_id = st_end.station_id
            JOIN
                status stat ON b.bus_id = stat.bus_id
            JOIN
                delay del ON b.bus_id = del.bus_id
            JOIN
                service_type stype ON b.service_id = stype.service_id
            WHERE 
                st_start.station_name = ? 
                AND st_end.station_name = ?
                AND so_start.stop_order < so_end.stop_order";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ss", $starting, $ending);
    $stmt->execute();   
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $bus_id = $row['bus_id'];
            $start_scheduled_time = new DateTime($row['start_scheduled_time']);
            $end_scheduled_time = new DateTime($row['end_scheduled_time']);
            $avg_time_taken = $row['avg_time_taken'];
            $start_order = $row['start_order'];
            $end_order = $row['end_order'];
            $total_no_of_stops = $row['total_no_of_stops'];
            $delay = $row['delay'];
            $status = $row['state'];
            $service_name = $row['service_name'];

            if(!$total_no_of_stops===2){
            // average time taken between each stops in hours and minutes
            // echo $avg_time_taken."<br>";
            // converting the average time taken between each stops to minutes
            [$avg_hours,$avg_minutes]=explode(":",$avg_time_taken);
            $total_avg_minutes=(int)$avg_hours*60+(int)$avg_minutes;
            // echo $total_avg_minutes."<br>";

            // recalibarating the number of stop for calculation
            $start_order=$start_order-1;
            $end_order=$end_order-1;
            
            // Calculating start and end times
            $adjusted_start_time = clone $start_scheduled_time;
            $adjusted_start_time->modify('+' . ($start_order * $total_avg_minutes) . ' minutes');
            
            $adjusted_end_time = clone $start_scheduled_time;
            $adjusted_end_time->modify('+' . ($end_order * $total_avg_minutes) . ' minutes');
            
            echo "Bus ID: " . $bus_id . "<br>" .
                 "Service: " . $service_name . "<br>" .
                 "Start Point: " . $starting . "<br>" .
                 "End Point: " . $ending . "<br>" .
                 "Scheduled Start Time: " . $adjusted_start_time->format('h:i:sA') . "<br>" .
                 "Scheduled End Time: " . $adjusted_end_time->format('h:i:sA') . "<br>" .
                 "Current Status: " . $status . "<br>" .
                 "Current Delay: " . $delay . " minutes<br><br>";
            }else{
                echo "Bus ID: " . $bus_id . "<br>" .
                 "Service: " . $service_name . "<br>" .
                 "Start Point: " . $starting . "<br>" .
                 "End Point: " . $ending . "<br>" .
                 "Scheduled Start Time: " . $start_scheduled_time->format('h:i:sA') . "<br>" .
                 "Scheduled End Time: " . $end_scheduled_time->format('h:i:sA') . "<br>" .
                 "Current Status: " . $status . "<br>" .
                 "Current Delay: " . $delay . " minutes<br><br>";
            }

        }
    } else {
        echo "No results found.";
    }

}


?>
