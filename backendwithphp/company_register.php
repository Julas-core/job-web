<?php 
include "db_conection.php";
if(!isset($_POST['company_register.php'])){
    $comapny_name=$_POST['company_name'];
    $contact_name=$_POST['contact_name'];
    $description=$_POST['description'];
    $representative=$_POST['representative'];
    $location=$_POST['location'];
    mysql_query($conect,"INSERT INTO company ('company_name','contact_name','description','representative','location') values ($comapny_name,$conatct_name,$description,$representative,$location)");
    echo "registered successfully";
}
?>