<?php

$servername = "localhost"; // Database server name
$username = "root"; // Database username
$password = ""; // Database password
$dbname = "careers"; // Database name

$conn = new mysqli($servername, $username, $password, $dbname); // Create new MySQL database connection

if ($conn->connect_error) { // Check if connection failed
  die('Connection failed: ' . $conn->connect_error); // Stop script and show error message
} // End connection check