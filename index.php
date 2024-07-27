<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSRTC</title>
</head>
<body>
    <!-- This would be the home page where the user finds initially and in this page the result of the search is found furthermore -->
     <form action="./api/GetBusDetails.php" method="post">
        <label>Enter start point: </label>
        <select name="start_point" id="start_name_id">
            <option value="Thiruvananthapuram">Thiruvananthapuram (Trivandrum)</option>
            <option value="Kollam">Kollam</option>
            <option value="Pathanamthitta">Pathanamthitta</option>
            <option value="Alappuzha">Alappuzha (Alleppey)</option>
            <option value="Kottayam">Kottayam</option>
            <option value="Idukki">Idukki</option>
            <option value="Ernakulam">Ernakulam (Kochi)</option>
            <option value="Thrissur">Thrissur</option>
            <option value="Palakkad">Palakkad</option>
            <option value="Malappuram">Malappuram</option>
            <option value="Kozhikode">Kozhikode</option>
            <option value="Wayanad">Wayanad</option>
            <option value="Kannur">Kannur</option>
            <option value="Kasaragod">Kasaragod</option>
        </select>
        <label>Enter end point: </label>
        <select name="end_point" id="end_name_id" default="Kasaragod">
            <option value="Thiruvananthapuram">Thiruvananthapuram (Trivandrum)</option>
            <option value="Kollam">Kollam</option>
            <option value="Pathanamthitta">Pathanamthitta</option>
            <option value="Alappuzha">Alappuzha (Alleppey)</option>
            <option value="Kottayam">Kottayam</option>
            <option value="Idukki">Idukki</option>
            <option value="Ernakulam" selected>Ernakulam (Kochi)</option>
            <option value="Thrissur">Thrissur</option>
            <option value="Palakkad">Palakkad</option>
            <option value="Malappuram">Malappuram</option>
            <option value="Kozhikode">Kozhikode</option>
            <option value="Wayanad">Wayanad</option>
            <option value="Kannur">Kannur</option>
            <option value="Kasaragod">Kasaragod</option>
        </select>
        <input type="submit" value="Search" name="submit_start_and_end">
     </form>
</body>
</html>