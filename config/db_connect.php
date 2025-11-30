<?php
$host = "mysql";
$user = "root";
$pass = "password";
$dbname = "personal_collection_db"; 

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Koneksi gagal:" . mysqli_connect_error());
}
?>
