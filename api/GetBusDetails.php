<?php
    require_once "../connection.php";
    if(isset($_POST["submit_start_and_end"])){
        // checks if the starting point and end point are the same and if it is then stops execution
        if ($_POST["start_point"] === $_POST["end_point"]){
            die("The start point and the end point are the same no services can be found");
        }
    }
?>