<?php
// fetch_path_details.php

// Include database connection code here
$servername = "209.172.60.196";
$username = "f3432361_User";
$password = "12345";
$dbname = "f3432361_GroupAssign";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["path_id"])) {
    $path_id = $_POST["path_id"];

    // Perform a query to get the details of the selected path
    $sqlFetch = "SELECT * FROM learning_paths WHERE id = ?";
    $stmtFetch = $conn->prepare($sqlFetch);
    $stmtFetch->bind_param("i", $path_id);
    $stmtFetch->execute();
    $resultDetails = $stmtFetch->get_result();

    if ($resultDetails->num_rows > 0) {
        $rowDetails = $resultDetails->fetch_assoc();

        // Fetch learning slots for the path
        $sqlSlots = "SELECT * FROM learning_slots WHERE path_id = ?";
        $stmtSlots = $conn->prepare($sqlSlots);
        $stmtSlots->bind_param("i", $path_id);
        $stmtSlots->execute();
        $resultSlots = $stmtSlots->get_result();

        $slotsData = array();

        while ($rowSlot = $resultSlots->fetch_assoc()) {
            $slotsData[] = array(
                'link_title' => $rowSlot['link_title'],
                'link_url' => $rowSlot['link_url']
            );
        }

        // Add learning slots data to the response
        $rowDetails['learning_slots'] = $slotsData;

        // Return the details as JSON
        echo json_encode($rowDetails);
    } else {
        // Handle the case where path details are not found
        echo json_encode(array('error' => 'Path details not found.'));
    }

    $stmtFetch->close();
    $stmtSlots->close();
} else {
    // Handle invalid or missing parameters
    echo json_encode(array('error' => 'Invalid request.'));
}

$conn->close();
?>
