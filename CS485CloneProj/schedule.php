<?php
session_start();
$servername = "localhost";
$sql_username = "root";  // MySQL username
$sql_password = "1234";  // MySQL password
$dbname = "mydb";  // Database name


// Create connection
$conn = new mysqli($servername, $sql_username, $sql_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$introText = '';
if (isset($_SESSION['loginName'])) {
    $introText = $_SESSION['loginName'] . " " . $_SESSION['loginLastname'] . " - " . $_SESSION['loginPosition'];

} else {
    // Handle the case where the session variable doesn't exist (e.g., user is not logged in)
    echo "You shouldn't be here";
}
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header('Location: login.php'); // Redirect to login page
    exit;
}

// Initialize an empty string to store the HTML content
$workerSelectHTML = '';
if (isset($_SESSION['loginPosition']) && $_SESSION['loginPosition'] == 'm') {
    $html = "<label> Schedule Worker: </label>";
    // Prepare the query
    $sql_result = $conn->prepare("SELECT f_name, l_name FROM employee");
    $sql_result->execute();

    // Get the result
    $workerName = $sql_result->get_result();

    // Start the select element
    $html .= "<select name='workerSelect'>";

    // Loop through the result set and generate options
    while ($row = $workerName->fetch_assoc()) {
        // Concatenate first and last names for the display
        $workerFullName = htmlspecialchars($row['f_name'] . " " . $row['l_name']);
        $html .= "<option value='" . $workerFullName . "'>" . $workerFullName . "</option>";
    }

    // Close the select element
    $html .= "</select>";

    $html .= "<label for='startTime'> Start Time: </label><input type='text' name='startTime' placeholder='00:00:00'> <label for='endTime'> End Time: </label><input type='text' name='endTime' placeholder='00:00:00'> <label for='dateForShift'> Date: </label><input type='date' name='dateForShift'> <br><br> <button name='addShift'>Add </button>";

    $workerSelectHTML = $html;
}


$tableHTML = ""; // Initialize variable for table content

function showSchedule($conn, &$tableHTML)
{
    $selectedDate = $_POST['schedule-date'];

    // Display the selected date
    $tableHTML .= "<h2>You selected: " . htmlspecialchars($selectedDate) . "</h2>";

    $sql_result = $conn->prepare("SELECT f_name AS 'First Name', 
                                         l_name AS 'Last Name', 
                                         start_time AS 'Start Time', 
                                         end_time AS 'End Time', 
                                         position AS 'Position'
                                  FROM shift AS s 
                                  JOIN employee AS e 
                                  ON s.emp_id = e.emp_id
                                  WHERE s.theDate = ? 
                                  ORDER BY s.start_time ASC");
    $sql_result->bind_param("s", $selectedDate);
    $sql_result->execute();

    // Fetch the result
    $workDay = $sql_result->get_result();

    // Check if there are rows in the result set
    if ($workDay->num_rows > 0) {
        // Generate table headers
        $tableHTML .= "<table border='1'><tr>";
        $fields = $workDay->fetch_fields();
        foreach ($fields as $field) {
            $tableHTML .= "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        $tableHTML .= "</tr>";

        // Generate table rows
        while ($row = $workDay->fetch_assoc()) {
            $tableHTML .= "<tr>";
            foreach ($row as $value) {
                $tableHTML .= "<td>" . htmlspecialchars($value) . "</td>";
            }
            $tableHTML .= "</tr>";
        }

        $tableHTML .= "</table>";
    } else {
        $tableHTML = "<h2>No shift found for the selected date.</h2>";
    }

    // Close the statement
    $sql_result->close();
}

function addToSchedule($conn)
{
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $date = $_POST['dateForShift'];
    $name = $_POST['workerSelect'];

    if (strlen($startTime) > 0 && strlen($endTime) > 0 && strlen($date) > 0 && strlen($name) > 0) {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $startTime) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $endTime)) {
            echo "Please enter the time in the correct format (HH:MM:SS).";
            return;
        }
        $sql_result = $conn->prepare("SELECT s.theDate, e.f_name
                                     FROM shift AS s 
                                     JOIN employee AS e 
                                     ON s.emp_id = e.emp_id
                                     WHERE CONCAT(e.f_name, ' ', e.l_name) = ? AND s.theDate = ?");
        $sql_result->bind_param("ss", $name, $date);
        $sql_result->execute();
        $doesExist = $sql_result->get_result();
        if ($doesExist->num_rows == 0) {
            // Insert the shift if not already scheduled
            $sql_result = $conn->prepare("INSERT INTO shift (theDate, start_time, end_time, emp_id)
                                          VALUES (?, ?, ?, (SELECT emp_id FROM employee WHERE CONCAT(f_name, ' ', l_name) = ?))");
            $sql_result->bind_param("ssss", $date, $startTime, $endTime, $name);
            $sql_result->execute();
            echo "Shift successfully added!";
        } else {
            echo "That person is already working on that day!";
        }
    } else {
        echo "Please fill in all the fields to add a shift.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['submit']) || isset($_POST['addShift']))) {
    showSchedule($conn, $tableHTML);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['addShift']))) {

    addToSchedule($conn);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" href="dealershipStyles.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car City</title>
    <link rel="icon" href="caricon.png" type="image/png" />
</head>

<body>
<nav>

    <?php if ($_SESSION['loginPosition'] === 's') {
        echo "<a href='vehicleSearch.php'>Car Search</a>";
        echo "<a href='addVehicle.php'>Add Vehicle</a>";
        echo "<a href='schedule.php'>Schedule</a>";
        echo "<a href='appointment.php'>Appointment</a>";
        echo "<a href='homePage.php'>Home</a>";
    }?>
    <?php if ($_SESSION['loginPosition'] === 't') {
        echo "<a href='partsSearch.php'>Parts Search</a>";
        echo "<a href='schedule.php'>Schedule</a>";
        echo "<a href='appointment.php'>Appointment</a>";
        echo "<a href='homePage.php'>Home</a>";
    }?>
    <?php if ($_SESSION['loginPosition'] === 'm') {
        echo "<a href='vehicleSearch.php'>Car Search</a>";
        echo "<a href='addVehicle.php'>Add Vehicle</a>";
        echo "<a href='partsSearch.php'>Parts Search</a>";
        echo "<a href='schedule.php'>Schedule</a>";
        echo "<a href='appointment.php'>Appointment</a>";
        echo "<a href='homePage.php'>Home</a>";
    }?>
    <!-- Display user info and logout button next to each other -->
    <span class="user-info"><?php echo $introText; ?></span> <!-- Display user info -->
    <a href="?logout=true" class="logout-btn">Logout</a> <!-- Logout Link -->

</nav>
<div class="container">

    <h1>Schedule</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="searchForm">
            <label for="schedule-date">Select a Date:</label>
            <input type="date" id="schedule-date" name="schedule-date" required>
            <br><br>
            <button type="submit" name="submit">Submit</button>
            <br><br>
            <?php echo $workerSelectHTML; ?>
        </div>
    </form>

    <script>
        // JavaScript to set the default date to today
        const dateInput = document.getElementById('schedule-date');
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
    </script>
    <br>
    <?php
    // Output the schedule table after submission
    echo $tableHTML;
    ?>
</div>
</body>
</html>
