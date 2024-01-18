<?php
// Start session
session_start();

// Update these values with your actual remote database credentials
$servername = "209.172.60.196";
$username = "f3432361_User";
$password = "12345";
$dbname = "f3432361_GroupAssign";

// Check if the user is logged in
if (!isset($_SESSION["id"])) {
    // Redirect to the login page if not logged in
    header("Location: login.php");
    exit();
}

// Create a connection to the remote MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for form autofill
$title = "";
$description = "";
$linkTitles = [];
$linkUrls = [];

// Check if clone data is provided in the URL
if (isset($_GET['clone_path'])) {
    // Decode the JSON data
    $cloneData = json_decode($_GET['clone_path'], true);

    // Autofill the form fields with the cloned data
    $title = $cloneData['title'];
    $description = $cloneData['description'];

    // Autofill learning slots if available
    if (isset($cloneData['learning_slots'])) {
        $learningSlots = $cloneData['learning_slots'];
        foreach ($learningSlots as $slot) {
            $linkTitles[] = $slot['link_title'];
            $linkUrls[] = $slot['link_url'];
        }
    }
}

// Fetch learning slots from the database
if (isset($_GET['path_id'])) {
    $pathId = $_GET['path_id'];

    $sql = "SELECT link_title, link_url FROM learning_slots WHERE path_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pathId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Reset linkTitles and linkUrls arrays
    $linkTitles = [];
    $linkUrls = [];

    while ($row = $result->fetch_assoc()) {
        $linkTitles[] = $row['link_title'];
        $linkUrls[] = $row['link_url'];
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_path'])) {
    // Get user ID from the session
    $userId = $_SESSION['id']; // Update 'id' with your actual session variable name

    // Get form data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $linkTitles = $_POST['link_title'];
    $linkUrls = $_POST['link_url'];

    // Insert data into the learning_paths table
    $sql = "INSERT INTO learning_paths (user_id, title, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $title, $description);
    $stmt->execute();

    // Get the last inserted path_id
    $pathId = $stmt->insert_id;

    // Insert data into the learning_slots table
    $sql = "INSERT INTO learning_slots (path_id, link_title, link_url) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    for ($i = 0; $i < count($linkTitles); $i++) {
        $stmt->bind_param("iss", $pathId, $linkTitles[$i], $linkUrls[$i]);
        $stmt->execute();
    }

    // Redirect to another page or display a success message
    header("Location: my_paths.php");
    exit();
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Paths</title>

    <!-- Include Bootstrap CSS link here -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Include Bootstrap JS, Popper.js, and your custom JavaScript file here -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
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

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Your Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <!-- Create Path Button -->
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>

                <!-- My Paths Button -->
                <li class="nav-item">
                    <a class="nav-link" href="my_paths.php">My Paths</a>
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
    <h2>Learning Paths</h2>

    <!-- Form to create or edit learning paths -->
    <form method="post" action="">
        <input type="hidden" id="pathId" name="path_id" value="<?php echo $pathId; ?>">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea class="form-control" id="description" name="description" rows="4"><?php echo $description; ?></textarea>
        </div>

        <!-- Dynamic fields for learning slots -->
        <div id="learningSlotsContainer">
            <?php
            // Autofill learning slot fields
            foreach ($linkTitles as $index => $linkTitle) {
                echo '
                    <div class="form-group">
                        <label for="linkTitle' . $index . '">Link Title:</label>
                        <input type="text" class="form-control" id="linkTitle' . $index . '" name="link_title[]" value="' . $linkTitle . '">
                        <label for="linkUrl' . $index . '">Link URL:</label>
                        <input type="text" class="form-control" id="linkUrl' . $index . '" name="link_url[]" value="' . $linkUrls[$index] . '">
                    </div>';
            }
            ?>
        </div>

        <button type="button" class="btn btn-primary" onclick="addSlot()">Add Slot</button>
        <button type="button" class="btn btn-danger" onclick="removeSlot()">Remove Slot</button>
        <button type="submit" class="btn btn-success" name="create_path">Save Path</button>
    </form>

    <script>
        var slotNumber = <?php echo count($linkTitles); ?>; // Initialize the slot number

        function addSlot() {
            var container = document.getElementById('learningSlotsContainer');

            // Create new slot fields
            var slotDiv = document.createElement('div');
            slotDiv.className = 'form-group';
            slotDiv.innerHTML = `
            <label for="linkTitle${slotNumber}">Link Title:</label>
            <input type="text" class="form-control" id="linkTitle${slotNumber}" name="link_title[]" value="">
            <label for="linkUrl${slotNumber}">Link URL:</label>
            <input type="text" class="form-control" id="linkUrl${slotNumber}" name="link_url[]" value="">
        `;

            container.appendChild(slotDiv);
            slotNumber++; // Increment the slot number
        }

        function removeSlot() {
            var container = document.getElementById('learningSlotsContainer');
            var slots = container.children;

            if (slots.length > 0) {
                container.removeChild(slots[slots.length - 1]);
                slotNumber--; // Decrement the slot number
            }
        }

        // Ensure that link URLs have https:// appended before submitting the form
        document.querySelector('form').addEventListener('submit', function (event) {
            var linkUrlInputs = document.querySelectorAll('input[name^="link_url"]');
            linkUrlInputs.forEach(function (input) {
                var url = input.value.trim();
                if (url !== '' && !url.startsWith('http://') && !url.startsWith('https://')) {
                    // If the URL is not empty and doesn't start with http:// or https://, prepend https://
                    input.value = 'https://' + url;
                }
            });

            // Log to the console for debugging
            console.log('Form submitted with updated link URLs:', linkUrlInputs);
        });
    </script>

</div>

</body>
</html>
