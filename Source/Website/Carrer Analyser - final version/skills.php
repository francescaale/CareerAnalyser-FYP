<!DOCTYPE html> <!-- Define HTML5 document type -->
<html lang="en"> <!-- Start HTML document and set language to English -->

<head> <!-- Start head section -->
    <meta charset="UTF-8"> <!-- Set character encoding to UTF-8 -->
    <title>Select Your Skills - Career Path Analyser</title> <!-- Set page title shown in browser tab -->
    <link rel="stylesheet" href="css/style.css"> <!-- Link external CSS stylesheet -->
</head> <!-- End head section -->

<body> <!-- Start body content -->

    <!-- Header --> <!-- Header section comment -->
    <header class="header" id="siteHeader"> <!-- Start site header -->
        <a href="index.php" class="logo-link"> <!-- Logo link to homepage -->
            <h1 class="logo">Career Path Analyser</h1> <!-- Main site logo/title -->
        </a> <!-- End logo link -->

        <!-- Hamburger button (mobile) --> <!-- Mobile menu button comment -->
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

    <section class="page-title"> <!-- Start page title section -->
        <h2>Enter Your Career Goal & Skills</h2> <!-- Page heading -->
        <p>Select your desired career and the skills you currently have.</p> <!-- Page description -->
    </section> <!-- End page title section -->

    <section id="form" class="form-section"> <!-- Start form section -->

        <form action="results.php" method="GET"> <!-- Form sends data to results.php using GET -->

            <!-- MODE TOGGLE --> <!-- Mode toggle comment -->
            <div class="form-group" style="text-align:center;"> <!-- Wrapper for toggle button -->
                <button type="button" id="toggleModeBtn" class="cta-btn"> <!-- Button to switch between modes -->
                    Career → Skills <!-- Default button text -->
                </button> <!-- End toggle button -->
            </div> <!-- End toggle wrapper -->

            <!-- CAREER → SKILLS MODE --> <!-- Career-to-skills mode comment -->
            <div id="careerGroup" class="form-group"> <!-- Career input group -->
                <label for="careerInput">Choose Career Goal:</label> <!-- Label for career input -->

                <!-- display only --> <!-- Visible career input comment -->
                <input type="text" id="careerInput" name="career_display" class="career-input" autocomplete="off"
                    placeholder="Start typing a career..."> <!-- Text input for career search/autocomplete -->

                <!-- submitted career --> <!-- Hidden submitted career comment -->
                <input type="hidden" id="selectedCareerHidden" name="career" value=""> <!-- Hidden input storing selected career -->

                <div id="careerSuggestions"></div> <!-- Container for career autocomplete suggestions -->
            </div> <!-- End career group -->

            <div id="skillsGroup" class="form-group"> <!-- Skills selection group -->
                <label>Select Your Current Skills (tick + rate yourself 0–5):</label> <!-- Label for skills area -->

                <!-- JS will fill this with checkbox + slider rows --> <!-- Skills container comment -->
                <div id="skillsContainer" class="checkbox-grid"> <!-- Container for skill checkboxes and sliders -->
                    <p>Select a career to view skills.</p> <!-- Default placeholder message -->
                </div> <!-- End skills container -->
            </div> <!-- End skills group -->

            <!-- SKILLS → CAREER MODE --> <!-- Skills-to-career mode comment -->
            <div id="skillsInputGroup" class="form-group" style="display:none;"> <!-- Hidden section for skills-to-career mode -->
                <label>Type a Skill:</label> <!-- Label for skill input -->
                <input type="text" id="skillInput" class="career-input" placeholder="Type a skill and press Enter"> <!-- Input for typing skills -->

                <div id="selectedSkills" class="selected-skills"></div> <!-- Container showing selected skill tags -->
                <div id="careerResults"></div> <!-- Container showing matched careers based on selected skills -->
            </div> <!-- End skillsInputGroup -->

            <!-- SUBMIT (hide in Skills → Career mode) --> <!-- Submit section comment -->
            <div class="form-group" id="submitGroup"> <!-- Submit button group -->
                <button type="submit" class="cta-btn">See My Results</button> <!-- Submit form button -->
            </div> <!-- End submit group -->

        </form> <!-- End form -->
    </section> <!-- End form section -->

    <footer class="footer"> <!-- Start footer -->
        <p>© 2026 Career Path Analyser | Final Year Project | Francesca Donea</p> <!-- Footer text -->
    </footer> <!-- End footer -->

    <script src="js/app.js"></script> <!-- Load external JavaScript file -->

    <script> <!-- Start inline JavaScript block -->
        // Hamburger menu toggle (mobile) // Comment describing mobile menu script
        const header = document.getElementById("siteHeader"); // Get header element
        const toggleBtn = document.getElementById("menuToggle"); // Get hamburger button element

        toggleBtn.addEventListener("click", function () { // Run when hamburger button is clicked
            header.classList.toggle("open"); // Toggle open class on header -  https://developer.mozilla.org/en-US/docs/Web/API/Window/open
        }); // End click event listener

        document.querySelectorAll("#navLinks a").forEach(function (link) { // Select all links inside navLinks and loop through them
            link.addEventListener("click", function () { // Run when a nav link is clicked
                header.classList.remove("open"); // Remove open class to close mobile menu -  https://developer.mozilla.org/en-US/docs/Web/API/Window/open
            }); // End nav link click event
        }); // End forEach loop
    </script> <!-- End inline JavaScript block -->

</body> <!-- End body -->

</html> <!-- End HTML document -->