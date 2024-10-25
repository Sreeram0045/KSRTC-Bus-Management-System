<?php
session_start();

class AdminPanel
{
    private $connection;
    private $busServices;

    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->checkAuth();
    }

    private function checkAuth()
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            header('Location: ./login.html');
            exit;
        }
    }

    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();
        header('Location: ./login.html');
        exit;
    }

    public function fetchBusServices()
    {
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

        $result = $this->connection->query($query);
        $this->busServices = $result;
        return $result;
    }
    // In AdminPanel.php - Update the deleteBusService method:
    public function deleteBusService($busId)
    {
        try {
            $this->connection->begin_transaction();

            $queries = [
                "DELETE FROM delay WHERE bus_id = ?",
                "DELETE FROM stop_order WHERE bus_id = ?",
                "DELETE FROM service_day WHERE bus_id = ?",
                "DELETE FROM status WHERE bus_id = ?",
                "DELETE FROM bus WHERE bus_id = ?"
            ];

            foreach ($queries as $query) {
                $stmt = $this->connection->prepare($query);
                $stmt->bind_param("i", $busId);
                $stmt->execute();
                $stmt->close();
            }

            $this->connection->commit();
            return ["success" => true, "message" => "Bus service deleted successfully"];
        } catch (Exception $e) {
            $this->connection->rollback();
            return ["success" => false, "message" => "Error deleting bus service: " . $e->getMessage()];
        }
    }

    // Add this method to handle AJAX requests:
    public function handleDeleteRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
            header('Content-Type: application/json');
            $busId = $_POST['busId'];
            $result = $this->deleteBusService($busId);
            echo json_encode($result);
            exit;
        }
    }

    public function renderHeader()
    {
        echo <<<HTML
                <header>
                    <h1>Welcome, Admin</h1>
                    <div class="header-buttons">
                        <form method="post" style="display: inline;">
                            <button class="Btn" name="logout">
                                <div class="sign">
                                    <svg viewBox="0 0 512 512">
                                        <path
                                            d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="text">Logout</div>
                            </button>
                        </form>
                        <div>
                            <a href="../index.html" class="home-button">Home</a>
                        </div>
                    </div>
                </header>
            HTML;
    }


    public function renderNav()
    {
        echo <<<HTML
        <nav>
            <ul>
                <li><button id="add-service" onclick="manageNewService();">Add Service</button></li>
                <li><button id="manage-station-master" onclick="manageStationMaster();">Manage Station Master</button></li>
            </ul>
        </nav>
        HTML;
    }

    public function renderTable()
    {
        echo <<<HTML
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
        HTML;

        if ($this->busServices->num_rows > 0) {
            while ($row = $this->busServices->fetch_assoc()) {
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
                echo "<td><button onclick='editService(\"" . $row['bus_id'] . "\")' class='table-edit-button'>Edit</button></td>";
                echo "<td><button onclick='removeService(\"" . $row['bus_id'] . "\")' class='table-remove-button' >Remove Service</button></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='10'>No bus services found.</td></tr>";
        }

        echo "</tbody></table></section>";
    }
    public function renderEditCard($busId)
    {
        // First, fetch the current bus data
        $query = "SELECT 
        b.*, 
        GROUP_CONCAT(sd.day_of_week) as service_days
    FROM bus b
    LEFT JOIN service_day sd ON b.bus_id = sd.bus_id
    WHERE b.bus_id = ?
    GROUP BY b.bus_id";

        $stmt = $this->connection->prepare($query);
        $stmt->bind_param("i", $busId);
        $stmt->execute();
        $result = $stmt->get_result();
        $busData = $result->fetch_assoc();

        // Get array of service days
        $serviceDays = $busData['service_days'] ? explode(',', $busData['service_days']) : [];

        $html = <<<HTML
    <section class="edit-form">
        <div class="card">
            <h2>Edit Bus Schedule - Bus ID: {$busId}</h2>
            <form id="edit-bus-form" data-bus-id="{$busId}">
                <div class="checkbox-wrapper-24">
HTML;

        // Add checkboxes for each day
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day) {
            $checked = in_array($day, $serviceDays) ? 'checked' : '';
            $html .= <<<HTML
            <div class="day-checkbox">
                <input type="checkbox" id="{$day}" name="days[]" value="{$day}" {$checked}>
                <label for="{$day}"><span></span>{$day}</label>
            </div>
HTML;
        }

        $html .= <<<HTML
                </div>
                <div class="time-container">
                    <div class="first-time">
                        <label>Starting Time:</label>
                        <input type="time" name="start_time" value="{$busData['start_scheduled_time']}">
                    </div>
                    <div class="second-time">
                        <label>Ending Time:</label>
                        <input type="time" name="end_time" value="{$busData['end_scheduled_time']}">
                    </div>
                </div>
                <button type="submit" class="submit-btn-edit" name="submit-btn-edit">Update Schedule</button>
            </form>
        </div>
    </section>
HTML;

        return $html;
    }
    public function editBusService()
    {
        try {
            // Get the JSON data from the request
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);

            if (!$data) {
                throw new Exception('Invalid data received');
            }

            $busId = $data['busId'];
            $startTime = $data['startTime'];
            $endTime = $data['endTime'];
            $days = $data['days'];

            // Start transaction
            $this->connection->begin_transaction();

            // Update bus times
            $updateBusQuery = "UPDATE bus 
                              SET start_scheduled_time = ?, 
                                  end_scheduled_time = ? 
                              WHERE bus_id = ?";

            $stmt = $this->connection->prepare($updateBusQuery);
            $stmt->bind_param("ssi", $startTime, $endTime, $busId);
            $stmt->execute();

            // Delete existing service days
            $deleteServiceDaysQuery = "DELETE FROM service_day WHERE bus_id = ?";
            $stmt = $this->connection->prepare($deleteServiceDaysQuery);
            $stmt->bind_param("i", $busId);
            $stmt->execute();

            // Insert new service days
            $insertServiceDayQuery = "INSERT INTO service_day (bus_id, day_of_week) VALUES (?, ?)";
            $stmt = $this->connection->prepare($insertServiceDayQuery);

            foreach ($days as $day) {
                $stmt->bind_param("is", $busId, $day);
                $stmt->execute();
            }

            // Commit transaction
            $this->connection->commit();

            return [
                'success' => true,
                'message' => 'Bus schedule updated successfully'
            ];
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->connection->rollback();

            return [
                'success' => false,
                'message' => 'Error updating bus schedule: ' . $e->getMessage()
            ];
        }
    }
    public function manageStationMaster()
    {
        return <<<HTML
        <form method="post" class="station-master-card" id="station-master-card">
            <div class="username-container">
                <label>Select the Station Master:</label>
                <div class="select-wrapper">
                    <select class="classic" name="value_of_username" required>
                        <option value="smaster1">smaster1</option>
                        <option value="smaster2">smaster2</option>
                        <option value="smaster3">smaster3</option>
                        <option value="smaster4">smaster4</option>
                        <option value="smaster5">smaster5</option>
                        <option value="smaster6">smaster6</option>
                        <option value="smaster7">smaster7</option>
                        <option value="smaster8">smaster8</option>
                        <option value="smaster9">smaster9</option>
                        <option value="smaster10">smaster10</option>
                        <option value="smaster11">smaster11</option>
                        <option value="smaster12">smaster12</option>
                        <option value="smaster13">smaster13</option>
                        <option value="smaster14">smaster14</option>
                    </select>
                </div>
            </div>
            <div class="password-container">
                <label>Enter new password:</label>
                <input type="password" name="stationmaster_new_password" class="password-input" required 
                       minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$">
                <div class="password-requirements">
                    Password must:
                    <ul>
                        <li>Be at least 8 characters long</li>
                        <li>Contain both letters and numbers</li>
                        <li>Not be empty</li>
                    </ul>
                </div>
            </div>
            <div class="button-container">
                <button type="submit" class="submit-button-manage-station-master" 
                        id="submit-button-manage-station-master" 
                        name="station-master-manage-submit">Submit</button>
            </div>
        </form>
HTML;
    }
    public function updateStationMasterPassword()
    {
        try {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);

            if (!$data) {
                throw new Exception('Invalid data received');
            }

            $username = $data['username'];
            $new_password = $data['new_password'];

            // Server-side validation
            if (empty($new_password) || strlen($new_password) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }

            if (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                throw new Exception('Password must contain both letters and numbers');
            }

            // Hash the password before storing
            // $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $this->connection->begin_transaction();

            $updateStationMasterQuery = "UPDATE `credentials` 
                                   SET password = ? 
                                   WHERE username = ? AND user_role = 'station_master'";

            $stmt = $this->connection->prepare($updateStationMasterQuery);
            $stmt->bind_param("ss", $new_password, $username);

            if (!$stmt->execute()) {
                throw new Exception('Failed to update password');
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception('No station master found with the given username');
            }

            $this->connection->commit();

            return [
                'success' => true,
                'username' => $username,
                'message' => 'Password updated successfully'
            ];
        } catch (Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollback();
            }
            return [
                'success' => false,
                'username' => $username ?? '',
                'message' => $e->getMessage()
            ];
        }
    }
    public function renderNewBusInputForm()
    {
        return <<<HTML
            <div class="card-bus-input-new">
            <h2>Bus Schedule</h2>
            <form action="" method="post" id="new-bus-input">
                <!-- Other Form Elements -->
                <div class="checkbox-wrapper-24">
                    <input type="checkbox" id="monday" name="check" value="Monday" />
                    <label for="monday">
                        <span></span>Monday
                    </label>
                    <input type="checkbox" id="tuesday" name="check" value="Tuesday" />
                    <label for="tuesday">
                        <span></span>Tuesday
                    </label>
                    <input type="checkbox" id="wednesday" name="check" value="Wednesday" />
                    <label for="wednesday">
                        <span></span>Wednesday
                    </label>
                    <input type="checkbox" id="thursday" name="check" value="Thursday" />
                    <label for="thursday">
                        <span></span>Thursday
                    </label>
                    <input type="checkbox" id="friday" name="check" value="Friday" />
                    <label for="friday">
                        <span></span>Friday
                    </label>
                    <input type="checkbox" id="saturday" name="check" value="Saturday" />
                    <label for="saturday">
                        <span></span>Saturday
                    </label>
                    <input type="checkbox" id="sunday" name="check" value="Sunday" />
                    <label for="sunday">
                        <span></span>Sunday
                    </label>
                </div>

                <div class="time-container">
                    <div class="first-time">
                        <label>Starting Time:</label>
                        <input type="time" name="edited_start_time" id="edited_start_time">
                    </div>
                    <div class="second-time">
                        <label>Ending Time:</label>
                        <input type="time" name="edited_end_time" id="edited_end_time">
                    </div>
                </div>
                <div class="service-id select">
                    <label for="service-type">Service Type:</label>
                    <select name="service-id" id="service-type">
                        <option selected disabled>Enter the Service Type</option>
                        <option value="1">Swift</option>
                        <option value="2">Garuda Maharaja</option>
                        <option value="3">Minnal</option>
                    </select>
                    <span>Select Swift for no stops, Garuda for 4 stops, or Minnal for multiple stops.</span>
                </div>
                <div class="bus-id">
                    <label>Bus ID:</label>
                    <input type="text" name="bus_id_input">
                    <span>e.g., For Swift: 10001, for Garuda: 20001, for Minnal: 30001.</span>
                </div>
                <div class="starting_and_ending_point_container">
                    <label>Starting Point: </label>
                    <div class="select">
                        <select name="start_point" id="start_name_id">
                            <option value="1">Thiruvananthapuram (Trivandrum)</option>
                            <option value="2">Kollam</option>
                            <option value="3">Pathanamthitta</option>
                            <option value="4">Alappuzha (Alleppey)</option>
                            <option value="5">Kottayam</option>
                            <option value="6">Idukki</option>
                            <option value="7">Ernakulam (Kochi)</option>
                            <option value="8">Thrissur</option>
                            <option value="9">Palakkad</option>
                            <option value="10">Malappuram</option>
                            <option value="11">Kozhikode</option>
                            <option value="12">Wayanad</option>
                            <option value="13">Kannur</option>
                            <option value="14">Kasaragod</option>
                        </select>
                    </div>

                    <label>Ending Point: </label>
                    <div class="select">
                        <select name="end_point" id="end_name_id">
                            <option value="1">Thiruvananthapuram (Trivandrum)</option>
                            <option value="2">Kollam</option>
                            <option value="3">Pathanamthitta</option>
                            <option value="4">Alappuzha (Alleppey)</option>
                            <option value="5">Kottayam</option>
                            <option value="6">Idukki</option>
                            <option value="7" selected>Ernakulam (Kochi)</option>
                            <option value="8">Thrissur</option>
                            <option value="9">Palakkad</option>
                            <option value="10">Malappuram</option>
                            <option value="11">Kozhikode</option>
                            <option value="12">Wayanad</option>
                            <option value="13">Kannur</option>
                            <option value="14">Kasaragod</option>
                        </select>
                    </div>
                </div>

                <div class="in-between-stops-container" id="stops-container">
                    <label>In-between Stops: </label>
                    <button type="button" class="add-stop-btn" id="add-stop">Add Stop</button>
                    <div class="stops-list" id="stops-list"></div>
                </div>

                <input type="submit" value="Submit">
            </form>
        </div>
        HTML;
    }
    // ... (add this method to the AdminPanel class)
    // ... (other methods remain the same)

    public function insertNewBusService()
    {
        try {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);

            if (!$data) {
                throw new Exception('Invalid data received');
            }

            $busId = (int)$data['busId'];
            $serviceType = (int)$data['serviceType'];
            $startTime = $data['startTime'];
            $endTime = $data['endTime'];
            $startPoint = (int)$data['startPoint'];
            $endPoint = (int)$data['endPoint'];
            $operatingDays = $data['operatingDays'];

            $this->connection->begin_transaction();

            // Validate bus ID format
            $validFormat = false;
            switch ($serviceType) {
                case 1: // Swift
                    $validFormat = preg_match('/^100\d+$/', $busId);
                    break;
                case 2: // Garuda
                    $validFormat = preg_match('/^200\d+$/', $busId);
                    break;
                case 3: // Minnal
                    $validFormat = preg_match('/^300\d+$/', $busId);
                    break;
            }

            if (!$validFormat) {
                throw new Exception('Invalid bus ID format');
            }

            // Check if bus ID already exists
            $stmt = $this->connection->prepare("SELECT bus_id FROM bus WHERE bus_id = ?");
            $stmt->bind_param("i", $busId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Bus ID already exists');
            }

            // Calculate number of stops
            $totalStops = isset($data['stops']) ? count($data['stops']) : 0;
            if ($serviceType === 1) {
                $totalStops = 2;  // Only start and end points
            } else {
                $totalStops += 2; // Add start and end points to intermediate stops
            }

            // Insert into bus table - Fixed query
            $stmt = $this->connection->prepare(
                "INSERT INTO bus (bus_id, service_id, start_point_id, end_point_id, start_scheduled_time, end_scheduled_time, total_no_of_stops) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt->bind_param("iiiissi", $busId, $serviceType, $startPoint, $endPoint, $startTime, $endTime, $totalStops)) {
                throw new Exception("Error binding parameters: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error inserting into bus table: " . $stmt->error);
            }

            // Insert service days
            $stmt = $this->connection->prepare(
                "INSERT INTO service_day (bus_id, day_of_week) VALUES (?, ?)"
            );
            foreach ($operatingDays as $day) {
                $stmt->bind_param("is", $busId, $day);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting service day: " . $stmt->error);
                }
            }

            // Insert stops
            $stmt = $this->connection->prepare(
                "INSERT INTO stop_order (bus_id, station_id, stop_order) VALUES (?, ?, ?)"
            );

            // Insert start point
            $stopOrder = 1;
            $stmt->bind_param("iii", $busId, $startPoint, $stopOrder);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting start point: " . $stmt->error);
            }

            // Insert intermediate stops if not Swift service
            if ($serviceType !== 1 && !empty($data['stops'])) {
                foreach ($data['stops'] as $index => $stationId) {
                    $stopOrder = $index + 2;
                    $stmt->bind_param("iii", $busId, $stationId, $stopOrder);
                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting intermediate stop: " . $stmt->error);
                    }
                }
            }

            // Insert end point
            $stopOrder = $totalStops;
            $stmt->bind_param("iii", $busId, $endPoint, $stopOrder);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting end point: " . $stmt->error);
            }

            // Insert initial status
            $stmt = $this->connection->prepare(
                "INSERT INTO status (bus_id, state) VALUES (?, 'active')"
            );
            $stmt->bind_param("i", $busId);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting status: " . $stmt->error);
            }

            // Insert initial delay record
            $stmt = $this->connection->prepare(
                "INSERT INTO delay (bus_id, current_station_id, delay) VALUES (?, ?, 0)"
            );
            $stmt->bind_param("ii", $busId, $startPoint);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting delay: " . $stmt->error);
            }

            $this->connection->commit();
            return [
                'success' => true,
                'message' => 'Bus service added successfully'
            ];
        } catch (Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollback();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ... (rest of the class remains the same)
}


// INSERT INTO bus (bus_id, service_id, start_point_id, end_point_id, start_scheduled_time, end_scheduled_time,avg_time_taken,total_no_of_stops)
// VALUES (10002, 1, 1, 14, '08:00:00', '20:00:00','12:00:00',2);  -- Adjust times as needed

// -- Insert stop order for both stations
// INSERT INTO stop_order (bus_id, station_id, stop_order)
// VALUES 
// (10002, 1, 1),   -- Thiruvananthapuram is first stop
// (10002, 14, 2);  -- Kasargode is second stop

// -- Insert delay information
// INSERT INTO delay (bus_id, current_station_id, delay)
// VALUES (10002, 1, 5);  -- 5 minutes delay at Thiruvananthapuram

// -- Set the bus status as active
// INSERT INTO status (bus_id, state)
// VALUES (10002, 'active');

// -- Insert service days (assuming it runs all days)
// INSERT INTO service_day (bus_id, day_of_week)
// VALUES 
// (10002, 'Monday'),
// (10002, 'Tuesday'),
// (10002, 'Friday'),
// (10002, 'Saturday');