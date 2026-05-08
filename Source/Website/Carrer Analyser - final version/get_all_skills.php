<?php

include 'connection.php'; // Include database connection file

header('Content-Type: application/json'); // Set response type to JSON so JavaScript can read it

$query = "SELECT DISTINCT skill_name FROM skill_resources ORDER BY skill_name"; // SQL query to get unique skill names sorted alphabetically
$result = mysqli_query($conn, $query); // Execute query on the database connection

$skills = []; // Create empty array to store skills

while ($row = mysqli_fetch_assoc($result)) { // Loop through each row returned from query
    $skills[] = $row; // Add each row (skill_name) to the skills array
} // End while loop

echo json_encode($skills); // Convert PHP array into JSON format and output it

exit; // Stop script execution
