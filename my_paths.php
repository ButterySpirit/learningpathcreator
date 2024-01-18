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

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_path"])) {
    $path_id = $_POST["delete_path"];

    // Delete learning slots associated with the learning path
    $sqlDeleteSlots = "DELETE FROM learning_slots WHERE path_id = ?";
    $stmtDeleteSlots = $conn->prepare($sqlDeleteSlots);
    $stmtDeleteSlots->bind_param("i", $path_id);
    $stmtDeleteSlots->execute();
    $stmtDeleteSlots->close();

    // Delete the learning path votes
    $sqlDeleteVotes = "DELETE FROM path_votes WHERE path_id = ?";
    $stmtDeleteVotes = $conn->prepare($sqlDeleteVotes);
    $stmtDeleteVotes->bind_param("i", $path_id);
    $stmtDeleteVotes->execute();
    $stmtDeleteVotes->close();

    // Delete the learning path
    $sqlDeletePath = "DELETE FROM learning_paths WHERE id = ?";
    $stmtDeletePath = $conn->prepare($sqlDeletePath);
    $stmtDeletePath->bind_param("i", $path_id);
    $stmtDeletePath->execute();
    $stmtDeletePath->close();
}

// Retrieve learning paths for the user
$sql = "SELECT * FROM learning_paths WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultPaths = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Learning Paths</title>

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
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">My Learning Paths</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <!-- Links from dashboard.php file -->
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create_learning_path.php">Create Path</a>
                </li>
                <!-- Profile Dropdown -->
                <li class="nav-item dropdown ml-auto">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="profile-picture">
                            <?php if(isset($_SESSION["pfp_filename"]) && file_exists("uploads/" . $_SESSION["pfp_filename"])): ?>
                                <img src="uploads/<?php echo $_SESSION["pfp_filename"]; ?>" alt="Profile Picture">
                            <?php else: ?>
                                <!-- Default image or placeholder if pfp_filename is not set or file not found -->
                                <img src="uploads/default_profile_image.jpg" alt="Default Profile Picture">
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="profileDropdown">
                        <a class="dropdown-item" href="profile_update.php">Edit Profile</a>
                        <a class="dropdown-item" href="logout.php">Logout</a>
                        <!-- Add more dropdown items as needed -->
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

<div class="container mt-5">
    <h2>My Learning Paths</h2>

    <!-- Display user's learning paths -->
    <?php
    // Loop through each learning path
    while ($rowPath = $resultPaths->fetch_assoc()) {
        echo "<div class='card mb-3'>";
        echo "<div class='card-body'>";
        echo "<h5 class='card-title'>{$rowPath['title']}</h5>";
        echo "<p class='card-text'>{$rowPath['description']}</p>";

        // Display edit link
        echo "<a href='edit_learning_path.php?path_id={$rowPath['id']}' class='btn btn-primary'>Edit</a>";

        // Display delete button
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='delete_path' value='{$rowPath['id']}'>";
        echo "<button type='submit' class='btn btn-danger'>Delete</button>";
        echo "</form>";

        // Retrieve learning slots for the current learning path
        $path_id = $rowPath['id'];
        $sqlSlots = "SELECT * FROM learning_slots WHERE path_id = ?";
        $stmtSlots = $conn->prepare($sqlSlots);
        $stmtSlots->bind_param("i", $path_id);
        $stmtSlots->execute();
        $resultSlots = $stmtSlots->get_result();

        // Display learning slots
        while ($rowSlot = $resultSlots->fetch_assoc()) {
            echo "<p class='card-text'><strong>Link Title:</strong> {$rowSlot['link_title']}</p>";
            echo "<p class='card-text'><strong>Link URL:</strong> {$rowSlot['link_url']}</p>";
        }

        // Close the learning slots result set
        $resultSlots->close();

        echo "</div>";
        echo "</div>";
    }

    // Close the learning paths result set
    $resultPaths->close();
    ?>
</div>

<!-- Include Bootstrap JS, Popper.js, and your custom JavaScript file here -->
<script src="node_modules/jquery/dist/jquery.min.js"></script>
<script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
<script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

</body>
</html>

<?php
// Close database connection
$conn->close();
?>
