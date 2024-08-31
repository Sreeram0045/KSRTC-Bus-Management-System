<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Redirect to login if not logged in
    header('Location: ./login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station-Master-Home-Page</title>
    <link rel="stylesheet" href="../css/stationmasterpagestyle.css">
</head>

<body>
    <header>
        <h1>'x' DIPPO</h1>
    </header>
    <main>
        <h2>Welcome, Station Master!</h2>
        <table>
            <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Starting Point</th>
                    <th>Start Time</th>
                    <th>Ending Point</th>
                    <th>End Time</th>
                    <th>Mark Delay</th>
                    <th>Mark Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>001</td>
                    <td>Station A</td>
                    <td>08:00 AM</td>
                    <td>Station B</td>
                    <td>09:00 AM</td>
                    <td><button class="button" onclick="openPopup('delay')">Mark Delay</button></td>
                    <td><button class="button" onclick="openPopup('status')">Mark Status</button></td>
                </tr>
                <!-- Add more rows as needed -->
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
        <h3>Mark Delay</h3>
        <div class="container">
            <p><strong>Service Name:</strong> XYZ</p>
            <p><strong>Current Delay:</strong> 10 minutes</p>
            <form onsubmit="submitDelay(); return false;">
                <label for="delay">Specify Delay:</label><br>
                <input type="text" id="delay" name="delay" placeholder="Enter delay in minutes" required><br>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>

    <!-- Status Pop-up -->
    <div class="popup" id="statusPopup">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <h3>Mark Status</h3>
        <div class="container">
            <p><strong>Service Name:</strong> XYZ</p>
            <p><strong>Current Status:</strong> Running</p>
            <form onsubmit="submitStatus(); return false;">
                <p><strong>Specify Status:</strong></p>
                <input type="hidden" name="bus_id">
                <select name="status_of_bus" id="">
                    <option value="Valid">Running</option>
                    <option value="Invalid">Facing Problem</option>
                </select>
                <input type="submit" value="Submit" name="submit">
            </form>
        </div>
    </div>

    <script>
        function openPopup(type) {
            // Clear previous content based on the type of pop-up
            if (type === 'delay') {
                const delayInput = document.getElementById('delay');
                if (delayInput) {
                    delayInput.value = ''; // Clear the delay input field
                }
            } else if (type === 'status') {
                const statusRadio = document.querySelector('input[name="status"]:checked');
                if (statusRadio) {
                    statusRadio.checked = false; // Uncheck any selected radio button
                }
            }

            // Show overlay and corresponding pop-up
            document.getElementById('overlay').classList.add('active');
            if (type === 'delay') {
                document.getElementById('delayPopup').classList.add('active');
            } else if (type === 'status') {
                document.getElementById('statusPopup').classList.add('active');
            }
        }

        function closePopup() {
            document.getElementById('overlay').classList.remove('active');
            document.getElementById('delayPopup').classList.remove('active');
            document.getElementById('statusPopup').classList.remove('active');
        }

        function submitDelay() {
            alert('Delay submitted');
            closePopup();
        }

        function submitStatus() {
            alert('Status submitted');
            closePopup();
        }
    </script>
</body>

</html>