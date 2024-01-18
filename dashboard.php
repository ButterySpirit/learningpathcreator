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

// Fetch learning paths and associated learning slots
$sql = "SELECT learning_paths.*, GROUP_CONCAT(learning_slots.link_title) AS slot_titles, GROUP_CONCAT(learning_slots.link_url) AS slot_urls,
    COALESCE(votes.vote_count, 0) AS vote_count
FROM learning_paths
LEFT JOIN learning_slots ON learning_paths.id = learning_slots.path_id
LEFT JOIN (
    SELECT path_id, SUM(vote_value) AS vote_count
    FROM path_votes
    GROUP BY path_id
) AS votes ON learning_paths.id = votes.path_id
GROUP BY learning_paths.id, votes.vote_count
ORDER BY learning_paths.id DESC

";

$resultPaths = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Include Bootstrap CSS link here -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Include Bootstrap JS and Popper.js scripts here -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.7.3/dist/js/bootstrap.min.js"></script>
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

            // Replace 'dashboard.php' with 'shared_path.php'
            var newPath = currentPath.replace('/dashboard.php', '/shared_path.php');

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

        function clonePath(pathId) {
            // Use AJAX to fetch the details of the selected path
            $.ajax({
                type: 'POST',
                url: 'fetch_path_details.php', // Update the path to the correct file
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
    <h1 class="mt-4">Welcome, <?php echo $_SESSION["username"]; ?>!</h1>
    <p>This is your dashboard. You are logged in.</p>

    <!-- Display learning paths -->
    <h2>Learning Paths</h2>
    <div class="row">
        <?php while ($row = $resultPaths->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $row['title']; ?></h5>
                        <p class="card-text"><?php echo $row['description']; ?></p>

                        <!-- Display learning slots -->
                        <h6>Learning Slots:</h6>
                        <?php
                        // Fetch learning slots separately for each path
                        $pathId = $row['id'];
                        $sqlSlots = "SELECT link_title, link_url FROM learning_slots WHERE path_id = $pathId";
                        $resultSlots = $conn->query($sqlSlots);

                        while ($slot = $resultSlots->fetch_assoc()): ?>
                            <p><?php echo $slot['link_title']; ?></p>
                            <p><a href="<?php echo $slot['link_url']; ?>" target="_blank"><?php echo $slot['link_url']; ?></a></p>
                        <?php endwhile; ?>

                        <!-- Display Clone Path button -->
                        <button class="btn btn-primary" onclick="clonePath(<?php echo $row['id']; ?>)">Clone Path</button>

                        <!-- Display upvote/downvote buttons -->
                        <div class="mt-3">
                            <button class="btn btn-success" onclick="votePath(<?php echo $row['id']; ?>, 1)">Upvote</button>
                            <button class="btn btn-danger" onclick="votePath(<?php echo $row['id']; ?>, -1)">Downvote</button>
                            <button class="btn btn-info" onclick="sharePath(<?php echo $row['id']; ?>)">Share</button>
                            <span class="ml-2">Upvotes: <?php echo $row['vote_count']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Add other dashboard content here -->
</div>

<!-- Include Bootstrap JS, Popper.js, and your custom JavaScript file here -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.7.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
