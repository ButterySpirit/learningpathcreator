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

// Initialize path_id to a default value or handle the case where it's not provided
$path_id = isset($_GET["path_id"]) ? $_GET["path_id"] : null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_changes"])) {
    // Update learning path details
    $title = $_POST["title"];
    $description = $_POST["description"];

    $sqlUpdatePath = "UPDATE learning_paths SET title = ?, description = ? WHERE id = ? AND user_id = ?";
    $stmtUpdatePath = $conn->prepare($sqlUpdatePath);
    $stmtUpdatePath->bind_param("ssii", $title, $description, $path_id, $user_id);
    $stmtUpdatePath->execute();
    $stmtUpdatePath->close();

    // Update or insert learning slot details
    $linkTitles = $_POST["link_title"];
    $linkUrls = $_POST["link_url"];
    $existingSlotIds = $_POST["existing_slot_id"];
    $removedSlotIds = isset($_POST["removed_slot_id"]) ? explode(',', $_POST["removed_slot_id"]) : [];

    // Update existing slots
    $sqlUpdateSlots = "UPDATE learning_slots SET link_title = ?, link_url = ? WHERE id = ?";
    $stmtUpdateSlots = $conn->prepare($sqlUpdateSlots);

    foreach ($existingSlotIds as $index => $existingSlotId) {
        $stmtUpdateSlots->bind_param("ssi", $linkTitles[$index], $linkUrls[$index], $existingSlotId);
        $stmtUpdateSlots->execute();
    }

    $stmtUpdateSlots->close();

    // Insert new slots
    $sqlInsertSlots = "INSERT INTO learning_slots (path_id, link_title, link_url) VALUES (?, ?, ?)";
    $stmtInsertSlots = $conn->prepare($sqlInsertSlots);

    for ($i = count($existingSlotIds); $i < count($linkTitles); $i++) {
        $stmtInsertSlots->bind_param("iss", $path_id, $linkTitles[$i], $linkUrls[$i]);
        $stmtInsertSlots->execute();
    }

    $stmtInsertSlots->close();

    // Delete removed slots
    if (!empty($removedSlotIds)) {
        $sqlDeleteSlots = "DELETE FROM learning_slots WHERE id IN (" . implode(",", array_fill(0, count($removedSlotIds), "?")) . ")";
        $stmtDeleteSlots = $conn->prepare($sqlDeleteSlots);
        $stmtDeleteSlots->bind_param(str_repeat("i", count($removedSlotIds)), ...$removedSlotIds);
        $stmtDeleteSlots->execute();
        $stmtDeleteSlots->close();
    }

    // Redirect to a success page or perform additional actions if needed
    header("Location: my_paths.php");
    exit();
}

// Fetch learning path details
$sqlPath = "SELECT * FROM learning_paths WHERE id = ? AND user_id = ?";
$stmtPath = $conn->prepare($sqlPath);
$stmtPath->bind_param("ii", $path_id, $user_id);
$stmtPath->execute();
$resultPath = $stmtPath->get_result();

// Fetch learning slots details
$sqlSlots = "SELECT * FROM learning_slots WHERE path_id = ?";
$stmtSlots = $conn->prepare($sqlSlots);
$stmtSlots->bind_param("i", $path_id);
$stmtSlots->execute();
$resultSlots = $stmtSlots->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Learning Path</title>

    <!-- Include Bootstrap CSS link here -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Include Bootstrap JS, Popper.js, and your custom JavaScript file here -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
    <script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

    <style>
        /* Add any custom styles here */
    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Edit Learning Path</h2>

    <!-- Form to edit learning path -->
    <form method="post" action="">
        <input type="hidden" id="pathId" name="path_id" value="<?php echo $path_id; ?>">
        <?php
        // Display learning path details
        if ($rowPath = $resultPath->fetch_assoc()) {
            echo "<div class='form-group'>";
            echo "<label for='title'>Title:</label>";
            echo "<input type='text' class='form-control' id='title' name='title' value='{$rowPath['title']}' required>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='description'>Description:</label>";
            echo "<textarea class='form-control' id='description' name='description' rows='4'>{$rowPath['description']}</textarea>";
            echo "</div>";
        }
        $stmtPath->close();

        // Display learning slot details
        while ($rowSlot = $resultSlots->fetch_assoc()) {
            echo "<div class='form-group'>";
            echo "<label for='linkTitle'>Link Title:</label>";
            echo "<input type='text' class='form-control' name='link_title[]' value='{$rowSlot['link_title']}'>";
            echo "<label for='linkUrl'>Link URL:</label>";
            echo "<input type='text' class='form-control' name='link_url[]' value='{$rowSlot['link_url']}'>";
            echo "<input type='hidden' name='existing_slot_id[]' value='{$rowSlot['id']}'>";
            echo "</div>";
        }
        $stmtSlots->close();
        ?>

        <!-- Dynamic fields for additional learning slots -->
        <div id="learningSlotsContainer">
            <!-- Additional learning slot fields will be dynamically added here -->
        </div>

        <button type="button" class="btn btn-primary" onclick="addSlot()">Add Slot</button>
        <button type="submit" class="btn btn-success" name="save_changes">Save Changes</button>
    </form>

    <!-- Your JavaScript code -->

    <script>
        function addSlot() {
            var container = document.getElementById('learningSlotsContainer');
            var slotDiv = document.createElement('div');
            slotDiv.className = 'form-group';
            slotDiv.innerHTML = `
                <label for="linkTitle">Link Title:</label>
                <input type="text" class="form-control" name="link_title[]">
                <label for="linkUrl">Link URL:</label>
                <input type="text" class="form-control" name="link_url[]">
            `;
            container.appendChild(slotDiv);
        }
    </script>

</div>

</body>
</html>
