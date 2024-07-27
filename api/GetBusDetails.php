<?php
    if(isset($_POST["submit_start_and_end"])){
        echo "Got the value";
        // echo $_POST["start_point"];
        // echo $_POST["end_point"];
        if ($_POST["start_point"] === $_POST["end_point"]){
            die("The start point and the end point are the same no services can be found");
        }
    }
?>