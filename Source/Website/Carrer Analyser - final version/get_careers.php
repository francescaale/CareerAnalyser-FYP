<?php

include 'connection.php'; // Include database connection file

header('Content-Type: application/json; charset=utf-8'); // Set response type to JSON with UTF-8 encoding

$query = "SELECT id, job_title FROM careers ORDER BY job_title ASC"; // SQL query to get all careers sorted alphabetically

$result = $conn->query($query); // Execute query using MySQLi

$careers = []; // Create empty array to store careers

if ($result && $result->num_rows > 0) { // Check if query returned results
    while ($row = $result->fetch_assoc()) { // Loop through each row in result set
        $careers[] = [ // Add formatted career data to array
            'id' => $row['id'], // Store career ID
            'job_title' => $row['job_title'] // Store career title
        ]; // End array item
    } // End while loop
} // End result check

echo json_encode($careers); // Convert careers array to JSON and output it

exit; // Stop script execution