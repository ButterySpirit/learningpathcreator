<?php
// Start session
session_start();

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

// Initialize error variable
$error = "";

// Process user login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Retrieve user data from the database
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            // Set session variables
            $_SESSION["id"] = $row["id"];
            $_SESSION["username"] = $row["username"];
            $_SESSION["pfp_filename"] = $row["pfp_filename"];

            // Redirect to a logged-in user page
            header("Location: dashboard.php");
            exit(); // Ensure that no further code is executed after the redirect
        } else {
            // Set error message
            $error = "Incorrect password!";
        }
    } else {
        // Set error message
        $error = "User not found!";
    }
}

$conn->close();
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
    <h2>User Login</h2>
    <!-- Display error message if set -->
    <?php if (!empty($error)): ?>
        <p class="text-danger"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post" action="login.php"> <!-- Specify the correct action attribute -->
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

</body>
</html>
