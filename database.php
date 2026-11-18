<?php
$host     = "localhost";
$username = "Patrick";  
$password = "cookies";        
$database = "Theacup"; 

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>