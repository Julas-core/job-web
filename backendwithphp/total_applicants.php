<?php
session_start();
include "db_conection.php";

if(!isset($_SESSION['user_id']) ||$_SESSION['rol'] !=='employer'){
    header("Location: login.php");
    exit;
}
$user_id=$_SESSION['user_id'];
$applicationquery=mysql_query($conect,"SELECT COUNT(*) AS total_applicants FROM applications JOIN jobs ON application.job_id=job_id WHERE company_id=$company_id");
$totaljobs=mysql_fetch_assoc($jobcounterquery) ['total_applicants'];


?>