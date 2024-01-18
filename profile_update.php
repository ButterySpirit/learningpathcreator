<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

// Establish a connection to your MySQL database
$servername = "209.172.60.196";
$username = "f3432361_User";
$password = "12345";
$dbname = "f3432361_GroupAssign";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve the current values from the database
$user_id = $_SESSION["id"];
$sql = "SELECT pfp_filename, bio, username, email, password FROM users WHERE id = $user_id";
$result = $conn->query($sql);

// Initialize variables to store the current values
$currentPfpFilename = "";
$currentBio = "";
$currentUsername = "";
$currentEmail = "";
$currentPassword = ""; // Initialize the current password variable

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentPfpFilename = $row["pfp_filename"];
    $currentBio = $row["bio"];
    $currentUsername = $row["username"];
    $currentEmail = $row["email"];
    $currentPassword = $row["password"];
}

// Set default profile image name
$defaultPfpFilename = "default_profile_image.jpg";

// Process user profile update form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newBio = $_POST["new_bio"];
    $newUsername = $_POST["new_username"];
    $newPassword = $_POST["new_password"];
    $newEmail = $_POST["new_email"];

    // Check if a new profile picture is selected
    if (!empty($_FILES["new_pfp"]["name"])) {
        // Handle file upload
        $targetDir = "uploads/";
        $newPfpFilename = basename($_FILES["new_pfp"]["name"]);

        // Validate and upload the file
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($newPfpFilename, PATHINFO_EXTENSION));

        // Check if the image file is a actual image or fake image
        $check = getimagesize($_FILES["new_pfp"]["tmp_name"]);
        if ($check === false) {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (adjust the size limit as needed)
        if ($_FILES["new_pfp"]["size"] > 5000000) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        $allowedFormats = ["jpg", "jpeg", "png", "gif"];
        if (!in_array($imageFileType, $allowedFormats)) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        } else {
            // Upload the file
            if (move_uploaded_file($_FILES["new_pfp"]["tmp_name"], $targetDir . $newPfpFilename)) {
                echo "The file " . htmlspecialchars(basename($_FILES["new_pfp"]["name"])) . " has been uploaded.";
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        // Keep the current profile picture filename if no new file is selected
        $newPfpFilename = $currentPfpFilename;
    }

    // Validate and update the user profile information
    if (!empty($newUsername) && isUniqueUsername($conn, $newUsername)) {
        $currentUsername = $newUsername;
    }

    if (!empty($newPassword)) {
        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    } else {
        // Keep the current password if no new password is provided
        $newPassword = $currentPassword;
    }

    if (!empty($newEmail) && filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $currentEmail = $newEmail;
    }

    // Update the user profile information
    $stmt = $conn->prepare("UPDATE users SET pfp_filename = ?, bio = ?, username = ?, password = ?, email = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $newPfpFilename, $newBio, $currentUsername, $newPassword, $currentEmail, $user_id);

    if ($stmt->execute()) {
        echo "Profile updated successfully!";

        // Set the session variables after a successful update
        $_SESSION["pfp_filename"] = $newPfpFilename;
        $_SESSION["username"] = $currentUsername;

        // Redirect to the dashboard
        header("Location: dashboard.php");
        exit(); // Ensure that no further code is executed after the redirect
    } else {
        echo "Error updating profile: " . $stmt->error;
    }

    // Close the prepared statement
    $stmt->close();
}

// Close the database connection
$conn->close();

// Function to check if the username is unique
function isUniqueUsername($conn, $username)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();

    return $num_rows === 0;
}
?>

<!-- HTML Form for Profile Update -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <!-- Include Bootstrap CSS link here -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Include Bootstrap JS and Popper.js scripts here -->
    <script src="node_modules/jquery/dist/jquery.slim.min.js"></script>
    <script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container mt-5">
    <h2>Edit Profile</h2>
    <!-- Profile Update Form -->
    <form action="profile_update.php" method="post" enctype="multipart/form-data">
        <!-- Choose Profile Picture -->
        <div class="form-group">
            <label for="newPfp">Choose Profile Picture:</label>
            <input type="file" class="form-control-file" id="newPfp" name="new_pfp" accept="image/jpeg, image/png, image/gif">
            <?php if (!empty($currentPfpFilename)): ?>
                <!-- Display current profile picture filename if available -->
                <p>Current Profile Picture: <?php echo $currentPfpFilename; ?></p>
            <?php endif; ?>
        </div>

        <!-- Bio -->
        <div class="form-group">
            <label for="newBio">Bio:</label>
            <textarea class="form-control" id="newBio" name="new_bio" rows="4"><?php echo $currentBio; ?></textarea>
        </div>

        <!-- New Username -->
        <div class="form-group">
            <label for="newUsername">Username:</label>
            <input type="text" class="form-control" id="newUsername" name="new_username" value="<?php echo $currentUsername; ?>">
        </div>

        <!-- New Password -->
        <div class="form-group">
            <label for="newPassword">New Password:</label>
            <input type="password" class="form-control" id="newPassword" name="new_password">
        </div>

        <!-- New Email -->
        <div class="form-group">
            <label for="newEmail">Email:</label>
            <input type="email" class="form-control" id="newEmail" name="new_email" value="<?php echo $currentEmail; ?>">
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

</body>
</html>
