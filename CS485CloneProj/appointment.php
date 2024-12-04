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

// Fetch all customer IDs from the database
function getCustomerDropdown($conn)
{
    $sql = "SELECT client_id FROM client"; // Replace 'client' with your table name
    $result = $conn->query($sql);

    if (!$result) {
        return '<option value="" disabled>Error fetching customers</option>';
    }

    if ($result->num_rows > 0) {
        $options = '<option value="" disabled selected>Select Customer ID</option>';
        while ($row = $result->fetch_assoc()) {
            $options .= '<option value="' . htmlspecialchars($row['client_id']) . '">' . htmlspecialchars($row['client_id']) . '</option>';
        }
        return $options;
    } else {
        return '<option value="" disabled>No customers found</option>';
    }
}

// Generate the dropdown options
$customerDropdownOptions = getCustomerDropdown($conn);


function makeAppointment($conn)
{
    $date = $_POST['makeAppointment-date'];
    $startTime = $_POST['start-time'];
    $cID = $_POST['customer-id'];
    $appointmentType = $_POST['appointment-type'];
    if (strlen($startTime) > 0 && strlen($date) > 0) {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $startTime)) {
            echo "Please enter the time in the correct format (HH:MM:SS).";
            return;
        }
        $sql_result = $conn->prepare("SELECT a.theDate, a.start_time, a.client_id, a.appointment_type
                                    FROM appointment AS a
                                    JOIN client AS c ON c.client_id = a.client_id
                                    WHERE a.client_id = ? AND a.theDate = ?");
        $sql_result->bind_param("ss", $cID, $date);
        $sql_result->execute();
        $doesExist = $sql_result->get_result();
        if ($doesExist->num_rows == 0) {
            // Insert the shift if not already scheduled
            $sql_result = $conn->prepare("INSERT INTO appointment (theDate, start_time, client_id, appointment_type)
                                          VALUES (?, ?, ?, ?)");
            $sql_result->bind_param("ssss", $date, $startTime, $cID, $appointmentType);
            $sql_result->execute();
            echo "Shift successfully added!";
        } else {
            echo "That person is already working on that day!";
        }


    } else {
        echo "Please fill all Fields";
    }

}

$tableHTML = ""; // Initialize variable for table content

function showAppointment($conn, &$tableHTML)
{
    $selectedDate = $_POST['appointment-date'];

    // Display the selected date
    $tableHTML .= "<h2>You selected: " . htmlspecialchars($selectedDate) . "</h2>";

    // Prepare SQL query to fetch appointments and join with clients
    $sql_result = $conn->prepare("SELECT c.f_name AS 'First Name', 
                                         c.l_name AS 'Last Name', 
                                         a.start_time AS 'Start Time',  
                                         a.appointment_type AS 'Appointment Type'
                                  FROM appointment AS a
                                  JOIN client AS c 
                                  ON a.client_id = c.client_id
                                  WHERE a.theDate = ? 
                                  ORDER BY a.start_time ASC");
    $sql_result->bind_param("s", $selectedDate);
    $sql_result->execute();

    // Fetch the result
    $appointments = $sql_result->get_result();

    // Check if there are rows in the result set
    if ($appointments->num_rows > 0) {
        // Generate table headers
        $tableHTML .= "<table border='1'><tr>";
        $fields = $appointments->fetch_fields();
        foreach ($fields as $field) {
            $tableHTML .= "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        $tableHTML .= "</tr>";

        // Generate table rows
        while ($row = $appointments->fetch_assoc()) {
            $tableHTML .= "<tr>";
            foreach ($row as $value) {
                $tableHTML .= "<td>" . htmlspecialchars($value) . "</td>";
            }
            $tableHTML .= "</tr>";
        }

        $tableHTML .= "</table>";
    } else {
        $tableHTML = "<h2>No appointments found for the selected date.</h2>";
    }

    // Close the statement
    $sql_result->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['create']))) {
    makeAppointment($conn);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['search']))) {
    showAppointment($conn, $tableHTML);
}

// Close the connection
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

    <h1>Appointment</h1>
    <div class="searchForm">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

            <label for="appointment-date">Select a Date:</label>
            <input type="date" id="appointment-date" name="appointment-date" required>
            <br><br>
            <button type="submit" name="search">Search</button>
            <br>

        </form>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <br>
            <label for="customer-id">Customer ID:</label>
            <select name="customer-id" id="customer-id">
                <?php echo $customerDropdownOptions; ?>
            </select>
            <label for="start-time">Start Time: </label>
            <input type="text" name="start-time" id="start-time" placeholder='00:00:00'>
            <label for="appointment-type">Appointment Type:</label>
            <select name="appointment-type" id="appointment-type">
                <option value="S">S</option>
                <option value="B">B</option>
            </select>
            <label for="makeAppointment-date">Date:</label>
            <input type="date" id="makeAppointment-date" name="makeAppointment-date" required>

            <br><br>
            <button type="submit" name="create">Create</button>
            <br><br>

        </form>
    </div>

    <br>
    <?php
    // Output the schedule table after submission
    echo $tableHTML;
    ?>

    <script>
        // JavaScript to set the default date to today
        const dateInput = document.getElementById('appointment-date');
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
    </script>
</div>
</body>
</html>