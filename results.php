<?php
// Start session
session_start();

// Include database connection code here if not already included
$servername = "209.172.60.196";
$username = "f3432361_User";
$password = "12345";
$dbname = "f3432361_GroupAssign";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION["id"])) {
    // Redirect to login page or handle unauthorized access
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["id"];

// Handle search request
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["search_query"])) {
    $search_query = $_GET["search_query"];

    // Perform a search query on all learning paths based on title and description
    $sqlSearch = "SELECT * FROM learning_paths WHERE title LIKE ? OR description LIKE ?";
    $stmtSearch = $conn->prepare($sqlSearch);
    $search_param = "%{$search_query}%";
    $stmtSearch->bind_param("ss", $search_param, $search_param);
    $stmtSearch->execute();
    $resultPaths = $stmtSearch->get_result();
    $stmtSearch->close();
} else {
    // Redirect to the dashboard if no search query is provided
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <!-- Include Bootstrap CSS link here -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">

    <script src="node_modules/jquery/dist/jquery.slim.min.js"></script>
    <script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

    <style>
        /* Add this CSS for hover effect on the profile dropdown */
        .navbar-nav .nav-item.dropdown:hover .dropdown-menu {
            display: block;
        }
        /* Add this CSS to move the profile dropdown to the right */
        .ml-auto {
            margin-left: auto;
        }
        /* Add this CSS for circular profile picture */
        .profile-picture {
            border-radius: 50%;
            overflow: hidden;
            width: 30px; /* Adjust the size as needed */
            height: 30px; /* Adjust the size as needed */
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensure the image covers the entire container without distortion */
        }
    </style>

    <!-- Include the JavaScript for voting and sharing -->
    <script>
        function votePath(pathId, voteValue) {
            // Use AJAX to submit the vote asynchronously
            $.ajax({
                type: 'POST',
                url: 'vote_path.php', // Replace with the actual PHP file handling the vote
                data: { path_id: pathId, vote_value: voteValue },
                success: function(response) {
                    // Update the UI based on the response
                    alert(response);
                    // You might want to refresh the page or update the UI dynamically
                },
                error: function(error) {
                    // Handle errors, e.g., display an error message
                    alert('Error submitting vote: ' + error.responseText);
                }
            });
        }

        function sharePath(pathId) {
            // Generate the link for shared_path.php with the path_id
            // Get the current path
            var currentPath = window.location.pathname;

// Replace 'results.php' with 'shared_path.php'
            var newPath = currentPath.replace('/results.php', '/shared_path.php');

// Construct the new shareLink
            var shareLink = window.location.origin + newPath + '?path_id=' + pathId;



            // Create a temporary input element
            var tempInput = document.createElement('input');
            tempInput.value = shareLink;

            // Append the input element to the DOM
            document.body.appendChild(tempInput);

            // Select the input text
            tempInput.select();
            tempInput.setSelectionRange(0, 99999); /* For mobile devices */

            // Copy the text to the clipboard
            document.execCommand('copy');

            // Remove the temporary input element
            document.body.removeChild(tempInput);

            // Provide feedback to the user, e.g., show a tooltip or update UI
            alert('Link copied to clipboard: ' + shareLink);
        }

    </script>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Your Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="create_learning_path.php">Create Path</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_paths.php">My Paths</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item dropdown ml-auto">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="profile-picture">
                            <?php if(isset($_SESSION["pfp_filename"]) && file_exists("uploads/" . $_SESSION["pfp_filename"])): ?>
                                <img src="uploads/<?php echo $_SESSION["pfp_filename"]; ?>" alt="Profile Picture">
                            <?php else: ?>
                                <img src="uploads/default_profile_image.jpg" alt="Default Profile Picture">
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="profileDropdown">
                        <a class="dropdown-item" href="profile_update.php">Edit Profile</a>
                        <a class="dropdown-item" href="logout.php">Logout</a>
                    </div>
                </li>
                <li>
                    <form class="d-flex" action="results.php" method="GET">
                        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" name="search_query">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container mt-5">
    <h1 class="mt-4">Search Results</h1>

    <!-- Display search results -->
    <div class="row">
        <?php
        while ($rowPath = $resultPaths->fetch_assoc()) {
            echo '<div class="col-md-4 mb-4">';
            echo '<div class="card">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . $rowPath['title'] . '</h5>';
            echo '<p class="card-text">' . $rowPath['description'] . '</p>';

            // Retrieve learning slots for the current learning path
            $path_id = $rowPath['id'];
            $sqlSlots = "SELECT * FROM learning_slots WHERE path_id = ?";
            $stmtSlots = $conn->prepare($sqlSlots);
            $stmtSlots->bind_param("i", $path_id);
            $stmtSlots->execute();
            $resultSlots = $stmtSlots->get_result();

            // Display learning slots
            while ($rowSlot = $resultSlots->fetch_assoc()) {
                echo '<p class="card-text"><strong>Link Title:</strong> ' . $rowSlot['link_title'] . '</p>';
                echo '<p class="card-text"><strong>Link URL:</strong> <a href="' . $rowSlot['link_url'] . '" target="_blank">' . $rowSlot['link_url'] . '</a></p>';
            }

            // Display Clone Path button
            echo '<button class="btn btn-primary" onclick="clonePath(' . $path_id . ')">Clone Path</button>';

            // Display upvote/downvote buttons and count
            echo '<div class="mt-3">';
            echo '<button class="btn btn-success" onclick="votePath(' . $path_id . ', 1)">Upvote</button>';
            echo '<button class="btn btn-danger" onclick="votePath(' . $path_id . ', -1)">Downvote</button>';
            echo '<button class="btn btn-info" onclick="sharePath(' . $path_id . ')">Share</button>';
            echo '<span class="ml-2">Upvotes: ' . getVoteCount($conn, $path_id) . '</span>';
            echo '</div>';

            // Close the learning slots result set
            $resultSlots->close();

            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
    <script>
        function clonePath(pathId) {
            // Use AJAX to fetch the details of the selected path
            $.ajax({
                type: 'POST',
                url: 'fetch_path_details.php', // Replace with the actual PHP file to fetch path details
                data: { path_id: pathId },
                success: function(response) {
                    // Redirect to create_path.php with the fetched details
                    window.location.href = 'create_learning_path.php?clone_path=' + encodeURIComponent(response);
                },
                error: function(error) {
                    // Handle errors, e.g., display an error message
                    alert('Error fetching path details: ' + error.responseText);
                }
            });
        }
    </script>
</div>

<!-- Include Bootstrap JS, Popper.js, and your custom JavaScript file here -->
<script src="node_modules/jquery/dist/jquery.min.js"></script>
<script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
<script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

<?php
// Close database connection
$conn->close();

// Function to get the vote count for a path
function getVoteCount($conn, $pathId) {
    $sqlVotes = "SELECT SUM(vote_value) AS total_votes FROM path_votes WHERE path_id = ?";
    $stmtVotes = $conn->prepare($sqlVotes);
    $stmtVotes->bind_param("i", $pathId);
    $stmtVotes->execute();
    $resultVotes = $stmtVotes->get_result();
    $rowVotes = $resultVotes->fetch_assoc();
    $voteCount = $rowVotes['total_votes'];
    $resultVotes->close();
    return $voteCount;
}
?>
</body>
</html>
