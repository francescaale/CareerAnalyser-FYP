<?php 
include 'connection.php'; // Include database connection file

// Career name (string) // explaining career input
$career = trim($_GET['career'] ?? ''); // Get career from URL and remove extra spaces, or use empty string if not set

// Skills[] (array) // explaining user skills input
$userSkills = $_GET['skills'] ?? []; // Get selected skills from URL, or use empty array if not set
if (!is_array($userSkills)) { // Check if skills value is not already an array
    $userSkills = [$userSkills]; // Convert single skill value into an array
} // End skills array check

// ratings[SkillName] => 0..5 (array) // explaining user ratings input
$userRatings = $_GET['ratings'] ?? []; // Get ratings array from URL, or use empty array if not set
if (!is_array($userRatings)) { // Check if ratings value is not already an array
    $userRatings = []; // Reset to empty array for safety
} // End ratings array check

if ($career === '') { // Check if no career was selected
    die("No career selected."); // Stop script and show error message
} // End empty career check

$stmt = $conn->prepare("SELECT id FROM careers WHERE job_title = ? LIMIT 1"); // Prepare query to find selected career ID by exact job title
$stmt->bind_param("s", $career); // Bind career name as string parameter
$stmt->execute(); // Execute career lookup query
$res = $stmt->get_result(); // Get query result
$careerRow = $res->fetch_assoc(); // Fetch one matching row as associative array

if (!$careerRow) { // Check if no matching career was found
    die("Career not found."); // Stop script and show error message
} // End career existence check

$careerId = (int)$careerRow['id']; // Store selected career ID as integer

// Start SQL query string to fetch required skills for this career
$sql = " 
    SELECT sr.skill_name
    FROM career_skills cs
    JOIN skill_resources sr ON cs.skill_id = sr.id
    WHERE cs.career_id = ?
"; // End SQL query string
$stmt = $conn->prepare($sql); // Prepare query to fetch required skills
$stmt->bind_param("i", $careerId); // Bind career ID as integer parameter
$stmt->execute(); // Execute skills query
$res = $stmt->get_result(); // Get query result set

$careerSkills = []; // Create empty array to store required career skills
while ($row = $res->fetch_assoc()) { // Loop through each returned skill row
    if (!empty($row['skill_name'])) { // Check that skill_name is not empty
        $careerSkills[] = $row['skill_name']; // Add skill name to careerSkills array
    } // End skill_name check
} // End while loop


$userRadar = []; // Create empty array for user skill ratings on radar chart
$targetRadar = []; // Create empty array for target ratings on radar chart

foreach ($careerSkills as $skill) { // Loop through each required career skill
    $val = 0; // Default user rating for this skill is 0
    if (isset($userRatings[$skill])) { // Check if user submitted a rating for this skill
        $val = (int)$userRatings[$skill]; // Convert submitted rating to integer
        if ($val < 0) $val = 0; // Prevent rating going below 0
        if ($val > 5) $val = 5; // Prevent rating going above 5
    } // End user rating check

    $userRadar[] = $val; // Add user rating (0..5) to user radar array
    $targetRadar[] = 5;  // Add target rating of 5 for each required skill
} // End foreach loop


function normalizeSkill($skill) { // Function to normalize skill names for easier matching
    return preg_replace('/[^a-z0-9]/', '', strtolower((string)$skill)); // Convert to lowercase and remove non-alphanumeric characters
} // End normalizeSkill function

// Map normalized career skill => original // explaining normalized skill map
$careerSkillMap = []; // Create empty array to map normalized skill names to original names
foreach ($careerSkills as $s) { // Loop through each career skill
    $careerSkillMap[normalizeSkill($s)] = $s; // Store normalized skill as key and original skill as value
} // End foreach loop

$userSkillsNorm = array_map(fn($v) => normalizeSkill($v), $userSkills); // Normalize all user skills for comparison

$matched = []; // Create empty array for matched skills
$missing = []; // Create empty array for missing skills

foreach ($careerSkillMap as $norm => $original) { // Loop through normalized career skill map
    if (in_array($norm, $userSkillsNorm, true)) { // Check if normalized career skill exists in normalized user skills
        $matched[] = $original; // Add original skill name to matched list
    } else { // If user does not have that skill
        $missing[] = $original; // Add original skill name to missing list
    } // End skill match check
} // End foreach loop

$totalRequired = count($careerSkills); // Count total required skills for the career
$matchPercent = $totalRequired ? round((count($matched) / $totalRequired) * 100) : 0; // Calculate match percentage or use 0 if no required skills

// For JS radar (normalized keys) // explaining normalized keys for JS
$careerSkillsNorm = array_keys($careerSkillMap); // Get normalized skill keys from map

//GET COURSE LINKS FOR MISSING SKILLS
 // describing course link query section

$missingResources = []; // Create empty array to store course/resource links for missing skills

if (!empty($missing)) { // Only run query if there are missing skills

    $placeholders = implode(',', array_fill(0, count($missing), '?')); // Create SQL placeholders like ?,?,? based on number of missing skills
    $types = str_repeat('s', count($missing)); // Build parameter type string with one 's' per missing skill

    // Start SQL query string to get one course/resource URL per missing skill
    $sqlLinks = " 
        SELECT skill_name, MIN(resource_url) AS resource_url
        FROM skill_resources
        WHERE skill_name IN ($placeholders)
        GROUP BY skill_name
    "; // End SQL query string

    $stmtLinks = $conn->prepare($sqlLinks); // Prepare query for missing skill resources
    $stmtLinks->bind_param($types, ...$missing); // Bind each missing skill as string parameter
    $stmtLinks->execute(); // Execute resource query

    $resLinks = $stmtLinks->get_result(); // Get resource query result set
    while ($row = $resLinks->fetch_assoc()) { // Loop through each returned resource row
        $missingResources[$row['skill_name']] = $row['resource_url']; // Map skill name to its resource URL
    } // End while loop
} // End missing skills check
?> <!-- End PHP block -->


<!DOCTYPE html> <!-- Define HTML5 document -->
<html lang="en"> <!-- Start HTML document and set language to English -->

<head> <!-- Start head section -->

    <meta charset="UTF-8"> <!-- Set character encoding to UTF-8 -->
    <title>Results – Career Path Analyser</title> <!-- Set page title -->

    <link rel="stylesheet" href="css/style.css"> <!-- Link external CSS stylesheet -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Load Chart.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script> <!-- Load html2pdf library for PDF export -->
</head> <!-- End head section -->

<body> <!-- Start body content -->

    <!-- Header --> <!-- Header section -->
    <header class="header" id="siteHeader"> <!-- Start site header -->
        <a href="index.php" class="logo-link"> <!-- Logo link to homepage -->
            <h1 class="logo">Career Path Analyser</h1> <!-- Site logo/title -->
        </a> <!-- End logo link -->

        <!-- Hamburger button (mobile) --> <!-- Mobile menu button -->
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open menu"> <!-- Hamburger menu toggle button -->
            <span class="bar"></span> <!-- First hamburger line -->
            <span class="bar"></span> <!-- Second hamburger line -->
            <span class="bar"></span> <!-- Third hamburger line -->
        </button> <!-- End hamburger button -->

        <nav> <!-- Start navigation -->
            <ul class="nav-links" id="navLinks"> <!-- Navigation links list -->
                <li><a href="index.php">Home</a></li> <!-- Link to home page -->
                <li><a href="skills.php">Skills Input</a></li> <!-- Link to skills input page -->
                <li><a href="animated.php">Simulation</a></li> <!-- Link to simulation page -->
            </ul> <!-- End nav links list -->
        </nav> <!-- End navigation -->
    </header> <!-- End header -->

    <!-- Export area --> <!-- Main exportable report area -->
    <div class="results-wrap" id="exportArea"> <!-- Wrapper for report content and PDF export -->

        <section class="results-hero"> <!-- Top results summary section -->
            <div class="results-top"> <!-- Top row inside results summary -->

                <div class="results-title"> <!-- Title block -->
                    <h2><?= htmlspecialchars($career) ?></h2> <!-- Display selected career name safely -->
                    <p>Your skill match and what to improve next.</p> <!-- Subtitle/description -->
                </div> <!-- End title block -->

                <div class="pill-row"> <!-- Row for match percentage pill and progress bar -->
                    <div class="pill">🎯 Skill Match: <?= (int)$matchPercent ?>%</div> <!-- Display match percentage -->

                    <div class="progress" aria-label="Skill match progress"> <!-- Progress bar wrapper -->
                        <div id="progressBar"></div> <!-- Progress bar fill -->
                    </div> <!-- End progress wrapper -->
                </div> <!-- End pill row -->

                <div class="actions"> <!-- Action buttons wrapper -->
                    <button class="btn-secondary" type="button" onclick="window.location.href='skills.php'">← Edit skills</button> <!-- Button to go back and edit skills -->
                    <button id="exportBtn" class="cta-btn" type="button">📑 Export as PDF</button> <!-- Button to export report as PDF -->
                </div> <!-- End actions wrapper -->

            </div> <!-- End results-top -->
        </section> <!-- End results-hero -->

        <section class="results-grid"> <!-- Main results grid with charts and insights -->

            <div class="card"> <!-- Charts card -->
                <h3>Charts</h3> <!-- Charts section heading -->

                <div class="charts"> <!-- Charts container -->
                    <div class="chart-box"> <!-- Radar chart box -->
                        <h4>Skill Match (Radar)</h4> <!-- Radar chart title -->
                        <canvas id="radarChart"></canvas> <!-- Canvas for radar chart -->
                    </div> <!-- End radar chart box -->

                    <div class="chart-box"> <!-- Bar chart box -->
                        <h4>Matched vs Missing</h4> <!-- Bar chart title -->
                        <canvas id="barChart"></canvas> <!-- Canvas for bar chart -->
                    </div> <!-- End bar chart box -->
                </div> <!-- End charts container -->
            </div> <!-- End charts card -->

            <div class="card"> <!-- Insights card -->
                <h3>Insights</h3> <!-- Insights section heading -->
                <div class="insight" id="insightText"></div> <!-- Placeholder for generated insights text -->
            </div> <!-- End insights card -->

        </section> <!-- End results grid -->

        <section class="card" style="margin-top:1.25rem;"> <!-- Skills breakdown card -->
            <h3>Skills Breakdown</h3> <!-- Skills breakdown heading -->

            <div class="two-lists"> <!-- Two-column layout for matched and missing skills -->

                <div class="list-box"> <!-- Matched skills box -->
                    <h4>✅ Matched Skills</h4> <!-- Matched skills heading -->

                    <?php if (!count($matched)): ?> <!-- Check if there are no matched skills -->
                        <p style="color:#666;margin:0;">No matched skills yet — pick a few skills and try again.</p> <!-- Fallback text when no matched skills -->
                    <?php else: ?> <!-- If there are matched skills -->
                        <ul class="skills-list"> <!-- Matched skills list -->
                            <?php foreach ($matched as $s): ?> <!-- Loop through matched skills -->
                                <li> <!-- Start matched skill item -->
                                    <span><?= htmlspecialchars($s) ?></span> <!-- Display matched skill safely -->
                                    <span class="tag tag-ok">Matched</span> <!-- Matched status tag -->
                                </li> <!-- End matched skill item -->
                            <?php endforeach; ?> <!-- End matched skills loop -->
                        </ul> <!-- End matched skills list -->
                    <?php endif; ?> <!-- End matched skills condition -->
                </div> <!-- End matched skills box -->

                <div class="list-box"> <!-- Missing skills box -->
                    <h4>❌ Missing Skills</h4> <!-- Missing skills heading -->

                    <?php if (!count($missing)): ?> <!-- Check if there are no missing skills -->
                        <p style="color:#666;margin:0;">None 🎉 You match all required skills for this career.</p> <!-- Fallback text when no skills are missing -->
                    <?php else: ?> <!-- If there are missing skills -->
                        <ul class="skills-list"> <!-- Missing skills list -->
                            <?php foreach ($missing as $s): ?> <!-- Loop through missing skills -->
                                <li> <!-- Start missing skill item -->
                                    <span><?= htmlspecialchars($s) ?></span> <!-- Display missing skill safely -->

                                    <span class="skill-actions"> <!-- Wrapper for course link and status tag -->
                                        <?php if (!empty($missingResources[$s])): ?> <!-- Check if a course/resource exists for this missing skill -->
                                            <a class="course-link" href="<?= htmlspecialchars($missingResources[$s]) ?>"
                                               target="_blank" rel="noopener noreferrer"> <!-- Link to external course/resource -->
                                                Course → <!-- Course link text -->
                                            </a> <!-- End course link -->
                                        <?php else: ?> <!-- If no course/resource exists -->
                                            <span class="course-link disabled">No course</span> <!-- Disabled fallback when no resource is available -->
                                        <?php endif; ?> <!-- End course resource condition -->

                                        <span class="tag tag-miss">Missing</span> <!-- Missing status tag -->
                                    </span> <!-- End skill-actions wrapper -->
                                </li> <!-- End missing skill item -->
                            <?php endforeach; ?> <!-- End missing skills loop -->
                        </ul> <!-- End missing skills list -->
                    <?php endif; ?> <!-- End missing skills condition -->
                </div> <!-- End missing skills box -->

            </div> <!-- End two-lists -->
        </section> <!-- End skills breakdown card -->

    </div> <!-- End exportArea -->

    <footer class="footer"> <!-- Start footer -->
        <p>© 2026 Career Path Analyser | Final Year Project | Francesca Donea</p> <!-- Footer text -->
    </footer> <!-- End footer -->


    <script> // Start JavaScript block
        // PHP data // describing PHP-to-JS variables
        const careerName = <?= json_encode($career) ?>; // Selected career name from PHP
        const matchPercent = <?= (int)$matchPercent ?>; // Skill match percentage from PHP

        const careerSkills = <?= json_encode($careerSkills) ?>; // All required career skills from PHP
        const matched = <?= json_encode($matched) ?>; // Matched skills from PHP
        const missing = <?= json_encode($missing) ?>; // Missing skills from PHP

        // Radar arrays (0..5 and target 5) // describing radar chart data
        const userRadarValues = <?= json_encode($userRadar) ?>; // User ratings array for radar chart
        const targetRadarValues = <?= json_encode($targetRadar) ?>; // Target ratings array for radar chart

        // Progress bar // describing progress bar setup
        document.getElementById("progressBar").style.width = Math.max(0, Math.min(100, matchPercent)) + "%"; // Set progress bar width and keep it between 0 and 100

        // Wrap long labels into multiple lines (Chart.js supports arrays for multi-line labels) // describing label wrapping helper
        function wrapLabel(label, maxLen = 14) { // Function to split long chart labels into multiple lines
            const words = String(label).split(" "); // Split label into words
            const lines = []; // Create empty array for wrapped lines
            let line = ""; // Start building current line

            for (const w of words) { // Loop through each word
                const test = line ? `${line} ${w}` : w; // Test line with next word added
                if (test.length <= maxLen) { // If test line length is within limit
                    line = test; // Keep building current line
                } else { // If test line is too long
                    if (line) lines.push(line); // Save current line if it exists
                    line = w; // Start a new line with current word
                } // End line length check
            } // End for...of loop
            if (line) lines.push(line); // Push final line if it exists

            // If still one long chunk, hard split // describing fallback split
            if (lines.length === 1 && lines[0].length > maxLen) { // If label is still one long word/line
                const s = lines[0]; // Store long string
                return [s.slice(0, maxLen), s.slice(maxLen)]; // Split string into two pieces
            } // End hard split check
            return lines; // Return wrapped label lines
        } // End wrapLabel function

        // Use wrapped labels // describing wrapped chart labels
        const radarLabels = careerSkills.map(s => wrapLabel(s, 14)); // Create wrapped labels for each career skill

        // Radar chart (your rating vs target) // describing radar chart
        new Chart(document.getElementById("radarChart"), { // Create new radar chart
            type: "radar", // Set chart type to radar
            data: { // Start radar chart data
                labels: radarLabels, // Use wrapped skill labels
                datasets: [ // Start datasets array
                    { // Start user rating dataset
                        label: "Your rating", // Dataset label
                        data: userRadarValues, // User skill ratings 0..5
                        fill: true, // Fill area under radar line
                        backgroundColor: "rgba(109, 99, 188, 0.20)", // Fill color
                        borderColor: "#6d63bc", // Border color
                        borderWidth: 3, // Border width
                        pointRadius: 4, // Point size
                        pointBackgroundColor: "#6d63bc" // Point color
                    }, // End user dataset
                    { // Start target dataset
                        label: "Target (required)", // Dataset label
                        data: targetRadarValues, // Target ratings all set to 5
                        fill: false, // Do not fill target line
                        borderColor: "#e57373", // Border color
                        borderWidth: 3, // Border width
                        pointRadius: 3, // Point size
                        pointBackgroundColor: "#e57373", // Point color
                        borderDash: [6, 6] // Dashed line style
                    } // End target dataset
                ] // End datasets array
            }, // End radar chart data
            options: { // Start radar chart options
                responsive: true, // Make chart responsive
                maintainAspectRatio: false, // Allow chart to fit container height
                devicePixelRatio: 2, // Make chart sharper on screen
                plugins: { // Plugin options
                    legend: { // Legend settings
                        position: "top", // Place legend at top
                        labels: { font: { size: 12, weight: "bold" } } // Legend font styling
                    }, // End legend config
                    title: { // Title settings
                        display: true, // Show chart title
                        text: "Skill Match Overview", // Title text
                        font: { size: 14, weight: "bold" } // Title font styling
                    } // End title config
                }, // End plugins config
                scales: { // Scale settings
                    r: { // Radar scale settings
                        min: 0, // Minimum radar value
                        max: 5, // Maximum radar value
                        ticks: { // Tick mark settings
                            stepSize: 1, // Show ticks in steps of 1
                            backdropColor: "transparent", // Transparent tick background
                            font: { size: 11, weight: "bold" } // Tick font styling
                        }, // End ticks config
                        pointLabels: { // Radar point label settings
                            font: { size: 11, weight: "bold" } // Point label font styling
                        }, // End pointLabels config
                        grid: { color: "#d7d7d7" }, // Radar grid line color
                        angleLines: { color: "#d7d7d7" } // Radar angle line color
                    } // End radar scale config
                } // End scales config
            } // End radar chart options
        }); // End radar chart creation

        // Bar chart: matched vs missing // describing bar chart
        new Chart(document.getElementById("barChart"), { // Create new bar chart
            type: "bar", // Set chart type to bar
            data: { // Start bar chart data
                labels: ["Matched Skills", "Missing Skills"], // X-axis labels
                datasets: [{ // Start dataset array with one dataset
                    label: "Number of Skills", // Dataset label
                    data: [matched.length, missing.length], // Use counts of matched and missing skills
                    backgroundColor: [ // Bar fill colors
                        "rgba(109, 99, 188, 0.45)", // Matched bar fill color
                        "rgba(229, 115, 115, 0.45)" // Missing bar fill color
                    ], // End backgroundColor array
                    borderColor: ["#6d63bc", "#e57373"], // Bar border colors
                    borderWidth: 3, // Bar border width
                    borderRadius: 10 // Rounded bar corners
                }] // End dataset array
            }, // End bar chart data
            options: { // Start bar chart options
                responsive: true, // Make chart responsive
                maintainAspectRatio: false, // Allow chart to fit container height
                devicePixelRatio: 2, // Make chart sharper on screen
                plugins: { // Plugin settings
                    legend: { display: false }, // Hide legend
                    title: { // Title settings
                        display: true, // Show chart title
                        text: "Matched vs Missing Skills", // Title text
                        color: "#4f45a6", // Title color
                        font: { weight: "bold" } // Title font styling
                    } // End title config
                }, // End plugins config
                scales: { // Axis settings
                    x: { // X-axis config
                        title: { // X-axis title settings
                            display: true, // Show axis title
                            text: "Skill Category", // Axis title text
                            color: "#333", // Axis title color
                            font: { weight: "bold" } // Axis title font styling
                        }, // End x-axis title config
                        ticks: { // X-axis tick settings
                            color: "#222", // Tick color
                            font: { weight: "bold" } // Tick font styling
                        }, // End x-axis tick config
                        grid: { color: "#eee" } // X-axis grid line color
                    }, // End x-axis config
                    y: { // Y-axis config
                        beginAtZero: true, // Start y-axis at zero
                        title: { // Y-axis title settings
                            display: true, // Show axis title
                            text: "Number of Skills", // Axis title text
                            color: "#333", // Axis title color
                            font: { weight: "bold" } // Axis title font styling
                        }, // End y-axis title config
                        ticks: { // Y-axis tick settings
                            color: "#222", // Tick color
                            font: { weight: "bold" }, // Tick font styling
                            precision: 0 // Only show whole numbers
                        }, // End y-axis tick config
                        grid: { color: "#eee" } // Y-axis grid line color
                    } // End y-axis config
                } // End scales config
            } // End bar chart options
        }); // End bar chart creation



        // Insights text // describing insights generation
        let insightHTML = `<strong>You matched ${matched.length} out of ${careerSkills.length} required skills.</strong><br><br>`; // Build first part of insight text
        if (missing.length > 0) { // Check if there are missing skills
            insightHTML += `Next focus: <strong>${missing.slice(0, 6).join(", ")}${missing.length > 6 ? "…" : ""}</strong>`; // Add next skills to focus on
        } else { // If no skills are missing
            insightHTML += `You have <strong>all</strong> required skills for this career 🎉`; // Add full-match success message
        } // End missing skills condition
        document.getElementById("insightText").innerHTML = insightHTML; // Insert insight text into page

        // Export PDF // describing PDF export
        document.getElementById("exportBtn").onclick = function () { // Run when Export button is clicked
            const area = document.getElementById("exportArea"); // Get export area element
            html2pdf().set({ // Configure PDF export settings
                margin: 10, // Set PDF margin
                filename: `${careerName}_skills_analysis.pdf`, // Set output file name
                html2canvas: { scale: 2 }, // Improve rendering quality
                jsPDF: { unit: "mm", format: "a4", orientation: "portrait" } // Set PDF size and orientation
            }).from(area).save(); // Export selected area and download PDF
        }; // End export button click handler

        // Hamburger menu toggle (mobile) // describing mobile menu script
        const header = document.getElementById("siteHeader"); // Get header element
        const toggleBtn = document.getElementById("menuToggle"); // Get hamburger toggle button

        toggleBtn.addEventListener("click", function () { // Run when hamburger button is clicked
            header.classList.toggle("open"); // Toggle open class on header
        }); // End click event listener

        document.querySelectorAll("#navLinks a").forEach(function (link) { // Select all nav links inside navLinks and loop through them
            link.addEventListener("click", function () { // Run when a nav link is clicked
                header.classList.remove("open"); // Remove open class to close mobile menu
            }); // End nav link click event
        }); // End forEach loop
    </script> <!-- End JavaScript block -->
</body> <!-- End body -->
</html> <!-- End HTML document -->