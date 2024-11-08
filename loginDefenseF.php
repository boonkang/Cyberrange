<?php
/* Please dont change this area !!! */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root"; 
$password = ""; 
$school_dbname = "school"; 

$conn_school = new mysqli($servername, $username, $password, $school_dbname);

if ($conn_school->connect_error) {
    die("Connection failed: " . $conn_school->connect_error);
}


function getMemberDataAsXML($conn_school) {
    $sql = "SELECT student_id, password, status FROM student";
    $result = $conn_school->query($sql);

    $xml = new SimpleXMLElement('<students></students>');

    while ($row = $result->fetch_assoc()) {
        $student = $xml->addChild('student');
        $student->addChild('student_id', htmlspecialchars($row['student_id'])); // Use htmlspecialchars to prevent XSS
        $student->addChild('password', htmlspecialchars($row['password'])); // Use htmlspecialchars to prevent XSS
        $student->addChild('status', htmlspecialchars($row['status'])); // Use htmlspecialchars to prevent XSS
    }

    return $xml;
}

/* Please dont change this area !!! */



function validateUser($xml, $studentId, $password) {

    $xpath = $xml->xpath("//student[student_id/text()='$studentId' and password/text()='$password']");
    return count($xpath) > 0 ? $xpath[0] : false;
}

/* Please dont change this area !!! */
// Login function
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // No input validation
    $student_id = $_POST['student_id'];
    $password = $_POST['password'];
    
    // Fetch member data from database and convert to XML
    $xml = getMemberDataAsXML($conn_school);
    
    if ($xml === false) {
        echo "Failed to fetch member data.";
    } else {
        $student = validateUser($xml, $student_id, $password);
        if ($student) {
            // Display "Welcome" on successful login
            echo "Welcome, " . htmlspecialchars($student_id) . "!";
        } else {
            echo "Invalid Student ID or Password.";
        }
    }
}

/* Please dont change this area !!! */

// Close the `school` database connection after all operations
$conn_school->close();
?>

<!--** Dont Delete this below part -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Sign In - XYZ School</title>
</head>
<body>
    <div class="container-fluid position-relative d-flex p-0">
        <!-- Sign In Start -->
        <div class="container-fluid">
            <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
                <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                    <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h3 class="text-primary"><i class="fa fa-user-edit me-2"></i>XYZ School</h3>
                            <h3>Sign In</h3>
                        </div>
                        <form method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Student ID" required>
                                <label for="student_id">Student ID</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                                <label for="floatingPassword">Password</label>
                            </div>
                            <button type="submit" class="btn btn-primary py-3 w-100 mb-4">Sign In</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sign In End -->
    </div>
</body>
</html>
