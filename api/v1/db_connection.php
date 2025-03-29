<?php
$host = 'mysql.ct8.pl';
$user = 'm42569_gaouser';
$pass = 'Android 8.1.0';
$db = 'm42569_openguild';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
  ?>