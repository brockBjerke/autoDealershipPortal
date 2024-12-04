<?php
session_start();

$servername = "localhost";
$sql_username = "root";  // MySQL username
$sql_password = "1234";  // MySQL password
$dbname = "mydb";

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

$lotIDErr = $makeErr = $modelErr = $yearErr = $VINErr = $milesErr = $colorErr = $typeErr = $coCostErr = $carNameErr = $MSRPErr = "";
$enteredLotID = $enteredMake = $enteredModel = $enteredVIN = $enteredColor = $enteredType = $enteredCarName = "";
$enteredYear = $enteredMiles = $enteredCoCost = $enteredMSRP = 0;

// Gather all lot IDs
$allLotIds = $conn->query("SELECT lot_id FROM vehicles");
$allVINs = $conn->query("SELECT VIN FROM vehicles");

$lotIDArray = array();
$i = 0;
// Push all lot IDs to array
while ($row = $allLotIds->fetch_assoc()) {
    $tuple = $row['lot_id'];
    $lotIDArray[$i] = $tuple;
    $i++;
}
// Gather the last used lot ID for the placeholder text
$lastUsedId = $lotIDArray[sizeof($lotIDArray) - 1];

$VINArray = array();
$i = 0;
// Push all VINs to array
while ($row = $allVINs->fetch_assoc()) {
    $tuple = $row['VIN'];
    $VINArray[$i] = $tuple;
    $i++;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST["lot_id"])) {
        $lotIDErr = "Lot ID is required";
    } else if (in_array($_POST["lot_id"], $lotIDArray)) {
        $lotIDErr = "Lot ID " . $_POST["lot_id"] . " is already in use";
    } else {
        $enteredLotID = $_POST["lot_id"];
    }
    if (empty($_POST["make"])) {
        $makeErr = "Vehicle make is required";
    } else {
        $enteredMake = $_POST["make"];
    }
    if (empty($_POST["model"])) {
        $modelErr = "Vehicle model is required";
    } else {
        $enteredModel = $_POST["model"];
    }
    if (empty($_POST["year"])) {
        $yearErr = "Vehicle year is required";
    } else if (!is_numeric($_POST["year"])) {
        $yearErr = "Please enter a 4-digit number without any special characters for the vehicle year";
    } else {
        $enteredYear = (int)$_POST["year"];
    }
    if (empty($_POST["VIN"])) {
        $VINErr = "VIN is required";
    } else if (in_array($_POST["VIN"], $VINArray)) {
        $VINErr = "VIN " . $_POST["VIN"] . " is already in use";
    } else {
        $enteredVIN = $_POST["VIN"];
    }
    if (empty($_POST["miles"])) {
        $milesErr = "Vehicle miles is required";
    } else if (!is_numeric($_POST["miles"])) {
        $milesErr = "Miles must be a numeric value no larger than 6 digits without commas";
    } else {
        $enteredMiles = (int)$_POST["miles"];
    }
    if (empty($_POST["color"])) {
        $colorErr = "Vehicle color is required";
    } else {
        $enteredColor = strtolower($_POST["color"]);
    }
    if (empty($_POST["type"])) {
        $typeErr = "Vehicle type is required";
    } else {
        $enteredType = strtolower($_POST["type"]);
    }
    if (empty($_POST["co_cost"])) {
        $coCostErr = "Company cost of the vehicle is required";
    } else if (!is_numeric($_POST["co_cost"])) {
        $coCostErr = "Company cost must be a numeric value no larger than 6 digits without commas";
    } else {
        $enteredCoCost = (float)$_POST["co_cost"];
    }
    if (empty($_POST["carName"])) {
        $carNameErr = "Full vehicle name is required";
    } else {
        $enteredCarName = $_POST["carName"];
    }
    if (empty($_POST["MSRP"])) {
        $MSRPErr = "Vehicle MSRP is required";
    } else if (!is_numeric($_POST["MSRP"])) {
        $MSRPErr = "MSRP must be a numeric value no larger than 6 digits without commas or periods";
    } else {
        $enteredMSRP = (int)$_POST["MSRP"];
    }

    // Check that there are no errors
    if (
        empty($lotIDErr) && empty($makeErr) && empty($modelErr) &&
        empty($yearErr) && empty($VINErr) && empty($milesErr) &&
        empty($colorErr) && empty($typeErr) && empty($coCostErr) &&
        empty($carNameErr) && empty($MSRPErr)
    ) {
        createVehicle($conn, $enteredLotID, $enteredMake, $enteredModel, $enteredYear, $enteredVIN, $enteredMiles, $enteredColor, $enteredType, $enteredCoCost, $enteredCarName, $enteredMSRP);
    }
}
function createVehicle($connection, string $lot_id, string $make, string $model, int $year,
                       string $VIN, int $miles, string $color, string $type, int $coCost, string $carName, int $MSRP)
{
    $stmtVehicle = $connection->prepare("INSERT INTO vehicles (lot_id, make, model, year, VIN, miles, color, type, co_cost, car_name, MSRP) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmtVehicle->bind_param("sssisissisi", $lot_id, $make, $model, $year, $VIN, $miles, $color, $type, $coCost, $carName, $MSRP);
    if ($stmtVehicle->execute()) echo "Vehicle entered successfully";
    else echo "An unexpected error has occurred";
}

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
<h1>Add a new vehicle</h1>
<div class="searchForm">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
    <!-- Vehicle details -->
    <h2>Vehicle Details</h2>
    <label>Lot ID: </label>
    <input type="text" name="lot_id" maxlength="49" placeholder="Last used lot ID <?php echo $lastUsedId; ?>">
    <span class="error" style="color: red">* <?php echo $lotIDErr; ?></span>
    <br>

    <label>Make: </label>
    <input type="text" name="make" maxlength="49" placeholder="e.g. Toyota">
    <span class="error" style="color: red">* <?php echo $makeErr; ?></span>
    <br>

    <label>Model: </label>
    <input type="text" name="model" maxlength="49" placeholder="e.g. Corolla">
    <span class="error" style="color: red">* <?php echo $modelErr; ?></span>
    <br>

    <label>Year: </label>
    <input type="number" name="year" maxlength="4" minlength="4" placeholder="Enter a 4 digit year">
    <span class="error" style="color: red">* <?php echo $yearErr; ?></span>
    <br>

    <label>VIN: </label>
    <input type="text" name="VIN" maxlength="17" minlength="17">
    <span class="error" style="color: red">* <?php echo $VINErr; ?></span>
    <br>

    <label>Miles: </label>
    <input type="number" name="miles" maxlength="6" placeholder="Don't enter commas">
    <span class="error" style="color: red">* <?php echo $milesErr; ?></span>
    <br>

    <label>Color: </label>
    <input type="text" name="color" maxlength="49" placeholder="e.g. blue">
    <span class="error" style="color: red">* <?php echo $colorErr; ?></span>
    <br>

    <label>Type: </label>
    <input type="text" name="type" maxlength="49" placeholder="e.g. sedan">
    <span class="error" style="color: red">* <?php echo $typeErr; ?></span>
    <br>

    <label>Company Cost: </label>
    <input type="number" name="co_cost" maxlength="6" placeholder="Don't enter commas">
    <span class="error" style="color: red">* <?php echo $coCostErr; ?></span>
    <br>

    <label>Car Name: </label>
    <input type="text" name="carName" maxlength="49" placeholder="Full make and model plus trim">
    <span class="error" style="color: red">* <?php echo $carNameErr; ?></span>
    <br>

    <label>MSRP: </label>
    <input type="text" name="MSRP" maxlength="6" placeholder="Suggested: company cost * 2.5">
    <span class="error" style="color: red">* <?php echo $MSRPErr; ?></span>
    <br>
    <br>
    <input type="submit" name="submitVehicle" value="Add">
</div>

</body>
</html>
