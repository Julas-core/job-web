<?php
session_start();
include "db_conection.php";

if(!isset($_SESSION['user_id']) ||$_SESSION['rol'] !=='job_seeker'){
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$app=mysql_query($conect,"SELECT * FROM applications JOIN jobs ON application.job_id=job.id WHERE application.user_id='$id'");
?>