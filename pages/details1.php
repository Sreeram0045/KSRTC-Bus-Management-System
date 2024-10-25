<?php
require_once "../connection.php";
?>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Details</title>
    <link rel='shortcut icon' href='../assets/Designer.png' type='image/x-icon'>
    <link rel='stylesheet' type='text/css' href='../css/detailspagestyle.css' />
</head>

<body>

    <?php
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

        // Array to store scheduled time to reach each stops
        $scheduled_time_array = [];

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
            echo "<h3>Status of the Bus: " . ucfirst($row['state']) . "</h3>";
            echo "<h3>Delay of the Bus: " . $row['delay'] . " Min</h3>";

            // storing delay in a variable
            $delay_in_time = $row['delay'];

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
            echo "<tbody id='table_body'>";

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

                // push each scheduled time in to the array
                array_push($scheduled_time_array, $adjusted_start_time);

                // Display each stop's details
                echo "<tr>";
                echo "<td>" . $row['stop_order'] . "</td>";
                echo "<td>" . $row['station_name'] . "</td>";
                echo "<td>" . $adjusted_start_time->format('h:i:s A') . "</td>";
                echo "</tr>";
            } while ($row = $result->fetch_assoc());

            $scheduled_time_with_delay_array = [];

            foreach ($scheduled_time_array as $key => $value) {
                $datetime = clone $value; // Clone to avoid modifying the original
                $minutesToAdd = $delay_in_time;
                $datetime->modify("+{$minutesToAdd} minutes");
                array_push($scheduled_time_with_delay_array, $datetime);
                // $datetime = new DateTime($value->format('Y-m-d H:i:s'));
                // $minutesToAdd = $delay_in_time;
                // $datetime->modify("+{$minutesToAdd} minutes");
                // array_push($scheduled_time_with_delay_array, $datetime);
            }

            $now = new DateTime();

            $position = 0;
            foreach ($scheduled_time_with_delay_array as $key => $value) {
                if ($value < $now) {
                    $position = $key + 1;
                }
            }

            $array_length = count($scheduled_time_with_delay_array);

            // Correct percentage calculation
            $percentage = ($array_length > 0) ? (100 / $array_length * $position) : 0;

            echo "</tbody>";
            echo "</table>";
            echo "</div>";

            // Add this line to pass the percentage to JavaScript
            echo "<script>
                var busPercentage = " . json_encode($percentage) . ";
                var timeNow = " . json_encode($now->format('Y-m-d H:i:s')) . ";
                var scheduledTimes = " . json_encode(array_map(function ($dt) {
                return $dt->format('Y-m-d H:i:s');
            }, $scheduled_time_with_delay_array)) . ";
            </script>";
        } else {
            echo "No detailed results found.";
        }
    } else {
        echo "No bus ID provided.";
    }
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timer = ms => new Promise(res => setTimeout(res, ms));

            // Delay calling the main function by 2-3 seconds
            setTimeout(callApi, 2000); // 3000ms = 3 seconds

            async function callApi() {
                let nameCell;
                try {
                    console.log("Script is running");
                    console.log("Current time:", timeNow);

                    if (typeof busPercentage === 'undefined' || typeof scheduledTimes === 'undefined') {
                        console.error("Required variables are not defined");
                        return;
                    }

                    console.log("Percentage:", busPercentage);
                    console.log("Scheduled times:", scheduledTimes);

                    const tableBody = document.querySelector("#table_body");
                    if (!tableBody) {
                        console.error("Table body not found");
                        return;
                    }

                    const rows = tableBody.querySelectorAll('tr');

                    // Function to compare times
                    function isTimePassed(scheduledTime, currentTime) {
                        return new Date(scheduledTime) < new Date(currentTime);
                    }

                    // Loop through the rows and color based on time comparison
                    for (let index = 0; index < rows.length; index++) {
                        if (index < scheduledTimes.length && isTimePassed(scheduledTimes[index], timeNow)) {
                            nameCell = rows[index].querySelector('td:nth-child(2)');

                            if (nameCell) {
                                await timer(1000); // Wait for 1 second before coloring the next cell
                                nameCell.style.backgroundColor = 'green';
                                console.log("Coloring cell:", index, "Time:", scheduledTimes[index]);
                            } else {
                                console.warn("Name cell not found in row:", index);
                            }
                        }
                    }
                } catch (error) {
                    console.error("An error occurred:", error);
                }

                // SVG loading part
                try {
                    function loadSVG(url) {
                        fetch(url)
                            .then(response => response.text())
                            .then(data => {
                                const parser = new DOMParser();
                                const svgDoc = parser.parseFromString(data, "image/svg+xml");
                                const svgElement = svgDoc.documentElement;

                                // Find the container element
                                // Append the SVG to the container
                                nameCell.appendChild(svgElement);
                            });
                    }

                    // Usage
                    loadSVG('../assets/destinationsvg.svg');
                } catch (error) {
                    console.log("Error occurred while loading the svg", error);
                }
            }
        });
    </script>

</body>

</html>