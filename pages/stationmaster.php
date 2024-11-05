<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Redirect to login if not logged in
    header('Location: ./login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}


require_once "../backend/Connection.php";

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
    delay d ON b.bus_id = d.bus_id  -- Removed the station condition here
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
    <link rel="shortcut icon" href="../assets/Designer.png" type="image/x-icon">
</head>

<body>
    <header>
        <h1><?php echo htmlspecialchars($station_name); ?> Depot</h1>
    </header>
    <nav>
        <button type="button" class="logout-button" onclick="handleLogout()" name="logout">
            <div class="sign">
                <svg viewBox="0 0 512 512">
                    <path
                        d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path>
                </svg>
            </div>
            <div class="text">Logout</div>
        </button>
        <div class="home-button-container">
            <a href="../index.html">Home</a>
        </div>
    </nav>
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