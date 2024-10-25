<?php

require_once "oop.php";

$stopsToVisit = ["Thiruvananthapuram", "Kollam", "Alappuzha", "Ernakulam", "Thrissur", "Kozhikode"];

$input = new InputFromDetails($stopsToVisit);

try {
    $result = $input->calculateTime($routes);
    echo "Travel times between stops: " . $result . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
