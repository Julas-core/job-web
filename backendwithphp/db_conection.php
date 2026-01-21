<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "job_launch";

// Create connection using mysqli
$conect = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conect->connect_error) {
    die("Connection failed: " . $conect->connect_error);
}
?>