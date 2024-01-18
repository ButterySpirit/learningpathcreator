<?php
// Update these values with your actual remote database credentials
$servername = "209.172.60.196";
$username = "f3432361_User";
$password = "12345";
$dbname = "f3432361_GroupAssign";

// Create a connection to the remote MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize the error message
$errorMsg = "";

// Process user registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);

    // Set default values
    $pfpFilename = "default_profile_image.jpg";
    $bio = ""; // Set this to a default or empty value

    // Check if the username already exists
    if (isUsernameTaken($conn, $username)) {
        $errorMsg = "Error: Username is already taken.";
    } else {
        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, pfp_filename, bio) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $password, $pfpFilename, $bio);

        // Check if registration was successful
        if ($stmt->execute()) {
            $errorMsg = "Registration successful!";

            // Redirect to index.html
            header("Location: index.html");
            exit(); // Ensure that no further code is executed after the redirect
        } else {
            $errorMsg = "Error: " . $stmt->error;
        }

        // Close the prepared statement
        $stmt->close();
    }
}

// Close the database connection
$conn->close();

// Function to check if the username is already taken
function isUsernameTaken($conn, $username)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();

    return $num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Your Learning Path Creator</title>
    <!-- Include Bootstrap CSS link here -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Include Bootstrap JS link here -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container mt-5">
    <h2>User Registration</h2>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $errorMsg; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="register.php">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

</body>
</html>
