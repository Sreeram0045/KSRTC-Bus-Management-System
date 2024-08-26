<?php
require_once "../connection.php";
echo "<link rel='shortcut icon' href='../assets/Designer.png' type='image/x-icon'>";
echo "<link rel='stylesheet' type='text/css' href='../css/detailspagestyle.css' />";
if (isset($_POST['redirect_submit'])) {
    $bus_id = $_POST['bus_id'];

    // Query to get bus details including service days
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

    // Query to get the days the bus operates
    $days_query = "SELECT day_of_week FROM service_day WHERE bus_id = ?";
    $days_stmt = $connection->prepare($days_query);
    $days_stmt->bind_param("i", $bus_id);
    $days_stmt->execute();
    $days_result = $days_stmt->get_result();

    // Store the operating days in an array
    $operating_days = [];
    while ($day_row = $days_result->fetch_assoc()) {
        $operating_days[] = $day_row['day_of_week'];
    }

    // Define the days of the week in the order you want them displayed
    $days_of_week = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $service_name = $row['service_name'];

        // Display the service name and bus ID
        echo "<div class='heading-container'>";
        echo "<h2>Details for Bus ID: $bus_id of Service Name: $service_name</h2>";
        echo "<h3>Status of the Bus: " . $row['state'] . "</h3>";
        echo "<h3>Delay of the Bus: " . $row['delay'] . " Min</h3>";
        // Display the days the service operates
        echo "<div class='days-circles'>";
        foreach ($days_of_week as $day) {
            $circle_class = in_array($day, $operating_days) ? 'active' : 'inactive';
            echo "<div class='day-circle $circle_class'>" . substr($day, 0, 3) . "</div>";
        }
        echo "</div>";
        echo "</div>";

        // Display the bus schedule details in a table
        echo "<div class='table-container'>";
        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Stop Order</th>";
        echo "<th>Station Name</th>";
        echo "<th>Scheduled Time</th>";
        echo "</tr>";
        echo "</thead>";

        do {
            $start_scheduled_time = new DateTime($row['start_scheduled_time']);
            $avg_time_taken = $row['avg_time_taken'];
            $start_order = $row['stop_order'];
            $total_no_of_stops = $row['total_no_of_stops'];

            // Average time taken between each stop in hours and minutes
            [$avg_hours, $avg_minutes] = explode(":", $avg_time_taken);
            $total_avg_minutes = (int)$avg_hours * 60 + (int)$avg_minutes;

            // Recalibrating the number of stops for calculation
            $start_order -= 1;

            // Calculating adjusted start time
            $adjusted_start_time = clone $start_scheduled_time;
            $adjusted_start_time->modify('+' . ($start_order * $total_avg_minutes) . ' minutes');

            // Display each stop's details
            echo "<tr>";
            echo "<td>" . $row['stop_order'] . "</td>";
            echo "<td>" . $row['station_name'] . "</td>";
            echo "<td>" . $adjusted_start_time->format('h:i:s A') . "</td>";
            echo "</tr>";
        } while ($row = $result->fetch_assoc());

        echo "</table>";
        echo "</div>";
    } else {
        echo "No detailed results found.";
    }
} else {
    echo "No bus ID provided.";
}
?>

<!-- CSS for styling the page -->