<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection for the `school` database
$servername = "localhost";
$username = "root";
$password = "";
$school_dbname = "school"; // Connect to school database

$conn_school = new mysqli($servername, $username, $password, $school_dbname);

if ($conn_school->connect_error) {
    die("Connection failed: " . $conn_school->connect_error);
}

// Function to fetch student data from the `school` database and convert to XML
function getMemberDataAsXML($conn_school) {
    $sql = "SELECT student_id, password, status FROM student";
    $result = $conn_school->query($sql);

    $xml = new SimpleXMLElement('<students></students>');

    while ($row = $result->fetch_assoc()) {
        $student = $xml->addChild('student');
        $student->addChild('student_id', $row['student_id']);
        $student->addChild('password', $row['password']);
        $student->addChild('status', $row['status']);
    }

    return $xml;
}

// Function to validate user using XPath
function validateUser($xml, $studentId, $password) {
    $xpath = $xml->xpath("//student[student_id/text()='$studentId' and password/text()='$password']");
    return count($xpath) > 0 ? $xpath[0] : false;
}

// Login function
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $password = $_POST['password'];

    try {
        $xml = getMemberDataAsXML($conn_school);
        
        if ($xml === false) {
            echo "Failed to fetch member data.";
        } else {
            $student = validateUser($xml, $student_id, $password);
            if ($student) {
                echo "Welcome";
            } else {
                echo "<div class='alert alert-danger'>The system bypassed authentication. Please try again.</div>";
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Close the `school` database connection
$conn_school->close();
?>