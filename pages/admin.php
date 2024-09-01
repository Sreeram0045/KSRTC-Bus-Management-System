<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Redirect to login if not logged in
    header('Location: ./login.html');
    exit;
}

require_once "../connection.php";

// Fetch bus services data including status
$query = "SELECT 
    b.bus_id,
    stype.service_name,
    st_start.station_name AS start_station,
    b.start_scheduled_time,
    st_end.station_name AS end_station,
    b.end_scheduled_time,
    COALESCE(d.delay, 0) AS current_delay,
    COALESCE(s.state, 'No Status') AS current_status
FROM 
    bus b
JOIN 
    station st_start ON b.start_point_id = st_start.station_id
JOIN 
    station st_end ON b.end_point_id = st_end.station_id
JOIN 
    service_type stype ON b.service_id = stype.service_id
LEFT JOIN
    delay d ON b.bus_id = d.bus_id
LEFT JOIN
    status s ON b.bus_id = s.bus_id
ORDER BY 
    b.start_scheduled_time";

$result = $connection->query($query);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSRTC Admin Page</title>
    <link rel="stylesheet" href="../css/adminpagestyle.css">
</head>

<body>
    <!-- Header -->
    <header>
        <h1>Welcome, Admin</h1>
        <a href="home.html" class="home-button">Home</a>
    </header>

    <!-- Menubar -->
    <nav>
        <ul>
            <li><button id="add-service">Add Service</button></li>
            <li><button id="manage-station-master">Manage Station Master</button></li>
        </ul>
    </nav>

    <!-- Service Table -->
    <section>
        <h2>Bus Services</h2>
        <table>
            <thead>
                <tr>
                    <th>Bus ID</th>
                    <th>Service</th>
                    <th>Pickup Point</th>
                    <th>Scheduled Reaching Time</th>
                    <th>Drop-Off Point</th>
                    <th>Scheduled Dropping Time</th>
                    <th>Current Delay</th>
                    <th>Status</th>
                    <th>Edit Details</th>
                    <th>Remove Service</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $start_time = new DateTime($row['start_scheduled_time']);
                        $end_time = new DateTime($row['end_scheduled_time']);
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['bus_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['start_station']) . "</td>";
                        echo "<td>" . htmlspecialchars($start_time->format('h:i A')) . "</td>";
                        echo "<td>" . htmlspecialchars($row['end_station']) . "</td>";
                        echo "<td>" . htmlspecialchars($end_time->format('h:i A')) . "</td>";
                        echo "<td>" . htmlspecialchars($row['current_delay']) . " min</td>";
                        echo "<td>" . htmlspecialchars($row['current_status']) . "</td>";
                        echo "<td><button onclick='editService(\"" . $row['bus_id'] . "\")'>Edit</button></td>";
                        echo "<td><button onclick='removeService(\"" . $row['bus_id'] . "\")'>Remove Service</button></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No bus services found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </section>

    <script>
        // Functionality for Add and Delete Service Buttons
        document.getElementById('add-service').addEventListener('click', function() {
            alert("Add Service functionality to be implemented.");
        });
        document.getElementById('delete-service').addEventListener('click', function() {
            alert("Delete Service functionality to be implemented.");
        });
        document.getElementById('manage-station-master').addEventListener('click', function() {
            alert("Manage Station Master functionality to be implemented.");
        });

        function editService(busId) {
            alert("Edit functionality for bus " + busId + " to be implemented.");
        }

        function removeService(busId) {
            alert("Remove functionality for bus " + busId + " to be implemented.");
        }
    </script>
</body>

</html>