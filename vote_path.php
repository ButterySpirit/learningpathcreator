<?php
// Start session
session_start();

// Include database connection code here
$servername = "209.172.60.196";
$username = "f3432361_User";
$password = "12345";
$dbname = "f3432361_GroupAssign";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["path_id"]) && isset($_POST["vote_value"])) {
    // Check if the user is logged in
    if (!isset($_SESSION["id"])) {
        // Redirect to login page or handle unauthorized access
        echo "Error: User not logged in";
        exit();
    }

    $user_id = $_SESSION["id"];
    $path_id = $_POST["path_id"];
    $vote_value = $_POST["vote_value"];

    // Check if the user has already voted for this path
    $sqlCheckVote = "SELECT id, vote_value FROM path_votes WHERE user_id = ? AND path_id = ?";
    $stmtCheckVote = $conn->prepare($sqlCheckVote);
    $stmtCheckVote->bind_param("ii", $user_id, $path_id);
    $stmtCheckVote->execute();
    $resultCheckVote = $stmtCheckVote->get_result();

    if ($resultCheckVote->num_rows > 0) {
        // User has already voted, update the vote value
        $row = $resultCheckVote->fetch_assoc();
        $existingVoteId = $row["id"];
        $existingVoteValue = $row["vote_value"];

        // If the vote value is different, update the vote
        if ($existingVoteValue != $vote_value) {
            $sqlUpdateVote = "UPDATE path_votes SET vote_value = ? WHERE id = ?";
            $stmtUpdateVote = $conn->prepare($sqlUpdateVote);
            $stmtUpdateVote->bind_param("ii", $vote_value, $existingVoteId);

            if ($stmtUpdateVote->execute()) {
                echo "Vote updated successfully";
            } else {
                echo "Error updating vote: " . $stmtUpdateVote->error;
            }

            $stmtUpdateVote->close();
        } else {
            // User is trying to vote with the same value, handle accordingly
            echo "Error: You have already voted with the same value";
        }
    } else {
        // User has not voted before, insert a new vote
        $sqlInsertVote = "INSERT INTO path_votes (user_id, path_id, vote_value) VALUES (?, ?, ?)";
        $stmtInsertVote = $conn->prepare($sqlInsertVote);
        $stmtInsertVote->bind_param("iii", $user_id, $path_id, $vote_value);

        if ($stmtInsertVote->execute()) {
            echo "Vote submitted successfully";
        } else {
            echo "Error submitting vote: " . $stmtInsertVote->error;
        }

        $stmtInsertVote->close();
    }

    $stmtCheckVote->close();
} else {
    echo "Invalid request";
}

// Close database connection
$conn->close();
?>
