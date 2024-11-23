<?php

/* Dont change this area */
include_once '../utility/header.php';
include_once '../utility/sidebar.php';
include_once '../utility/connection.php';

// Number of subjects to show per page
$subjectsPerPage = 10;

// Get the current page number from the URL, if not present default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;


// Get the search query from the form or URL (to preserve across pagination)
$searchQuery = isset($_POST['search']) ? trim($_POST['search']) : (isset($_GET['search']) ? trim($_GET['search']) : '');

// Calculate the offset for the XML query pagination
$offset = ($page - 1) * $subjectsPerPage;

// Helper function to detect suspicious patterns that resemble SQL injection attempts
function isSuspiciousQuery($searchQuery) {
    
    $suspiciousPatterns = [
        "or 1=1",     // Common SQL injection pattern
        "') or",      // Close query and inject
        "contains(",  // XPath injection pattern
        "or",         // General SQL injection with OR conditions
        "--",         // SQL comment syntax to bypass query conditions
        "/*"          // SQL comment block
    ];

    // Check if any suspicious pattern is found in the search query
    foreach ($suspiciousPatterns as $pattern) {
        if (stripos($searchQuery, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

/* Dont change this area */

/* Change this area to become secure source code */
function createXML($conn, $student_id, $includeStatusTwo = false) {
    // If we detect a suspicious query, allow subjects with status 2, otherwise exclude them
    $statusCondition = $includeStatusTwo ? '' : " WHERE s.subject_status = '1'";

    // SQL query to fetch all subject details, with or without status 2 based on the flag
    $sql = "
    SELECT s.subject_name, s.subject_description, s.subject_level, s.subject_status
    FROM subject s
    $statusCondition
    ";

    $result = $conn->query($sql);

    // Create the root XML element
    $xml = new SimpleXMLElement('<subjects/>');

    // Check if there are results from the query
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Add a new <subject> element for each row
            $subject = $xml->addChild('subject');

            // Add child elements to <subject> with corresponding data from the database
            $subject->addChild('subject_name', htmlspecialchars($row['subject_name']));
            $subject->addChild('subject_description', htmlspecialchars($row['subject_description']));
            $subject->addChild('subject_level', htmlspecialchars($row['subject_level']));
            $subject->addChild('subject_status', htmlspecialchars($row['subject_status']));
        }
    }

    return $xml->asXML(); // Return the XML as a string
}

// Get the student ID from the session (replace this according to your session management)
$student_id = $_SESSION['student_id'];

// Detect if the query is suspicious (indicating a potential injection attempt)
$suspiciousQuery = isSuspiciousQuery($searchQuery);

// Generate XML data from the database query, including status 2 subjects if the query is suspicious
$xmlString = createXML($conn, $student_id, $suspiciousQuery);

// Load the XML data into a DOMDocument
$dom = new DOMDocument;
$dom->loadXML($xmlString);

// Prepare the XPath query to search for subjects
$xpath = new DOMXPath($dom);

// Use the search query in the XPath to filter results
$xpathQuery = "//subject[contains(translate(subject_name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '" . strtolower($searchQuery) . "')]";

// Query the filtered results based on the search query
$subjects = $xpath->query($xpathQuery);

// Get the total number of filtered subjects
$totalSubjects = $subjects->length;

// Calculate total number of pages
$totalPages = ($totalSubjects > 0) ? ceil($totalSubjects / $subjectsPerPage) : 1;

/* Change this area to become secure source code */
?>


<!-- Dont change this area -->
<div class="container-fluid pt-4 px-4">
    <div class="bg-secondary text-center rounded p-4">
    <i class="bi bi-search display-1 text-primary"></i>
                        <h1 class="mb-4">Find Your Subject</h1>
                        <p class="mb-4">Here you can find a list of subjects available for your current level. Check out the subjects you're enrolled in and explore what each one offers!</p>
        <div class="bg-secondary rounded h-100 p-4">
            <form method="post" action="">
                <div class="mb-3">
                    <input name="search" class="form-control bg-dark border-0" type="search" placeholder="Search your subject" value="<?= htmlspecialchars($searchQuery) ?>" onkeydown="if (event.key === 'Enter') this.form.submit();">
                </div>
            </form>

            <?php if (!empty($searchQuery)): ?>
                <h6 class="bg-secondary text-center rounded p-4">Subject List</h6>
                  <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Subject Name</th>
                            <th scope="col">Subject Description</th>
                            <th scope="col">Subject Level</th>
                            <th scope="col">Subject Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($totalSubjects > 0) {
                            // Implement pagination by slicing the subjects node list
                            $index = $offset + 1;  // Start numbering the subjects from the current page's offset
                            $end = min($offset + $subjectsPerPage, $totalSubjects); // Ensure we don’t go out of bounds

                            for ($i = $offset; $i < $end; $i++) {
                                $subject = $subjects->item($i);
                                echo "<tr>";
                                echo "<th scope='row'>" . $index++ . "</th>";
                                echo "<td>" . $subject->getElementsByTagName('subject_name')[0]->nodeValue . "</td>";
                                echo "<td>" . $subject->getElementsByTagName('subject_description')[0]->nodeValue . "</td>";
                                echo "<td>" . $subject->getElementsByTagName('subject_level')[0]->nodeValue . "</td>";
                                echo "<td>" . $subject->getElementsByTagName('subject_status')[0]->nodeValue . "</td>"; // Add subject status
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No subjects relevant to the search found.</td></tr>"; // Updated colspan to 5 for the new column
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Pagination Button Toolbar -->
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group" aria-label="Page group">
                        <?php
                        // Dynamically generate pagination buttons
                        for ($i = 1; $i <= $totalPages; $i++) {
                            // Preserve the search query in the pagination links
                            $btnClass = $i == $page ? 'btn-primary' : 'btn-secondary';
                            echo "<a href='?page=$i&search=" . urlencode($searchQuery) . "' class='btn $btnClass'>$i</a>";
                        }
                        ?>
                    </div>
                </div>
                
            <?php else: ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Dont change this area -->

<?php
include_once '../utility/footer.php';
ob_end_flush(); // Flush the output buffer
?>
