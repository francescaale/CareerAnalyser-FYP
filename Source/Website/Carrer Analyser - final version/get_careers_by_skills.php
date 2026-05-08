<?php 

include 'connection.php'; // Include the database connection file
header('Content-Type: application/json'); // Set response type to JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // Show all errors except notices and warnings - https://www.php.net/manual/en/function.error-reporting.php

try { // Start try block to handle errors safely

    if (!isset($_GET['skills'])) { // Check if the skills parameter is missing from the URL - https://www.php.net/manual/en/function.isset.php
        echo json_encode([]); // Return an empty JSON array
        exit; // Stop script execution
    } // End skills existence check

    if (is_array($_GET['skills'])) { // Check if skills were passed as an array
        $skills = array_map('trim', $_GET['skills']); // Remove extra spaces from each skill
    } else { // If skills were passed as a comma-separated string
        $skills = array_map('trim', explode(',', $_GET['skills'])); // Split string into array and trim each skill - https://www.php.net/manual/en/function.explode.php
    } // End skills format check

    if (count($skills) === 0) { // Check if the skills array is empty
        echo json_encode([]); // Return an empty JSON array
        exit; // Stop script execution
    } // End empty skills check

    $placeholders = implode(',', array_fill(0, count($skills), '?')); // Create SQL placeholders like ?,?,? based on number of skills - https://www.php.net/manual/en/function.implode.php

    $sql = "SELECT c.id, c.job_title
        FROM careers c
        JOIN career_skills cs ON c.id = cs.career_id
        JOIN skill_resources sr ON cs.skill_id = sr.id
        WHERE sr.skill_name IN ($placeholders)
        GROUP BY c.id
        HAVING COUNT(DISTINCT sr.skill_name) = ?
        ORDER BY c.job_title"; // SQL query to find careers matching all selected skills

    $stmt = $conn->prepare($sql); // Prepare the SQL query
    if (!$stmt) { // Check if statement preparation failed
        echo json_encode([]); // Return empty JSON array if query could not be prepared
        exit; // Stop script execution
    } // End statement check

    $types = str_repeat('s', count($skills)) . 'i'; // Build parameter types string: one 's' per skill, then one 'i' for skill count
    $params = array_merge($skills, [count($skills)]); // Combine skill names with total skill count
    $stmt->bind_param($types, ...$params); // Bind all parameters to the prepared statement - https://www.php.net/manual/en/mysqli-stmt.bind-param.php

    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the query results

    $careers = []; // Create empty array to store matching careers
    while ($row = $result->fetch_assoc()) { // Loop through each result row
        $careers[] = $row; // Add each career row to the careers array
    } // End result loop

    echo json_encode($careers); // Return matching careers as JSON
    exit; // Stop script execution

} catch (Exception $e) { // Catch any exception that happens in the try block - https://www.php.net/manual/en/language.exceptions.php
    echo json_encode([]); // Return empty JSON array if an error occurs
    exit; // Stop script execution
} // End catch block