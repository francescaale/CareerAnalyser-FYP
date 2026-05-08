<?php
include 'connection.php'; // Include database connection file

// Get selected career_id from URL (default UX Designer = 43) // describing career_id source
$career_id = isset($_GET['career_id']) ? (int) $_GET['career_id'] : 43; // Read career_id from URL, cast to integer, or use 43 as default - help reference https://www.php.net/manual/en/function.isset.php

// Fetch selected career - Prepare SQL statement to fetch one selected career
$stmt = $conn->prepare("
  SELECT id, job_title, alternative_titles, description, salary_min, salary_max, req_skills 
  FROM careers 
  WHERE id = ? 
  LIMIT 1 
"); // End prepared SQL string
$stmt->bind_param("i", $career_id); // Bind career_id as integer parameter
$stmt->execute(); // Run the prepared statement
$res = $stmt->get_result(); // Get query result set
$career = $res->fetch_assoc(); // Fetch one row as associative array - help reference https://www.php.net/manual/en/mysqli-result.fetch-assoc.php

if (!$career) { // If no career was found
    die("Career not found."); // Stop execution and show error message
} // End not found check

// Fetch all careers (for dropdown) // describing dropdown query
$allCareers = []; // Create empty array for all careers
$q = $conn->query("SELECT id, job_title FROM careers ORDER BY job_title ASC"); // Query all career IDs and titles sorted alphabetically
while ($row = $q->fetch_assoc()) { // Loop through each returned career row
    $allCareers[] = $row; // Add current row to allCareers array
} // End while loop

// Data for JS + chart // describing variables prepared for JavaScript/chart
$jobTitle = $career['job_title']; // Store selected career title
$jobSkills = array_filter(array_map('trim', explode(',', $career['req_skills'] ?? ''))); // Split required skills into array, trim spaces, remove empty entries - help reference https://www.php.net/manual/en/function.explode.php / https://www.php.net/manual/en/function.array-map.php

$salaryMin = (int) $career['salary_min']; // Store minimum salary as integer
$salaryMax = (int) $career['salary_max']; // Store maximum salary as integer

// Simple salary progression based on min/max (auto-updates per job) // describing salary chart values
$salaryLabels = ["Junior", "Mid-level", "Senior", "Lead", "Manager"]; // Labels for salary progression chart
$salaryValues = [ // Salary values for each label
    $salaryMin, // Junior salary
    (int) round($salaryMin * 1.20), // Mid-level salary estimated as 20% above minimum
    (int) round(($salaryMin + $salaryMax) / 2), // Senior salary estimated as midpoint between min and max
    (int) round($salaryMax * 0.90), // Lead salary estimated as 90% of maximum
    $salaryMax // Manager salary equals maximum salary
]; // End salary values array

// Make sure salary progression never goes backwards // describing salary correction - help reference https://www.php.net/manual/en/control-structures.for.php
for ($i = 1; $i < count($salaryValues); $i++) { // Loop from second salary value to the end
    if ($salaryValues[$i] < $salaryValues[$i - 1]) { // If current value is lower than previous one
        $salaryValues[$i] = $salaryValues[$i - 1]; // Replace it so chart never decreases
    } // End backwards check
} // End for loop
?> <!-- End PHP block -->

<!DOCTYPE html> <!-- Define HTML5 document -->
<html lang="en"> <!-- Start HTML document with English language -->

<head> <!-- Start head section -->
    <meta charset="UTF-8" /> <!-- Set character encoding to UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" /> <!-- Make page responsive on mobile devices -->
    <title>Career Day Simulation - Dashboard</title> <!-- Set browser tab title -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Load Chart.js library -->
    <link rel="stylesheet" href="css/style.css"> <!-- Link external CSS stylesheet -->
</head> <!-- End head section -->

<body> <!-- Start body content -->

    <!-- Header --> <!-- Header section -->
    <header class="header" id="siteHeader"> <!-- Start site header -->
        <a href="index.php" class="logo-link"> <!-- Logo link back to homepage -->
            <h1 class="logo">Career Path Analyser</h1> <!-- Site logo text -->
        </a> <!-- End logo link -->

        <!-- Hamburger button (mobile) --> <!-- Mobile menu button -->
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open menu"> <!-- Hamburger toggle button -->
            <span class="bar"></span> <!-- First hamburger bar -->
            <span class="bar"></span> <!-- Second hamburger bar -->
            <span class="bar"></span> <!-- Third hamburger bar -->
        </button> <!-- End hamburger button -->

        <nav> <!-- Start navigation -->
            <ul class="nav-links" id="navLinks"> <!-- Navigation links list -->
                <li><a href="index.php">Home</a></li> <!-- Link to home page -->
                <li><a href="skills.php">Skills Input</a></li> <!-- Link to skills input page -->
                <li><a href="animated.php">Simulation</a></li> <!-- Link to simulation page -->
            </ul> <!-- End nav links list -->
        </nav> <!-- End navigation -->
    </header> <!-- End header -->

    <div class="dash-wrap"> <!-- Main dashboard wrapper -->

        <!-- Title --> <!-- Dashboard title -->
        <div class="dash-title"> <!-- Dashboard title container -->
            <h2 id="pageTitle"><?= htmlspecialchars($jobTitle) ?> — Day Simulation</h2> <!-- Page title with selected job title -->
            <p>Dashboard view: milestones, progress, current task, and salary progression.</p> <!-- Subtitle/description -->
        </div> <!-- End title container -->

        <!-- Controls --> <!-- Controls section -->
        <div class="dash-controls"> <!-- Controls wrapper -->
            <div class="control-card"> <!-- Job select card -->
                <label for="jobSelect">Choose a job</label> <!-- Label for job dropdown -->
                <select id="jobSelect"> <!-- Job dropdown -->
                    <?php foreach ($allCareers as $c): ?> <!-- Loop through all careers -->
                        <option value="<?= (int) $c['id'] ?>" <?= ((int) $c['id'] === (int) $career_id) ? 'selected' : '' ?>> <!-- Dropdown option with selected check -->
                            <?= htmlspecialchars($c['job_title']) ?> <!-- Show career title -->
                        </option> <!-- End dropdown option -->
                    <?php endforeach; ?> <!-- End foreach loop -->
                </select> <!-- End job dropdown -->
            </div> <!-- End job control card -->

            <div class="control-card"> <!-- Level select card -->
                <label for="levelSelect">Choose a level</label> <!-- Label for level dropdown -->
                <select id="levelSelect"> <!-- Level dropdown -->
                    <option value="junior">Junior</option> <!-- Junior option -->
                    <option value="mid">Mid-level</option> <!-- Mid-level option -->
                    <option value="senior">Senior</option> <!-- Senior option -->
                    <option value="lead">Lead</option> <!-- Lead option -->
                    <option value="manager">Manager</option> <!-- Manager option -->
                </select> <!-- End level dropdown -->
            </div> <!-- End level control card -->
        </div> <!-- End controls wrapper -->

        <!-- Main Grid --> <!-- Main two-column layout -->
        <div class="dash-grid"> <!-- Start dashboard grid -->

            <!-- LEFT: Simulation --> <!-- Left column -->
            <section class="dash-card"> <!-- Left card for simulation -->
                <h3>Simulate a Day of Their Life</h3> <!-- Simulation section heading -->

                <div class="progress-row"> <!-- Progress row wrapper -->
                    <div id="progressText">Progress: 0%</div> <!-- Progress percentage text -->
                    <div class="dash-pill" id="currentLevel">Level: —</div> <!-- Current selected level display -->
                </div> <!-- End progress row -->

                <div class="progress-bar"> <!-- Progress bar wrapper -->
                    <div class="progress-fill" id="progressFill"></div> <!-- Progress bar fill -->
                </div> <!-- End progress bar -->

                <div class="path-area"> <!-- Timeline/milestones wrapper -->
                    <div class="path-line"></div> <!-- Decorative timeline line -->
                    <div class="milestones" id="milestones"></div> <!-- Milestones container -->
                </div> <!-- End path area -->

                <div class="current-task"> <!-- Current task display wrapper -->
                    <div class="dash-pill" id="currentTime">Time: —</div> <!-- Current time pill -->
                    <div class="big" id="currentTaskTitle">Click “Simulate Day” to start</div> <!-- Current task title -->
                    <div style="color:#555;" id="currentTip">Tip will appear here.</div> <!-- Current tip text -->
                    <div id="currentSkills"></div> <!-- Current skills pills container -->
                </div> <!-- End current task wrapper -->

                <div class="btn-row"> <!-- Buttons row -->
                    <button class="btn-primary" id="playBtn" type="button">Simulate Day</button> <!-- Start simulation button -->
                    <button class="btn-secondary" id="resetBtn" type="button">Reset</button> <!-- Reset simulation button -->
                </div> <!-- End button row -->

                <!-- Career Growth Insight --> <!-- Growth insight -->
                <div class="growth-insight-card"> <!-- Career growth insight card -->
                    <h3>📈 Career Growth Insight</h3> <!-- Growth insight heading -->

                    <div class="growth-level"> <!-- Junior level description -->
                        <h4>Junior Level</h4> <!-- Junior heading -->
                        <p> <!-- Junior paragraph -->
                            Focus on execution, learning tools, and building confidence. <!-- Junior description line -->
                            <br>The main goal is to understand processes and deliver assigned tasks effectively. <!-- Junior continuation -->
                        </p> <!-- End Junior paragraph -->
                    </div> <!-- End Junior level -->

                    <div class="growth-level"> <!-- Mid-level description -->
                        <h4>Mid-Level</h4> <!-- Mid-level heading -->
                        <p> <!-- Mid-level paragraph -->
                            Focus on ownership and independent decision-making. <!-- Mid-level description line -->
                            <br>Their responsibilities include handling more complex tasks and contributing actively to <!-- Mid-level continuation -->
                            team discussions. <!-- Mid-level continuation end -->
                        </p> <!-- End Mid-level paragraph -->
                    </div> <!-- End Mid-level -->

                    <div class="growth-level"> <!-- Senior level description -->
                        <h4>Senior Level</h4> <!-- Senior heading -->
                        <p> <!-- Senior paragraph -->
                            Focus on leadership, mentoring others, and solving complex problems. <!-- Senior description line -->
                            <br>Their role involves influencing direction and ensuring high-quality standards across <!-- Senior continuation -->
                            projects. <!-- Senior continuation end -->
                        </p> <!-- End Senior paragraph -->
                    </div> <!-- End Senior level -->

                    <div class="growth-level"> <!-- Lead level description -->
                        <h4>Lead Level</h4> <!-- Lead heading -->
                        <p> <!-- Lead paragraph -->
                            Focus on strategic direction, cross-team collaboration, and aligning projects with business <!-- Lead description line -->
                            goals. <!-- Lead continuation -->
                            <br>They guide teams and ensure consistency in delivery. <!-- Lead continuation -->
                        </p> <!-- End Lead paragraph -->
                    </div> <!-- End Lead level -->

                    <div class="growth-level"> <!-- Manager level description -->
                        <h4>Manager Level</h4> <!-- Manager heading -->
                        <p> <!-- Manager paragraph -->
                            Focus on vision, performance management, budgeting, and long-term strategy rather than <!-- Manager description line -->
                            day-to-day execution. <!-- Manager continuation -->
                            <br>They are responsible for team growth and organisational impact. <!-- Manager continuation -->
                        </p> <!-- End Manager paragraph -->
                    </div> <!-- End Manager level -->
                </div> <!-- End growth insight card -->

            </section> <!-- End left simulation section -->

            <!-- RIGHT: Salary + Overview --> <!-- Right column -->
            <aside class="dash-card"> <!-- Right card -->
                <h3>Salary Progression</h3> <!-- Salary section heading -->
                <div class="chart-wrap"> <!-- Chart wrapper -->
                    <canvas id="salaryChart"></canvas> <!-- Canvas for salary chart -->
                </div> <!-- End chart wrapper -->

                <hr style="border:none;border-top:1px solid #eee;margin:1rem 0;"> <!-- Divider line -->

                <h3>Career Overview</h3> <!-- Career overview heading -->
                <div class="career-info"> <!-- Career info wrapper -->
                    <p><strong>Job Title:</strong> <?= htmlspecialchars($career['job_title']) ?></p> <!-- Display job title -->
                    <p><strong>Salary Range:</strong> £<?= number_format((int) $career['salary_min']) ?> - <!-- Salary range start -->
                        £<?= number_format((int) $career['salary_max']) ?></p> <!-- Salary range end -->
                    <p><strong>Description:</strong> <!-- Description label -->
                        <?= htmlspecialchars($career['description'] ?: 'No description available.') ?></p> <!-- Description value or fallback -->
                    <p><strong>Alternative Titles:</strong> <!-- Alternative titles label -->
                        <?= htmlspecialchars($career['alternative_titles'] ?: 'None') ?></p> <!-- Alternative titles value or fallback -->
                    <p><strong>Key Skills:</strong> <?= htmlspecialchars($career['req_skills'] ?: 'None') ?></p> <!-- Required skills value or fallback -->
                </div> <!-- End career info -->
            </aside> <!-- End right aside -->

        </div> <!-- End dashboard grid -->
    </div> <!-- End dashboard wrapper -->

    <footer class="footer"> <!-- Start footer -->
        <p>© 2026 Career Path Analyser | Final Year Project | Francesca Donea</p> <!-- Footer text -->
    </footer> <!-- End footer -->

    <script> // Start script block for passing PHP data into JS
        // Pass PHP values safely into JavaScript // describing safe data transfer
        const careerId = <?= json_encode((string) $career_id) ?>; // Selected career ID as JavaScript string
        const jobTitle = <?= json_encode($jobTitle) ?>; // Selected job title as JavaScript string

        const salaryLabels = <?= json_encode($salaryLabels) ?>; // Salary chart labels array
        const salaryValues = <?= json_encode($salaryValues) ?>; // Salary chart values array
    </script> <!-- End PHP-to-JS values script -->

    <!-- LOAD ALL 49 SCHEDULES --> <!-- schedules.js -->
    <script src="js/schedules.js"></script> <!-- Load predefined schedules file -->
    

    <!-- DASHBOARD SIMULATION JS--> <!-- Dashboard simulation script -->
    <script> // Start simulation script
        /*GET ELEMENTS */ // Section for DOM element selection
        const jobSelect = document.getElementById("jobSelect"); // Job dropdown element
        const levelSelect = document.getElementById("levelSelect"); // Level dropdown element

        const playBtn = document.getElementById("playBtn"); // Simulate button element
        const resetBtn = document.getElementById("resetBtn"); // Reset button element

        const milestonesDiv = document.getElementById("milestones"); // Milestones container element

        const progressFill = document.getElementById("progressFill"); // Progress bar fill element
        const progressText = document.getElementById("progressText"); // Progress text element

        const currentTime = document.getElementById("currentTime"); // Current time element
        const currentLevel = document.getElementById("currentLevel"); // Current level element
        const currentTaskTitle = document.getElementById("currentTaskTitle"); // Current task title element
        const currentTip = document.getElementById("currentTip"); // Current tip element
        const currentSkills = document.getElementById("currentSkills"); // Current skills container

        const pageTitle = document.getElementById("pageTitle"); // Page title element

        /* HELPERS */ // Helpers section
        function prettyLevel(level) { // Convert level key into readable text
            if (level === "junior") return "Junior"; // Return Junior label
            if (level === "mid") return "Mid-level"; // Return Mid-level label
            if (level === "senior") return "Senior"; // Return Senior label
            if (level === "lead") return "Lead"; // Return Lead label
            return "Manager"; // Default return Manager
        } // End prettyLevel function

        function fallbackSchedule(level) { // Create fallback schedule if schedules.js has no data
            const label = prettyLevel(level); // Convert current level to readable label
            return [ // Return fallback array of schedule steps
                { time: "09:00 - 10:00", task: `${label}: Plan day + priorities`, tip: "Start with what matters most.", skills: ["Communication", "Organisation"] }, // First schedule item
                { time: "10:00 - 12:00", task: "Main work block", tip: "Focus and finish key tasks.", skills: ["Problem Solving"] }, // Second schedule item
                { time: "12:00 - 13:00", task: "Lunch break", tip: "Recharge.", skills: [] }, // Third schedule item
                { time: "13:00 - 15:00", task: "Collaboration + review", tip: "Work with others to improve results.", skills: ["Teamwork"] }, // Fourth schedule item
                { time: "15:00 - 17:00", task: "Wrap-up + reporting", tip: "Summarise progress clearly.", skills: ["Organisation"] }, // Fifth schedule item
            ]; // End fallback array
        } // End fallbackSchedule function

        /* UI RENDER */ // UI rendering section
        function setActiveMilestone(index) { // Highlight current milestone
            const all = document.querySelectorAll(".milestone"); // Select all milestone elements 
            all.forEach((el, i) => { // Loop through milestones
                if (i === index) el.classList.add("active"); // Add active class to selected milestone
                else el.classList.remove("active"); // Remove active class from others
            }); // End forEach loop
        } // End setActiveMilestone function - help reference  https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/forEach

        function renderMilestones(schedule) { // Render milestone elements for the schedule
            milestonesDiv.innerHTML = ""; // Clear old milestones

            schedule.forEach((step, index) => { // Loop through each schedule step
                const m = document.createElement("div"); // Create milestone element
                m.className = "milestone"; // Apply milestone class

                m.innerHTML = ` 
                <div class="dot"></div> <!-- Milestone dot -->
                <div class="time">${step.time}</div> <!-- Milestone time -->
                <div class="task">${step.task}</div> <!-- Milestone task -->
            `; // End template string

                m.addEventListener("click", () => { // Add click event to milestone
                    showStep(index, schedule); // Show that schedule step when clicked
                }); // End click event

                milestonesDiv.appendChild(m); // Add milestone to milestones container
            }); // End forEach loop

            setActiveMilestone(0); // Mark first milestone as active initially
        } // End renderMilestones function - help reference https://developer.mozilla.org/en-US/docs/Web/API/Node/appendChild

        function showStep(index, schedule) { // Show selected schedule step in current task panel
            const step = schedule[index]; // Get current step object

            currentTime.textContent = "Time: " + step.time; // Update current time display - https://developer.mozilla.org/en-US/docs/Web/API/Node/textContent
            currentTaskTitle.textContent = step.task; // Update current task title
            currentTip.textContent = "Tip: " + step.tip; // Update current tip

            currentSkills.innerHTML = ""; // Clear old current skills

            if (step.skills && step.skills.length > 0) { // If step has skills
                step.skills.forEach((s) => { // Loop through each skill
                    const pill = document.createElement("span"); // Create skill pill
                    pill.className = "dash-pill"; // Apply pill class
                    pill.textContent = s; // Set skill text
                    currentSkills.appendChild(pill); // Add pill to current skills container
                }); // End forEach loop
            } else { // If no skills exist for the step
                const pill = document.createElement("span"); // Create fallback pill
                pill.className = "dash-pill"; // Apply pill class
                pill.textContent = "No skills"; // Set fallback text
                currentSkills.appendChild(pill); // Add fallback pill
            } // End skills check

            setActiveMilestone(index); // Highlight current milestone

            const percent = Math.round(((index + 1) / schedule.length) * 100); // Calculate progress percentage
            progressFill.style.width = percent + "%"; // Update progress bar width
            progressText.textContent = "Progress: " + percent + "%"; // Update progress text
        } // End showStep function - help reference https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/forEach

        function resetSimulation(level) { // Reset simulation UI to default state
            progressFill.style.width = "0%"; // Reset progress bar - https://developer.mozilla.org/en-US/docs/Web/SVG/Reference/Attribute/width
            progressText.textContent = "Progress: 0%"; // Reset progress text

            currentLevel.textContent = "Level: " + prettyLevel(level); // Update level pill
            currentTime.textContent = "Time: —"; // Reset time text
            currentTaskTitle.textContent = "Click “Simulate Day” to start"; // Reset task prompt
            currentTip.textContent = "Tip will appear here."; // Reset tip text
            currentSkills.innerHTML = ""; // Clear skills display

            setActiveMilestone(0); // Reset active milestone to first one

            playBtn.disabled = false; // Re-enable play button - https://developer.mozilla.org/en-US/docs/Web/API/HTMLButtonElement/disabled
            playBtn.style.opacity = 1; // Reset play button opacity
        } // End resetSimulation function 

        /* BUILD DASHBOARD FROM schedules.js */ // Build dashboard section
        let currentSchedule = []; // Store currently active schedule
        let isPlaying = false; // Track whether simulation is currently running

        function buildDashboard() { // Build dashboard based on selected level and career
            const level = levelSelect.value; // Get current selected level

            currentLevel.textContent = "Level: " + prettyLevel(level); // Update current level - https://developer.mozilla.org/en-US/docs/Web/API/Node/textContent
            pageTitle.textContent = jobTitle + " — " + prettyLevel(level) + " Day Simulation"; // Update page title

            let scheduleFromJs = null; // Placeholder for schedule loaded from schedules.js

            if ( // Start condition to safely check schedule exists
                schedules[String(careerId)] && // Check career exists in schedules object
                schedules[String(careerId)].levels && // Check career has levels object
                schedules[String(careerId)].levels[level] // Check selected level exists
            ) { // End if condition
                scheduleFromJs = schedules[String(careerId)].levels[level]; // Load schedule from schedules.js
            } // End schedule existence check - https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Statements/if...else

            currentSchedule = (Array.isArray(scheduleFromJs) && scheduleFromJs.length) // Check if scheduleFromJs is a non-empty array - https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/isArray
                ? scheduleFromJs // Use loaded schedule if valid
                : fallbackSchedule(level); // Otherwise use fallback schedule

            renderMilestones(currentSchedule); // Render milestone UI
            resetSimulation(level); // Reset simulation for selected level
        } // End buildDashboard function  

        /* EVENTS */ // Events section

        jobSelect.addEventListener("change", () => { // Run when selected job changes
            const newId = jobSelect.value; // Get new job ID
            window.location.href = "animated.php?career_id=" + encodeURIComponent(newId); // Reload page with new career_id in URL - https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent
        }); // End jobSelect change event

        levelSelect.addEventListener("change", () => { // Run when selected level changes
            if (isPlaying) return; // Do nothing if simulation is currently playing
            buildDashboard(); // Rebuild dashboard for new level
        }); // End levelSelect change event

        playBtn.addEventListener("click", () => { // Run when Simulate Day button is clicked
            if (!currentSchedule || currentSchedule.length === 0) return; // Stop if no schedule exists

            isPlaying = true; // Mark simulation as running
            playBtn.disabled = true; // Disable play button while running
            playBtn.style.opacity = 0.6; // Fade play button visually

            let i = 0; // Start from first schedule step

            function runNext() { // Function to run steps one after another
                if (i >= currentSchedule.length) { // If all steps have been shown
                    isPlaying = false; // Mark simulation as stopped
                    playBtn.disabled = false; // Re-enable play button
                    playBtn.style.opacity = 1; // Restore play button opacity
                    return; // Stop function
                } // End finished simulation check

                showStep(i, currentSchedule); // Show current step
                i++; // Move to next step index
                setTimeout(runNext, 2400); // Wait 2.4 seconds before showing next step
            } // End runNext function

            runNext(); // Start simulation sequence
        }); // End play button click event

        resetBtn.addEventListener("click", () => { // Run when Reset button is clicked
            isPlaying = false; // Mark simulation as not playing
            resetSimulation(levelSelect.value); // Reset UI using selected level
        }); // End reset button click event

        buildDashboard(); // Build dashboard on initial page load
    </script> <!-- End dashboard simulation script -->

    <!-- SALARY CHART --> <!-- Salary chart section -->
    <script> // Start salary chart script
        const ctx = document.getElementById("salaryChart").getContext("2d"); // Get 2D drawing context from canvas

        new Chart(ctx, { // Create new Chart.js chart
            type: "bar", // Set chart type to bar
            data: { // Start chart data object
                labels: salaryLabels, // Use salaryLabels array for x-axis labels
                datasets: [{ // Start datasets array with one dataset
                    label: "Salary (£)", // Dataset label
                    data: salaryValues, // Dataset values

                    // faded fill + bold border // Dataset styling
                    backgroundColor: "rgba(109, 99, 188, 0.40)", // Bar fill color
                    borderColor: "#6d63bc", // Bar border color
                    borderWidth: 3, // Bar border width

                    borderRadius: 10, // Rounded bar corners
                    barThickness: 26 // Fixed bar thickness
                }] // End dataset object and array
            }, // End chart data object
            options: { // Start chart options
                responsive: true, // Make chart responsive
                maintainAspectRatio: false, // Allow chart to fill wrapper height
                layout: { padding: 30 }, // Add internal chart padding

                // reduce blur // Device pixel ratio
                devicePixelRatio: 2, // Use higher pixel ratio for sharper rendering

                plugins: { // Plugin configuration
                    legend: { // Legend options
                        display: true, // Show legend
                        labels: { // Legend label styles
                            color: "#222", // Legend text color
                            font: { size: 13, weight: "bold" } // Legend font settings
                        } // End legend label styles
                    }, // End legend config
                    title: { // Chart title options
                        display: true, // Show title
                        text: "Salary progression by level", // Title text
                        color: "#4f45a6", // Title color
                        font: { size: 16, weight: "bold" } // Title font settings
                    }, // End title config
                    tooltip: { // Tooltip options
                        callbacks: { // Tooltip callback functions
                            label: function (context) { // Custom tooltip label function
                                return " £" + Number(context.raw).toLocaleString(); // Format salary value with £ and commas
                            } // End tooltip label function
                        } // End tooltip callbacks
                    } // End tooltip config
                }, // End plugins config
                scales: { // Axis options
                    x: { // X-axis config
                        title: { // X-axis title config
                            display: true, // Show x-axis title
                            text: "Level", // X-axis title text
                            color: "#333", // X-axis title color
                            font: { size: 13, weight: "bold" } // X-axis title font
                        }, // End x title
                        ticks: { // X-axis tick label config
                            color: "#222", // X-axis tick color
                            font: { size: 12, weight: "bold" } // X-axis tick font
                        }, // End x ticks
                        grid: { color: "#eee" } // X-axis grid line color
                    }, // End x-axis config
                    y: { // Y-axis config
                        beginAtZero: true, // Start y-axis at zero
                        title: { // Y-axis title config
                            display: true, // Show y-axis title
                            text: "Salary (£)", // Y-axis title text
                            color: "#333", // Y-axis title color
                            font: { size: 13, weight: "bold" } // Y-axis title font
                        }, // End y title
                        ticks: { // Y-axis tick config
                            color: "#222", // Y-axis tick color
                            font: { size: 12, weight: "bold" }, // Y-axis tick font
                            callback: (value) => "£" + Number(value).toLocaleString() // Format y-axis values as currency
                        }, // End y ticks
                        grid: { color: "#eee" } // Y-axis grid line color
                    } // End y-axis config
                } // End scales config
            } // End options object
        }); // End chart creation - help reference https://www.w3schools.com/js/js_graphics_chartjs.asp
    </script> <!-- End salary chart script -->

    <!--  HAMBURGER MENU --> <!-- Hamburger menu section -->
    <script> // Start hamburger menu script
        const header = document.getElementById("siteHeader"); // Get header element
        const toggleBtn = document.getElementById("menuToggle"); // Get mobile menu toggle button

        toggleBtn.addEventListener("click", function () { // Run when hamburger button is clicked
            header.classList.toggle("open"); // Toggle open class on header - https://developer.mozilla.org/en-US/docs/Web/API/Window/open
        }); // End toggle button event

        // Close menu when you click a link (nice on mobile) // describing mobile UX
        document.querySelectorAll("#navLinks a").forEach(function (link) { // Select all nav links and loop through them
            link.addEventListener("click", function () { // Run when a nav link is clicked
                header.classList.remove("open"); // Close the mobile menu - https://developer.mozilla.org/en-US/docs/Web/API/Window/open
            }); // End nav link click event
        }); // End forEach loop
    </script> <!-- End hamburger script -->

</body> <!-- End body -->

</html> <!-- End HTML document -->