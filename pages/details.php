<?php
require_once "../connection.php";
require_once "oop.php";
require_once "timecalculation.php";
require_once "timedelaycalculation.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

class BusDetails
{
    private $connection;
    private $bus_id;
    private $bus_data;
    private $stops;
    private $operating_days;
    private $scheduled_times;
    private $delayed_times;
    private $delay_station_id;
    private $delay_position;

    public function __construct($connection, $bus_id)
    {
        $this->connection = $connection;
        $this->bus_id = $bus_id;
        $this->stops = [];
        $this->operating_days = [];
        $this->delay_position = 0;
    }

    public function loadStops()
    {
        try {
            $query = "SELECT 
                st.station_id,
                st.station_name,
                so.stop_order
            FROM 
                stop_order so
            JOIN 
                station st ON so.station_id = st.station_id
            WHERE 
                so.bus_id = ?
            ORDER BY 
                so.stop_order ASC";

            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            $stmt->bind_param("i", $this->bus_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $this->stops = [];

            while ($row = $result->fetch_assoc()) {
                $this->stops[] = [
                    'station_id' => $row['station_id'],
                    'station_name' => $row['station_name'],
                    'stop_order' => $row['stop_order']
                ];
                // Find the position of the delayed station
                if ($row['station_id'] === $this->delay_station_id) {
                    $this->delay_position = count($this->stops) - 1;
                }
            }

            return !empty($this->stops);
        } catch (Exception $e) {
            echo "Error loading stops: " . $e->getMessage();
            return false;
        }
    }

    public function calculateTimes()
    {
        try {
            if (empty($this->stops)) {
                throw new Exception("No stops loaded");
            }

            // Extract station names for time calculation
            $station_names = array_map(function ($stop) {
                return $stop['station_name'];
            }, $this->stops);

            // Calculate time between stops
            $input = new InputFromDetails($station_names);
            $time_array = json_decode($input->calculateTime($GLOBALS['routes']));

            if ($time_array === null) {
                throw new Exception("Failed to calculate time between stops");
            }

            // Calculate scheduled times
            $time_calculation = new TimeCalculation(
                $this->bus_data['start_scheduled_time'],
                $this->bus_data['end_scheduled_time'],
                $time_array
            );
            $time_calculation->calculateScheduledTime();
            $this->scheduled_times = json_decode($time_calculation->printTime());

            if ($this->scheduled_times === null) {
                throw new Exception("Failed to calculate scheduled times");
            }

            // Calculate delayed times using the correct delay position
            $delay_calculation = new Delay(
                $this->bus_data['delay'],
                $this->delay_position,
                $this->scheduled_times
            );
            $this->delayed_times = json_decode($delay_calculation->delayCalculation());

            if ($this->delayed_times === null) {
                throw new Exception("Failed to calculate delayed times");
            }

            return true;
        } catch (Exception $e) {
            echo "Error calculating times: " . $e->getMessage();
            return false;
        }
    }

    public function renderTable()
    {
        try {
            if (empty($this->stops) || empty($this->scheduled_times) || empty($this->delayed_times)) {
                throw new Exception("Required data not loaded");
            }

            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead><tr><th>Stop Order</th><th>Station Name</th><th>Scheduled Time</th><th>Expected/Arrival Time</th></tr></thead>";
            echo "<tbody id='table_body'>";

            foreach ($this->stops as $index => $stop) {
                // Convert times to DateTime objects for formatting
                $scheduled = new DateTime($this->scheduled_times[$index]);
                $delayed = new DateTime($this->delayed_times[$index]);

                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . $stop['station_name'] . "</td>";
                echo "<td>" . $scheduled->format('h:i A') . "</td>";  // Format with AM/PM
                echo "<td>" . $delayed->format('h:i A') . "</td>";    // Format with AM/PM
                echo "</tr>";
            }

            echo "</tbody></table></div>";
            return true;
        } catch (Exception $e) {
            echo "Error rendering table: " . $e->getMessage();
            return false;
        }
    }

    public function loadBusData()
    {
        try {
            $query = "SELECT 
                b.bus_id,
                stype.service_name,
                b.start_scheduled_time,
                b.end_scheduled_time,
                stat.state,
                del.delay,
                del.current_station_id
            FROM 
                bus b
            JOIN
                status stat ON b.bus_id = stat.bus_id
            JOIN
                delay del ON b.bus_id = del.bus_id
            JOIN
                service_type stype ON b.service_id = stype.service_id
            WHERE 
                b.bus_id = ?";

            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            $stmt->bind_param("i", $this->bus_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $this->bus_data = $result->fetch_assoc();
                $this->delay_station_id = $this->bus_data['current_station_id'];
                return true;
            }
            return false;
        } catch (Exception $e) {
            echo "Error loading bus data: " . $e->getMessage();
            return false;
        }
    }

    public function loadOperatingDays()
    {
        try {
            $query = "SELECT day_of_week FROM service_day WHERE bus_id = ?";
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            $stmt->bind_param("i", $this->bus_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $this->operating_days = [];
            while ($row = $result->fetch_assoc()) {
                $this->operating_days[] = $row['day_of_week'];
            }
            return true;
        } catch (Exception $e) {
            echo "Error loading operating days: " . $e->getMessage();
            return false;
        }
    }

    public function renderHeader()
    {
        try {
            if (!isset($this->bus_data)) {
                throw new Exception("Bus data not loaded");
            }

            echo "<div class='heading-container'>";
            echo "<h2>Details for Bus ID: {$this->bus_id} of Service Name: {$this->bus_data['service_name']}</h2>";
            echo "<h3>Status of the Bus: " . ucfirst($this->bus_data['state']) . "</h3>";
            echo "<h3>Delay of the Bus: {$this->bus_data['delay']} Min</h3>";

            echo "<div class='days-circles'>";
            $days_of_week = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
            foreach ($days_of_week as $day) {
                $circle_class = in_array($day, $this->operating_days) ? 'active' : 'inactive';
                echo "<div class='day-circle $circle_class'>" . substr($day, 0, 3) . "</div>";
            }
            echo "</div></div>";
            return true;
        } catch (Exception $e) {
            echo "Error rendering header: " . $e->getMessage();
            return false;
        }
    }

    public function getJavaScriptData()
    {
        try {
            if (empty($this->delayed_times)) {
                throw new Exception("No delayed times available");
            }

            $now = new DateTime();
            $position = 0;
            foreach ($this->delayed_times as $time) {
                if (new DateTime($time) < $now) {
                    $position++;
                }
            }
            $percentage = (count($this->delayed_times) > 0) ? (100 / count($this->delayed_times) * $position) : 0;

            return [
                'percentage' => $percentage,
                'currentTime' => $now->format('Y-m-d H:i:s'),
                'times' => array_map(function ($time) {
                    return (new DateTime($time))->format('Y-m-d H:i:s');
                }, $this->delayed_times)
            ];
        } catch (Exception $e) {
            echo "Error preparing JavaScript data: " . $e->getMessage();
            return null;
        }
    }
}
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
        echo "<div class='debug'>Processing bus ID: " . $_POST['bus_id'] . "</div>";

        $bus_details = new BusDetails($connection, $_POST['bus_id']);

        if ($bus_details->loadBusData()) {
            echo "<div class='debug'>Bus data loaded successfully</div>";

            if ($bus_details->loadOperatingDays()) {
                echo "<div class='debug'>Operating days loaded successfully</div>";

                if ($bus_details->loadStops()) {
                    echo "<div class='debug'>Stops loaded successfully</div>";

                    if ($bus_details->calculateTimes()) {
                        echo "<div class='debug'>Times calculated successfully</div>";

                        if ($bus_details->renderHeader()) {
                            echo "<div class='debug'>Header rendered successfully</div>";
                        }

                        if ($bus_details->renderTable()) {
                            echo "<div class='debug'>Table rendered successfully</div>";

                            $js_data = $bus_details->getJavaScriptData();
                            if ($js_data) {
                                echo "<script>
                                var busPercentage = " . json_encode($js_data['percentage']) . ";
                                var timeNow = " . json_encode($js_data['currentTime']) . ";
                                var scheduledTimes = " . json_encode($js_data['times']) . ";
                            </script>";
                                echo "<div class='debug'>JavaScript data set successfully</div>";
                            }
                        }
                    }
                }
            }
        } else {
            echo "<div class='error'>Failed to load bus details</div>";
        }
    } else {
        echo "<div class='error'>No bus ID provided</div>";
    }
    ?>

    <script>
        // Your existing JavaScript code remains the same
        document.addEventListener('DOMContentLoaded', function() {
            const timer = ms => new Promise(res => setTimeout(res, ms));

            setTimeout(callApi, 2000);

            async function callApi() {
                let nameCell;
                try {
                    if (typeof busPercentage === 'undefined' || typeof scheduledTimes === 'undefined') {
                        console.error("Required variables are not defined");
                        return;
                    }

                    const tableBody = document.querySelector("#table_body");
                    if (!tableBody) {
                        console.error("Table body not found");
                        return;
                    }

                    const rows = tableBody.querySelectorAll('tr');

                    function isTimePassed(scheduledTime, currentTime) {
                        return new Date(scheduledTime) < new Date(currentTime);
                    }

                    for (let index = 0; index < rows.length; index++) {
                        if (index < scheduledTimes.length && isTimePassed(scheduledTimes[index], timeNow)) {
                            nameCell = rows[index].querySelector('td:nth-child(2)');

                            if (nameCell) {
                                await timer(1000);
                                nameCell.style.backgroundColor = 'green';
                            }
                        }
                    }

                    loadSVG('../assets/destinationsvg.svg', nameCell);
                } catch (error) {
                    console.error("An error occurred:", error);
                }
            }

            function loadSVG(url, nameCell) {
                fetch(url)
                    .then(response => response.text())
                    .then(data => {
                        const parser = new DOMParser();
                        const svgDoc = parser.parseFromString(data, "image/svg+xml");
                        const svgElement = svgDoc.documentElement;
                        nameCell.appendChild(svgElement);
                    })
                    .catch(error => {
                        console.log("Error occurred while loading the svg", error);
                    });
            }
        });
    </script>

    <style>
        .debug {
            background-color: #f0f0f0;
            padding: 5px;
            margin: 2px;
            font-family: monospace;
            display: none;
            /* Remove this line to see debug messages */
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            margin: 5px;
            font-family: monospace;
        }
    </style>

</body>

</html>