<?php
session_start();
include "db_conection.php";

if(!isset($_SESSION['user_id']) ||$_SESSION['rol'] !=='employer'){
    header("Location: login.php");
    exit;
}
$user_id=$_SESSION['user_id'];
$jobcounterquery=mysql_query($conect,"SELECT COUNT(*) AS total_jobs FROM jobs WHERE company_id=$company_id");
$totaljobs=mysql_fetch_assoc($jobcounterquery) ['total_jobs'];

?>