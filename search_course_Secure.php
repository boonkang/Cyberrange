<?php

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

// Escape special characters for safe XPath queries
$searchQueryEscaped = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');

// Calculate the offset for the XML query pagination
$offset = ($page - 1) * $subjectsPerPage;

function isSuspiciousQuery($searchQuery) {
    $suspiciousPatterns = [
        "or 1=1", "'", '"', '--', "/*", "*/", "contains("
    ];

    foreach ($suspiciousPatterns as $pattern) {
        if (stripos($searchQuery, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

function createXML($conn, $student_id, $includeStatusTwo = false) {
    $statusCondition = $includeStatusTwo ? '' : " WHERE s.subject_status = '1'";
    $sql = "
    SELECT s.subject_name, s.subject_description, s.subject_level, s.subject_status
    FROM subject s
    $statusCondition
    ";
    $result = $conn->query($sql);

    $xml = new SimpleXMLElement('<subjects/>');
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subject = $xml->addChild('subject');
            $subject->addChild('subject_name', htmlspecialchars($row['subject_name']));
            $subject->addChild('subject_description', htmlspecialchars($row['subject_description']));
            $subject->addChild('subject_level', htmlspecialchars($row['subject_level']));
            $subject->addChild('subject_status', htmlspecialchars($row['subject_status']));
        }
    }
    return $xml->asXML();
}

$student_id = $_SESSION['student_id'];
$suspiciousQuery = isSuspiciousQuery($searchQuery);
$xmlString = createXML($conn, $student_id, $suspiciousQuery);

$dom = new DOMDocument;
$dom->loadXML($xmlString);

$xpath = new DOMXPath($dom);
$xpathQuery = "//subject[contains(translate(subject_name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '" . strtolower($searchQueryEscaped) . "')]";

$subjects = $xpath->query($xpathQuery);
$totalSubjects = $subjects->length;
$totalPages = ($totalSubjects > 0) ? ceil($totalSubjects / $subjectsPerPage) : 1;

?>

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
                            $index = $offset + 1;
                            $end = min($offset + $subjectsPerPage, $totalSubjects);
                            for ($i = $offset; $i < $end; $i++) {
                                $subject = $subjects->item($i);
                                echo "<tr>";
                                echo "<th scope='row'>" . $index++ . "</th>";
                                echo "<td>" . $subject->getElementsByTagName('subject_name')[0]->nodeValue . "</td>";
                                echo "<td>" . $subject->getElementsByTagName('subject_description')[0]->nodeValue . "</td>";
                                echo "<td>" . $subject->getElementsByTagName('subject_level')[0]->nodeValue . "</td>";
                                echo "<td>" . $subject->getElementsByTagName('subject_status')[0]->nodeValue . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No subjects relevant to the search found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group" aria-label="Page group">
                        <?php
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $btnClass = $i == $page ? 'btn-primary' : 'btn-secondary';
                            echo "<a href='?page=$i&search=" . urlencode($searchQuery) . "' class='btn $btnClass'>$i</a>";
                        }
                        ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include_once '../utility/footer.php';
ob_end_flush();
?>
