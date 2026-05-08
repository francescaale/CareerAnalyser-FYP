// Wait until the whole page is loaded before running the code
document.addEventListener("DOMContentLoaded", function () { // Run this function only after the HTML document is fully loaded

  // BASIC VARIABLES (STATE)
  let mode = "career"; // Stores the current mode: starts in "career" mode
  let careers = []; // Stores all careers loaded from get_careers.php
  let allSkills = []; // Stores all skills loaded from get_all_skills.php
  let selectedSkills = []; // Stores the skills the user has selected in Skills → Career mode

  // GET ELEMENTS
  const toggleBtn = document.getElementById("toggleModeBtn"); // Button used to switch between the two modes

  const careerGroup = document.getElementById("careerGroup"); // Section containing the career input
  const skillsGroup = document.getElementById("skillsGroup"); // Section containing the loaded skills
  const skillsInputGroup = document.getElementById("skillsInputGroup"); // Section containing skill search input
  const submitGroup = document.getElementById("submitGroup"); // Section containing submit button or submit-related controls

  const careerInput = document.getElementById("careerInput"); // Input where user types a career name
  const careerSuggestions = document.getElementById("careerSuggestions"); // Dropdown box for career suggestions
  const skillsContainer = document.getElementById("skillsContainer"); // Container where career skills will be shown

  const skillInput = document.getElementById("skillInput"); // Input where user types skill names
  const selectedSkillsBox = document.getElementById("selectedSkills"); // Container showing selected skill tags
  const careerResults = document.getElementById("careerResults"); // Container showing matched careers

  const hiddenCareer = document.getElementById("selectedCareerHidden"); // Hidden input storing selected career value for form submission

  // SAFETY CHECK
  if (!toggleBtn || !careerGroup || !skillsGroup || !skillsInputGroup || !careerInput || // Check first group of required elements
    !careerSuggestions || !skillsContainer || !skillInput || !selectedSkillsBox || // Check second group of required elements
    !careerResults || !hiddenCareer || !submitGroup) { // Check final group of required elements
    console.error("Some required HTML elements are missing. Check IDs in skills.php."); // Log error if any required element is missing
    return; // Stop running the script if required elements do not exist
  } // End safety check - help reference https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Control_flow_and_error_handling

  // LOAD CAREERS
  fetch("get_careers.php") // Request career data from PHP endpoint
    .then(res => res.json()) // Convert response to JSON
    .then(data => { careers = Array.isArray(data) ? data : []; }) // Save careers if data is an array, otherwise save empty array
    .catch(err => console.error("Error loading careers:", err)); // Log error if request fails - help reference https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch

  // LOAD ALL SKILLS
  fetch("get_all_skills.php") // Request all available skills from PHP endpoint
    .then(res => res.json()) // Convert response to JSON
    .then(data => { // Handle returned data
      allSkills = []; // Reset allSkills array before filling it
      if (Array.isArray(data)) { // Make sure returned data is an array
        for (let i = 0; i < data.length; i++) { // Loop through each returned skill object
          if (data[i].skill_name) allSkills.push(data[i].skill_name); // Add skill_name to allSkills if it exists
        } // End loop
      } // End array check
    }) // End success handler
    .catch(err => console.error("Error loading skills:", err)); // Log error if request fails - help reference https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch

  // TOGGLE MODES
  toggleBtn.addEventListener("click", function () { // Run when toggle button is clicked
    if (mode === "career") { // If currently in career mode
      mode = "skills"; // Switch mode to skills
      toggleBtn.textContent = "Skills → Career"; // Update button text

      careerGroup.style.display = "none"; // Hide career input section
      skillsGroup.style.display = "none"; // Hide selected career skills section
      skillsInputGroup.style.display = "block"; // Show skill input section
      submitGroup.style.display = "none"; // Hide submit group

      careerSuggestions.innerHTML = ""; // Clear any career suggestions
      careerSuggestions.classList.remove("show"); // Hide career suggestions dropdown
    } else { // If currently in skills mode
      mode = "career"; // Switch back to career mode
      toggleBtn.textContent = "Career → Skills"; // Update button text

      careerGroup.style.display = "block"; // Show career input section
      skillsGroup.style.display = "block"; // Show career skills section
      skillsInputGroup.style.display = "none"; // Hide skill input section
      submitGroup.style.display = "block"; // Show submit group

      hideSkillSuggestions(); // Hide skill suggestions dropdown
    } // End mode toggle condition
  }); // End toggle button listener

  // CAREER AUTOCOMPLETE
  careerInput.addEventListener("input", function () { // Run every time user types into career input
    careerSuggestions.innerHTML = ""; // Clear previous suggestions
    careerSuggestions.classList.remove("show"); // Hide suggestions before rebuilding them

    let q = careerInput.value.trim().toLowerCase(); // Get trimmed lowercase search query
    if (q.length < 2) return; // Stop if query is shorter than 2 characters

    let matches = []; // Store matching careers
    for (let i = 0; i < careers.length; i++) { // Loop through all careers
      let title = (careers[i].job_title || "").toLowerCase(); // Get lowercase career title safely
      if (title.includes(q)) matches.push(careers[i]); // Add career if title contains query
      if (matches.length >= 20) break; // Stop once 20 matches are found
    } // End career search loop
    if (matches.length === 0) return; // Stop if no matches found

    for (let i = 0; i < matches.length; i++) { // Loop through each match
      let div = document.createElement("div"); // Create suggestion item element
      div.className = "suggestion-item"; // Apply suggestion styling
      div.textContent = matches[i].job_title; // Show job title text

      div.addEventListener("click", function () { // Run when a suggestion is clicked
        careerInput.value = matches[i].job_title; // Fill input with chosen career
        hiddenCareer.value = matches[i].job_title; // Store chosen career in hidden input

        loadSkills(matches[i].job_title); // Load the skills for that chosen career

        careerSuggestions.innerHTML = ""; // Clear suggestions
        careerSuggestions.classList.remove("show"); // Hide suggestions box
      }); // End suggestion click event

      careerSuggestions.appendChild(div); // Add suggestion to dropdown
    } // End suggestions loop

    careerSuggestions.classList.add("show"); // Show suggestions dropdown
  }); // End career input listener

  careerInput.addEventListener("blur", function () { // Run when career input loses focus
    setTimeout(function () { // Delay hiding slightly so click on suggestion still works
      careerSuggestions.innerHTML = ""; // Clear suggestion items
      careerSuggestions.classList.remove("show"); // Hide suggestions dropdown
    }, 150); // Delay by 150 milliseconds
  }); // End blur listener - help reference https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener

  // LOAD SKILLS WITH CHECKBOX + RATING SLIDER
  function loadSkills(careerName) { // Function to load skills for a selected career
    skillsContainer.innerHTML = "<p>Loading skills...</p>"; // Show loading message while waiting

    fetch("get_skills.php?career_name=" + encodeURIComponent(careerName)) // Request skills for selected career - help reference https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent
      .then(res => res.json()) // Convert response to JSON
      .then(data => { // Handle returned skill data
        skillsContainer.innerHTML = ""; // Clear loading message / previous skills

        if (!Array.isArray(data) || data.length === 0) { // If no valid skills are returned
          skillsContainer.innerHTML = "<p>No skills found for this career.</p>"; // Show no skills message
          return; // Stop function here
        } // End empty data check

        // Create one row per skill
        for (let i = 0; i < data.length; i++) { // Loop through returned skills
          const name = data[i].skill_name; // Get current skill name
          if (!name) continue; // Skip if skill name is missing

          const label = document.createElement("label"); // Create label wrapper for skill row
          label.className = "skill-row"; // Apply row styling

          label.innerHTML = ` 
            <input type="checkbox" class="skill-check" name="skills[]" value="${escapeHtml(name)}"> 
            <span class="skill-name">${escapeHtml(name)}</span> 

            <input 
              type="range" 
              class="skill-rating" 
              name="ratings[${escapeHtml(name)}]" 
              min="0" 
              max="5" 
              step="1" 
              value="0" 
              disabled 
            > 
            <span class="rating-value">0</span> 
          `; // Create checkbox, skill name, range slider, and rating text 

          const checkbox = label.querySelector(".skill-check"); // Get checkbox element from this row
          const slider = label.querySelector(".skill-rating"); // Get slider element from this row
          const valueText = label.querySelector(".rating-value"); // Get rating text element from this row

          checkbox.addEventListener("change", function () { // Run when checkbox is checked or unchecked
            if (checkbox.checked) { // If checkbox is selected
              slider.disabled = false; // Enable rating slider

              // Default to 3 when first selected
              if (slider.value === "0") { // If slider still has default zero value
                slider.value = "3"; // Set slider to default rating of 3
                valueText.textContent = "3"; // Show 3 next to slider
              } // End default slider value check
            } else { // If checkbox is unselected
              slider.disabled = true; // Disable slider
              slider.value = "0"; // Reset slider back to zero
              valueText.textContent = "0"; // Show zero next to slider
            } // End checkbox state condition
          }); // End checkbox change listener

          slider.addEventListener("input", function () { // Run while slider value changes
            valueText.textContent = slider.value; // Update visible rating number
          }); // End slider input listener

          skillsContainer.appendChild(label); // Add completed skill row to container
        } // End skill loop
      }) // End success handler
      .catch(err => { // Handle fetch errors
        skillsContainer.innerHTML = "<p>Failed to load skills.</p>"; // Show failure message
        console.error("get_skills.php error:", err); // Log detailed error
      }); // End catch
  } // End loadSkills function - help reference https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch

  // ESCAPE TEXT
  function escapeHtml(str) { // Function to safely escape HTML special characters
    return String(str) // Convert input to string
      .replaceAll("&", "&amp;") // Replace & with HTML-safe version
      .replaceAll("<", "&lt;") // Replace < with HTML-safe version
      .replaceAll(">", "&gt;") // Replace > with HTML-safe version
      .replaceAll('"', "&quot;") // Replace double quote with HTML-safe version
      .replaceAll("'", "&#039;"); // Replace single quote with HTML-safe version
  } // End escapeHtml function - help reference https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/replaceAll

  // SKILL SUGGESTIONS (Skills → Career mode)
  function createSkillSuggestionsDiv() { // Create skill suggestions dropdown if it does not exist
    let div = document.createElement("div"); // Create dropdown div
    div.id = "skillSuggestions"; // Set dropdown ID
    div.className = "suggestions-box"; // Apply dropdown styles
    skillInput.parentNode.appendChild(div); // Add dropdown after skill input inside same parent
    return div; // Return created dropdown
  } // End createSkillSuggestionsDiv function

  function hideSkillSuggestions() { // Hide and clear skill suggestions
    let box = document.getElementById("skillSuggestions"); // Get skill suggestions element
    if (box) { // If box exists
      box.innerHTML = ""; // Clear suggestion items
      box.classList.remove("show"); // Hide dropdown
    } // End box existence check
  } // End hideSkillSuggestions function

  function updateSkillSuggestions(text) { // Update skill suggestions based on typed text
    let box = document.getElementById("skillSuggestions"); // Try to get existing suggestions box
    if (!box) box = createSkillSuggestionsDiv(); // Create it if it does not exist

    box.innerHTML = ""; // Clear old suggestions
    box.classList.remove("show"); // Hide before repopulating

    let q = (text || "").trim().toLowerCase(); // Get cleaned lowercase query
    if (q === "") return; // Stop if query is empty

    let matches = []; // Store matching skills
    for (let i = 0; i < allSkills.length; i++) { // Loop through all available skills
      let skill = allSkills[i]; // Get current skill
      if (skill.toLowerCase().includes(q) && !selectedSkills.includes(skill)) { // Match query and skip already selected skills
        matches.push(skill); // Add matching skill
      } // End condition
      if (matches.length >= 20) break; // Stop after 20 matches
    } // End skills loop
    if (matches.length === 0) return; // Stop if no matches found

    for (let i = 0; i < matches.length; i++) { // Loop through matches
      let div = document.createElement("div"); // Create suggestion item
      div.className = "suggestion-item"; // Apply item style
      div.textContent = matches[i]; // Show skill name
      div.addEventListener("click", function () { // Run when suggestion is clicked
        addSkill(matches[i]); // Add selected skill
      }); // End click listener
      box.appendChild(div); // Add suggestion to dropdown
    } // End matches loop

    box.classList.add("show"); // Show suggestions dropdown
  } // End updateSkillSuggestions function - help reference https://developer.mozilla.org/en-US/docs/Web/API/Document/createElement

  function addSkill(text) { // Add a skill to the selected list
    let skill = (text || "").trim(); // Clean entered skill text
    if (skill === "") return; // Stop if empty
    if (selectedSkills.includes(skill)) return; // Stop if skill is already selected

    if (!allSkills.includes(skill)) { // If typed skill is not in known skills list
      updateSkillSuggestions(skill); // Show possible matching suggestions instead
      return; // Stop here
    } // End unknown skill check

    selectedSkills.push(skill); // Add skill to selectedSkills array

    let tag = document.createElement("span"); // Create visible tag for selected skill
    tag.className = "skill-label"; // Apply tag styling
    tag.textContent = skill + " ×"; // Show skill name with remove symbol

    tag.addEventListener("click", function () { // Run when user clicks the skill tag
      selectedSkills = selectedSkills.filter(s => s !== skill); // Remove skill from selectedSkills array
      tag.remove(); // Remove tag from page

      let hiddenInputs = document.querySelectorAll('input[type="hidden"][name="skills[]"]'); // Find all hidden skill inputs 
      for (let i = 0; i < hiddenInputs.length; i++) { // Loop through hidden inputs
        if (hiddenInputs[i].value === skill) hiddenInputs[i].remove(); // Remove matching hidden input
      } // End hidden inputs loop

      findCareers(); // Refresh matched careers after removing skill
    }); // End tag click listener

    selectedSkillsBox.appendChild(tag); // Show tag in selected skills box

    let hidden = document.createElement("input"); // Create hidden input for form submission
    hidden.type = "hidden"; // Set input type to hidden
    hidden.name = "skills[]"; // Set input name for PHP array submission
    hidden.value = skill; // Store skill value
    skillInput.form.appendChild(hidden); // Add hidden input to the form

    skillInput.value = ""; // Clear skill input field
    hideSkillSuggestions(); // Hide suggestions dropdown
    findCareers(); // Refresh matched careers
  } // End addSkill function - help reference https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelectorAll

  skillInput.addEventListener("keydown", function (e) { // Listen for keyboard input in skill field
    if (e.key === "Enter") { // If Enter key is pressed
      e.preventDefault(); // Prevent form from submitting
      addSkill(skillInput.value); // Add the typed skill
    } // End Enter key condition
  }); // End keydown listener - help reference https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/key

  skillInput.addEventListener("input", function () { // Run whenever user types in skill input
    updateSkillSuggestions(skillInput.value); // Update skill suggestions based on input
  }); // End skill input listener - help reference https://developer.mozilla.org/en-US/docs/Web/API/Element/input_event

  skillInput.addEventListener("blur", function () { // Run when skill input loses focus
    setTimeout(function () { // Delay hiding so suggestion clicks still work
      hideSkillSuggestions(); // Hide suggestions dropdown
    }, 150); // Delay by 150 milliseconds
  }); // End blur listener - help reference - https://developer.mozilla.org/en-US/docs/Web/API/Element/blur_event

  // FIND CAREERS BY SKILLS
  function findCareers() { // Find matching careers based on selected skills
    if (selectedSkills.length === 0) { // If no skills are selected
      careerResults.innerHTML = ""; // Clear results area
      return; // Stop function
    } // End empty selection check - help reference https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Statements/if...else

    careerResults.innerHTML = "<p>Finding careers...</p>"; // Show loading message

    fetch("get_careers_by_skills.php?skills=" + encodeURIComponent(selectedSkills.join(","))) // Request matching careers for selected skills - help reference https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent / https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/join
      .then(res => res.json()) // Convert response to JSON
      .then(data => { // Handle returned career data
        careerResults.innerHTML = ""; // Clear loading message / previous results - help reference https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API

        if (!Array.isArray(data) || data.length === 0) { // If no valid careers returned
          careerResults.innerHTML = "<p>No matching careers found.</p>"; // Show no match message
          return; // Stop function
        } // End empty results check - 

        let title = document.createElement("p"); // Create title element
        title.className = "results-title"; // Apply title styling
        title.textContent = "Matching careers"; // Set title text
        careerResults.appendChild(title); // Add title to results container

        let grid = document.createElement("div"); // Create grid container for career cards
        grid.className = "results-grid"; // Apply grid styling
        careerResults.appendChild(grid); // Add grid to results container

        for (let i = 0; i < data.length && i < 30; i++) { // Loop through up to 30 careers
          let card = document.createElement("div"); // Create career card
          card.className = "career-card"; // Apply card styling

          let cTitle = document.createElement("div"); // Create career title element
          cTitle.className = "career-title"; // Apply title style
          cTitle.textContent = data[i].job_title; // Show career name

          let subtitle = document.createElement("div"); // Create subtitle element
          subtitle.className = "career-subtitle"; // Apply subtitle style
          subtitle.textContent = "Matched based on your selected skills"; // Show subtitle text

          let button = document.createElement("div"); // Create fake button text element
          button.className = "career-button"; // Apply button style
          button.textContent = "View required skills →"; // Show button text

          card.appendChild(cTitle); // Add title to card
          card.appendChild(subtitle); // Add subtitle to card
          card.appendChild(button); // Add button text to card

          card.addEventListener("click", function () { // Run when user clicks a career card
            hiddenCareer.value = data[i].job_title; // Save selected career in hidden input

            if (mode !== "career") toggleBtn.click(); // Switch back to career mode if currently in skills mode

            careerInput.value = data[i].job_title; // Fill career input with selected career
            loadSkills(data[i].job_title); // Load required skills for selected career

            skillsGroup.scrollIntoView({ behavior: "smooth", block: "start" }); // Smoothly scroll to skills section - help reference https://developer.mozilla.org/en-US/docs/Web/API/Element/scrollIntoView
          }); // End card click listener

          grid.appendChild(card); // Add card to results grid
        } // End careers loop
      }) // End success handler
      .catch(err => { // Handle fetch errors
        careerResults.innerHTML = "<p>Failed to find careers.</p>"; // Show failure message
        console.error("Career search error:", err); // Log detailed error
      }); // End catch - 
  } // End findCareers function

}); // End DOMContentLoaded listener