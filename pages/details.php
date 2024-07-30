<?php
require_once "../connection.php";

if (isset($_POST['redirect_submit'])) {
    $bus_id = $_POST['bus_id'];

    $query = "SELECT 
                b.bus_id,
                stype.service_name,
                st.station_name,
                so.stop_order,
                b.start_scheduled_time,
                b.end_scheduled_time,
                b.total_no_of_stops,
                b.avg_time_taken,
                del.delay,
                stat.state
            FROM 
                bus b
            JOIN 
                stop_order so ON b.bus_id = so.bus_id
            JOIN 
                station st ON so.station_id = st.station_id
            JOIN
                status stat ON b.bus_id = stat.bus_id
            JOIN
                delay del ON b.bus_id = del.bus_id
            JOIN
                service_type stype ON b.service_id = stype.service_id
            WHERE 
                b.bus_id = ? 
            ORDER BY 
                so.stop_order ASC";

    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $service_name = $row['service_name'];
        echo "<h2>Details for Bus ID: $bus_id of Service Name: $service_name</h2>";
        echo "<table>";
        echo "<tr>";
        echo "<th>Stop Order</th>";
        echo "<th>Station Name</th>";
        echo "<th>Scheduled Time</th>";
        echo "<th>Current Status</th>";
        echo "<th>Delay</th>";
        echo "</tr>";

        do {
            $start_scheduled_time = new DateTime($row['start_scheduled_time']);
            $end_scheduled_time = new DateTime($row['end_scheduled_time']);
            $avg_time_taken = $row['avg_time_taken'];
            $start_order = $row['stop_order'];
            $total_no_of_stops = $row['total_no_of_stops'];
            // Average time taken between each stop in hours and minutes
            [$avg_hours, $avg_minutes] = explode(":", $avg_time_taken);
            $total_avg_minutes = (int)$avg_hours * 60 + (int)$avg_minutes;

            // Recalibrating the number of stops for calculation
            $start_order -= 1;

            // Calculating start and end times
            $adjusted_start_time = clone $start_scheduled_time;
            $adjusted_start_time->modify('+' . ($start_order * $total_avg_minutes) . ' minutes');

            // Small h denotes the conversion from 24 hrs to 12 hrs and A at the end indicates AM and PM
            echo "<tr>";
            echo "<td>". $row['stop_order'] . "</td>";
            echo "<td>". $row['station_name'] . "</td>";
            echo "<td>". $adjusted_start_time->format('h:i:s A') . "</td>";
            echo "<td>". $row['state'] . "</td>";
            echo "<td>". $row['delay'] . " Min</td>";
            echo "</tr>";
        } while ($row = $result->fetch_assoc());

        echo "</table>";
    } else {
        echo "No detailed results found.";
    }
} else {
    echo "No bus ID provided.";
}
?>
