<?php

header('Content-Type: application/json; charset=utf-8'); // Set response type to JSON with UTF-8 encoding
include 'connection.php'; // Include database connection file

$career_name = isset($_GET['career_name']) ? trim($_GET['career_name']) : ''; // Get career_name from URL and trim spaces, or use empty string if missing - https://www.php.net/manual/en/function.isset.php

if ($career_name === '') { // Check if career_name is empty
    echo json_encode([]); // Return empty JSON array
    exit; // Stop script execution
} // End empty career name check

$stmt = $conn->prepare("SELECT id FROM careers WHERE LOWER(job_title) = LOWER(?) LIMIT 1"); // Prepare query to find exact career title match ignoring case - https://www.php.net/manual/en/mysqli.prepare.php
$stmt->bind_param('s', $career_name); // Bind career_name as string parameter
$stmt->execute(); // Execute the query - https://www.php.net/manual/en/mysqli-stmt.execute.php
$stmt->bind_result($career_id); // Bind the returned id to $career_id variable

if (!$stmt->fetch()) { // Check if no exact match was found - https://www.php.net/manual/en/mysqli-stmt.fetch.php
    $stmt->close(); // Close first statement
    $like = "%" . $career_name . "%"; // Create LIKE search pattern for partial match
    $stmt2 = $conn->prepare("SELECT id FROM careers WHERE job_title LIKE ? ORDER BY job_title LIMIT 1"); // Prepare fallback query for partial title match
    $stmt2->bind_param('s', $like); // Bind LIKE pattern as string parameter
    $stmt2->execute(); // Execute fallback query - https://www.php.net/manual/en/mysqli-stmt.execute.php
    $stmt2->bind_result($career_id); // Bind returned id to $career_id variable
    if (!$stmt2->fetch()) { // Check if no partial match was found - https://www.php.net/manual/en/mysqli-stmt.fetch.php
        echo json_encode([]); // Return empty JSON array
        $stmt2->close(); // Close second statement
        exit; // Stop script execution
    } // End no partial match check
    $stmt2->close(); // Close second statement after successful match
} else { // If exact match was found
    $stmt->close(); // Close first statement
} // End exact/partial match condition

//Get skills for careers
$query = "
  SELECT sr.skill_name
  FROM career_skills cs
  JOIN skill_resources sr ON cs.skill_id = sr.id
  WHERE cs.career_id = ?
  ORDER BY sr.skill_name ASC
"; // End SQL query string

$stmt3 = $conn->prepare($query); // Prepare query to fetch skill names for selected career
$stmt3->bind_param('i', $career_id); // Bind career_id as integer parameter - https://www.php.net/manual/en/mysqli-stmt.bind-param.php
$stmt3->execute(); // Execute the skills query
$result = $stmt3->get_result(); // Get query result set

$skills = []; // Create empty array to store returned skills
while ($row = $result->fetch_assoc()) { // Loop through each returned skill row - https://www.php.net/manual/en/mysqli-result.fetch-assoc.php
    $skills[] = [ // Add skill row to skills array
        'skill_name' => $row['skill_name'] // Store skill_name value
    ]; // End array item
} // End while loop
$stmt3->close(); // Close third statement

echo json_encode($skills); // Convert skills array to JSON and output it - https://www.php.net/manual/en/function.json-encode.php
exit; // Stop script execution