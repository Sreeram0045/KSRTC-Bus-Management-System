<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../connection.php";
require_once "AdminPanel.php";

$adminPanel = new AdminPanel($connection);

// Handle delete request first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    $result = $adminPanel->deleteBusService($_POST['busId']);
    echo json_encode($result);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manage_station_master') {
    header('Content-Type: application/json');
    try {
        $editHtml = $adminPanel->manageStationMaster();
        echo json_encode([
            "success" => true,
            "html" => $editHtml
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error loading edit form: " . $e->getMessage()
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    header('Content-Type: application/json');
    try {
        $editHtml = $adminPanel->renderEditCard($_POST['busId']);
        echo json_encode([
            "success" => true,
            "html" => $editHtml
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error loading edit form: " . $e->getMessage()
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'input_new_bus') {
    header('Content-Type: application/json');
    try {
        $editHtml = $adminPanel->renderNewBusInputForm();
        echo json_encode([
            "success" => true,
            "html" => $editHtml
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error loading edit form: " . $e->getMessage()
        ]);
    }
    exit;
}


// Add this right after your other POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    if ($contentType === "application/json") {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (isset($data['action']) && $data['action'] === 'update') {
            header('Content-Type: application/json');
            $result1 = $adminPanel->editBusService();
            echo json_encode($result1);
            exit;
        }
        if (isset($data['action']) && $data['action'] === 'update_station_master_password') {
            header('Content-Type: application/json');
            $result2 = $adminPanel->updateStationMasterPassword();
            echo json_encode($result2);
            exit;
        }
        if (isset($data['action']) && $data['action'] === 'insert_new_bus') {
            header('Content-Type: application/json');
            $result3 = $adminPanel->insertNewBusService();
            $jsonError = json_last_error(); //Check for json errors
            if ($jsonError !== JSON_ERROR_NONE) {
                echo json_encode(['success' => false, 'message' => 'JSON Error: ' . json_last_error_msg()]);
                exit;
            }
            echo json_encode($result3);
            exit;
        }
    }
}
// Handle logout
if (isset($_POST['logout'])) {
    $adminPanel->logout();
}

// Fetch bus services
$adminPanel->fetchBusServices();
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
    <?php
    // Render components
    $adminPanel->renderHeader();
    $adminPanel->renderNav();
    $adminPanel->renderTable();

    ?>
    <div id="edit-form-container"></div>
    <script src="../js/adminManage.js"></script>
</body>

</html>