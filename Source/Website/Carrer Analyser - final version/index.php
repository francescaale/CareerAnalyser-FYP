<!DOCTYPE html> <!-- Define HTML5 document type -->
<html lang="en"> <!-- Start HTML document and set language to English -->

<head> <!-- Start head section -->
    <meta charset="UTF-8"> <!-- Set character encoding to UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Make layout responsive on mobile devices -->
    <title>Career Path Analyser</title> <!-- Set page title shown in browser tab -->
    <link rel="stylesheet" href="css/style.css"> <!-- Link external CSS stylesheet -->
    <?php include 'connection.php'; ?> <!-- Include PHP database connection file -->
</head> <!-- End head section -->

<body> <!-- Start body content -->

    <!-- Header --> <!-- Header section  -->
    <header class="header" id="siteHeader"> <!-- Start site header -->
        <a href="index.php" class="logo-link"> <!-- Logo link to homepage -->
            <h1 class="logo">Career Path Analyser</h1> <!-- Main site logo/title -->
        </a> <!-- End logo link -->

        <!-- Hamburger button (mobile) --> <!-- Mobile menu button  -->
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open menu"> <!-- Hamburger menu toggle button -->
            <span class="bar"></span> <!-- First hamburger line -->
            <span class="bar"></span> <!-- Second hamburger line -->
            <span class="bar"></span> <!-- Third hamburger line -->
        </button> <!-- End hamburger button -->

        <nav> <!-- Start navigation -->
            <ul class="nav-links" id="navLinks"> <!-- Navigation links list -->
                <li><a href="index.php">Home</a></li> <!-- Link to home page -->
                <li><a href="#features">Features</a></li> <!-- Link to features section on same page -->
                <li><a href="skills.php">Start Analysis</a></li> <!-- Link to skills input page -->
                <li><a href="animated.php">Simulation</a></li> <!-- Link to simulation page -->
                <li><a href="#about">About</a></li> <!-- Link to about section on same page -->
            </ul> <!-- End nav links list -->
        </nav> <!-- End navigation -->
    </header> <!-- End header -->


    <!-- Hero Section --> <!-- Hero section  -->
    <section class="hero"> <!-- Start hero section -->
        <h2>Find Your Skills. Bridge the Gap. Shape Your Future.</h2> <!-- Main hero heading -->
        <p> <!-- Hero description paragraph -->
            Discover your strengths, identify skill gaps for your dream career, <!-- First line of hero text -->
            and get personalised learning paths. <!-- Second line of hero text -->
        </p> <!-- End hero paragraph -->
        <a href="skills.php" class="cta-btn">Start Your Analysis</a> <!-- Call-to-action button linking to analysis page -->
    </section> <!-- End hero section -->

    <!-- Features Section --> <!-- Features section  -->
    <section id="features" class="features"> <!-- Start features section -->
        <h3>Key Features</h3> <!-- Features section heading -->
        <div class="feature-grid"> <!-- Grid container for feature cards -->
            <div class="feature-card"> <!-- Feature card 1 -->
                <span class="emoji">🔍</span> <!-- Feature icon -->
                <h4>Skill Gap Analysis</h4> <!-- Feature title -->
                <p>Compare your skills with job requirements.</p> <!-- Feature description -->
            </div> <!-- End feature card 1 -->
            <div class="feature-card"> <!-- Feature card 2 -->
                <span class="emoji">📊</span> <!-- Feature icon -->
                <h4>Data Visualisation</h4> <!-- Feature title -->
                <p>Radar & bar charts for easy insights.</p> <!-- Feature description -->
            </div> <!-- End feature card 2 -->
            <div class="feature-card"> <!-- Feature card 3 -->
                <span class="emoji">🎓</span> <!-- Feature icon -->
                <h4>Learning Suggestions</h4> <!-- Feature title -->
                <p>Find courses to find skill gaps.</p> <!-- Feature description -->
            </div> <!-- End feature card 3 -->
            <div class="feature-card"> <!-- Feature card 4 -->
                <span class="emoji">📑</span> <!-- Feature icon -->
                <h4>Export Reports</h4> <!-- Feature title -->
                <p>Download a personalised career report.</p> <!-- Feature description -->
            </div> <!-- End feature card 4 -->
        </div> <!-- End feature grid -->
    </section> <!-- End features section -->

    <!-- Why It Matters --> <!-- About/importance section  -->
    <section id="about" class="about"> <!-- Start about section -->
        <h3>Why It Matters</h3> <!-- About section heading -->
        <p> <!-- About paragraph -->
            The career landscape is changing faster than ever. By analysing your skills and <!-- First line of about text -->
            comparing them with industry requirements, you can stay competitive and take the right <!-- Second line of about text -->
            steps toward achieving your dream career. <!-- Third line of about text -->
        </p> <!-- End about paragraph -->
    </section> <!-- End about section -->


    <!-- Footer --> <!-- Footer section  -->
    <footer class="footer"> <!-- Start footer -->
        <p>© 2026 Career Path Analyser | Final Year Project | Francesca Donea</p> <!-- Footer text -->
    </footer> <!-- End footer -->

    <script> <!-- Start JavaScript block -->
        // Hamburger menu toggle (mobile) //  describing menu script
        const header = document.getElementById("siteHeader"); // Get header element
        const toggleBtn = document.getElementById("menuToggle"); // Get hamburger button element

        toggleBtn.addEventListener("click", function () { // Run when hamburger button is clicked
            header.classList.toggle("open"); // Toggle the open class on the header -  https://developer.mozilla.org/en-US/docs/Web/API/Window/open
        }); // End click event listener

        // Close menu when you click a link //  describing link click behavior
        document.querySelectorAll("#navLinks a").forEach(function (link) { // Select all nav links inside element with id navLinks and loop through them
            link.addEventListener("click", function () { // Run when a nav link is clicked
                header.classList.remove("open"); // Remove open class to close menu -  https://developer.mozilla.org/en-US/docs/Web/API/Window/open
            }); // End nav link click event
        }); // End forEach loop
    </script> <!-- End JavaScript block -->

</body> <!-- End body -->

</html> <!-- End HTML document -->