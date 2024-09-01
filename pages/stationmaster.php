<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Redirect to login if not logged in
    header('Location: ./login.html');
    exit;
}

require_once "../connection.php";

$station_name = $_SESSION['station_master_station'];

$query = "SELECT DISTINCT
    b.bus_id,
    stype.service_name,
    st_start.station_name AS start_station,
    b.start_scheduled_time,
    st_end.station_name AS end_station,
    b.end_scheduled_time,
    s.state AS status,
    COALESCE(d.delay, 0) AS delay
FROM 
    bus b
JOIN 
    stop_order so ON b.bus_id = so.bus_id
JOIN 
    station st ON so.station_id = st.station_id
JOIN 
    station st_start ON b.start_point_id = st_start.station_id
JOIN 
    station st_end ON b.end_point_id = st_end.station_id
JOIN 
    service_type stype ON b.service_id = stype.service_id
LEFT JOIN
    status s ON b.bus_id = s.bus_id
LEFT JOIN
    delay d ON b.bus_id = d.bus_id AND st.station_id = d.current_station_id
WHERE 
    st.station_name = ?
ORDER BY 
    b.start_scheduled_time";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $station_name);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Master Home Page</title>
    <link rel="stylesheet" href="../css/stationmasterpagestyle.css">
</head>

<body>
    <header>
        <h1><?php echo htmlspecialchars($station_name); ?> Depot</h1>
    </header>
    <main>
        <h2>Welcome, Station Master!</h2>
        <table>
            <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Service Name</th>
                    <th>Starting Point</th>
                    <th>Start Time</th>
                    <th>Ending Point</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Delay (minutes)</th>
                    <th>Mark Delay</th>
                    <th>Mark Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $start_time_to_be_formatted = new DateTime($row['start_scheduled_time']);
                        $end_time_to_be_formatted = new DateTime($row['end_scheduled_time']);
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['bus_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['start_station']) . "</td>";
                        echo "<td>" . htmlspecialchars($start_time_to_be_formatted->format('h:i:s A')) . "</td>";
                        echo "<td>" . htmlspecialchars($row['end_station']) . "</td>";
                        echo "<td>" . htmlspecialchars($end_time_to_be_formatted->format('h:i:s A')) . "</td>";
                        echo "<td>" . htmlspecialchars(ucwords($row['status'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['delay']) . "</td>";
                        echo '<td><button class="button" onclick="openPopup(\'delay\', \'' . $row['bus_id'] . '\')">Mark Delay</button></td>';
                        echo '<td><button class="button" onclick="openPopup(\'status\', \'' . $row['bus_id'] . '\')">Mark Status</button></td>';
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='10'>No buses found for this station.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </main>
    <footer>
        <a href="../home.html" class="button">Home</a>
    </footer>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Delay Pop-up -->
    <div class="popup" id="delayPopup">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <h3>Mark Delay for Bus <span id="busIdForDelay"></span></h3>
        <div class="container">
            <form id="delayForm">
                <label for="delay">Specify Delay:</label><br>
                <input type="hidden" name="bus_id" id="hidden_bus_id_for_delay">
                <input type="text" id="delay" name="delay" placeholder="Enter delay in minutes" required><br>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>

    <!-- Status Pop-up -->
    <div class="popup" id="statusPopup">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <h3>Mark Status for Bus <span id="busIdForStatus"></span></h3>
        <div class="container">
            <form id="statusForm">
                <p><strong>Specify Status:</strong></p>
                <input type="hidden" name="bus_id" id="hidden_bus_id_for_status">
                <select name="status_of_bus" id="status_of_bus">
                    <option value="active">Running</option>
                    <option value="inactive">Facing Problem</option>
                </select>
                <span id="error-result-status-popup"></span>
                <input type="submit" value="Submit" name="submit">
            </form>
        </div>
    </div>
    <script src="../js/stationmasterhandling.js"></script>
</body>

</html>