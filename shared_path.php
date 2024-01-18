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

// Check if the path_id is set in the URL
if (isset($_GET['path_id'])) {
    $path_id = $_GET['path_id'];

    // Retrieve information about the learning path
    $sqlPath = "SELECT * FROM learning_paths WHERE id = ?";
    $stmtPath = $conn->prepare($sqlPath);
    $stmtPath->bind_param("i", $path_id);
    $stmtPath->execute();
    $resultPath = $stmtPath->get_result();

    if ($resultPath->num_rows > 0) {
        $rowPath = $resultPath->fetch_assoc();
        $title = $rowPath['title'];
        $description = $rowPath['description'];

        // Retrieve learning slots for the current learning path
        $sqlSlots = "SELECT * FROM learning_slots WHERE path_id = ?";
        $stmtSlots = $conn->prepare($sqlSlots);
        $stmtSlots->bind_param("i", $path_id);
        $stmtSlots->execute();
        $resultSlots = $stmtSlots->get_result();

        // Get upvotes and downvotes count
        $upvotes = getVoteCount($conn, $path_id);
        $downvotes = -$upvotes; // Assuming you store downvotes as negative values

        // Close the learning path result set
        $stmtPath->close();
    } else {
        // Path not found, handle accordingly (e.g., redirect to an error page)
        header("Location: error_page.php");
        exit();
    }
} else {
    // path_id not set in the URL, handle accordingly (e.g., redirect to an error page)
    header("Location: error_page.php");
    exit();
}

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Learning Path</title>
    <!-- Include Bootstrap CSS link here or your preferred styling -->
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
    <script src="node_modules/jquery/dist/jquery.slim.min.js"></script>
    <script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <?php
        if (isset($_SESSION["id"])) {
            // If a user is logged in, display the dashboard navigation
            echo "<a class='navbar-brand' href='#'>Your Dashboard</a>";
            echo "<button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarNav' aria-controls='navbarNav' aria-expanded='false' aria-label='Toggle navigation'>";
            echo "<span class='navbar-toggler-icon'></span>";
            echo "</button>";
            echo "<div class='collapse navbar-collapse' id='navbarNav'>";
            echo "<ul class='navbar-nav'>";
            echo "<li class='nav-item'>";
            echo "<a class='nav-link' href='create_learning_path.php'>Create Path</a>";
            echo "</li>";
            echo "<li class='nav-item'>";
            echo "<a class='nav-link' href='my_paths.php'>My Paths</a>";
            echo "</li>";
            echo "<li class='nav-item dropdown ml-auto'>";
            echo "<a class='nav-link dropdown-toggle' href='#' id='profileDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>";
            echo "<div class='profile-picture'>";
            if (isset($_SESSION["pfp_filename"]) && file_exists("uploads/" . $_SESSION["pfp_filename"])) {
                echo "<img src='uploads/{$_SESSION["pfp_filename"]}' alt='Profile Picture'>";
            } else {
                echo "<img src='uploads/default_profile_image.jpg' alt='Default Profile Picture'>";
            }
            echo "</div>";
            echo "</a>";
            echo "<div class='dropdown-menu' aria-labelledby='profileDropdown'>";
            echo "<a class='dropdown-item' href='profile_update.php'>Edit Profile</a>";
            echo "<a class='dropdown-item' href='logout.php'>Logout</a>";
            // Add more dropdown items as needed
            echo "</div>";
            echo "</li>";
            echo "<li>";
            echo "<form class='d-flex' action='results.php' method='GET'>";
            echo "<input class='form-control me-2' type='search' placeholder='Search' aria-label='Search' name='search_query'>";
            echo "<button class='btn btn-outline-success' type='submit'>Search</button>";
            echo "</form>";
            echo "</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            // If no user is logged in, display "Register" and "Login" buttons
            echo "<a class='navbar-brand' href='#'>Shared Learning Path</a>";
            echo "<ul class='navbar-nav ml-auto'>";
            echo "<li class='nav-item'>";
            echo "<a class='nav-link' href='register.php'>Register</a>";
            echo "</li>";
            echo "<li class='nav-item'>";
            echo "<a class='nav-link' href='login.php'>Login</a>";
            echo "</li>";
            echo "</ul>";
        }
        ?>
        <!-- You can add more navigation items if needed -->
    </div>
</nav></nav>

<div class="container mt-5">
    <h2><?php echo $title; ?></h2>
    <p><?php echo $description; ?></p>

    <!-- Display learning slots -->
    <?php
    while ($rowSlot = $resultSlots->fetch_assoc()) {
        echo "<p><strong>Link Title:</strong> {$rowSlot['link_title']}</p>";
        echo "<p><strong>Link URL:</strong> <a href='{$rowSlot['link_url']}' target='_blank'>{$rowSlot['link_url']}</a></p>";
    }

    // Display upvotes and downvotes count
    echo "<p><strong>Upvotes:</strong> {$upvotes}</p>";


    // Close the learning slots result set
    $resultSlots->close();
    ?>
</div>



</body>
</html>

<?php
// Close database connection
$conn->close();
?>
