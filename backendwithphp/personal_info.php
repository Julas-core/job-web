<?php
session_start();
include "db_conection.php";

if(!isset($_SESSION['user_id']) ||$_SESSION['rol'] !=='job_seeker'){
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$user=mysql_fetch_assoc(mysql_query($conect,"SELECT * FROM users WHERE user_id='$id'"));

?>