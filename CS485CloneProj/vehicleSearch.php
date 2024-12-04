
<?php
session_start();
$servername = "localhost";
$sql_username = "root";  //user name
$sql_password = "1234";  //password used to login MySQL server
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


// Initialize variables
$areYouSureMessage = '';
$confirmButton = '';
$denyButton = '';
$customerField = '';

// Get logged-in user details
$loginFullName = $_SESSION['loginName'] . $_SESSION['loginLastname'];

// Functions for formatting dropdown options
function formatColumnDropdown($column)
{
    $removeUnderscores = str_replace("_", " ", $column);
    if ($removeUnderscores === 'co cost') $removeUnderscores = 'company cost';
    if ($removeUnderscores === 'lot id') $removeUnderscores = 'lot ID';
    return ucfirst($removeUnderscores);
}

// Function to create queries for vehicle searches
function createQuery($term, $attribute)
{
    $baseQuery = "SELECT * FROM vehicles WHERE is_sold = 0";
    if (empty($term)) {
        return $baseQuery;
    } else {
        return $baseQuery . " AND " . $attribute . " = '" . trim($term, "\s") . "'";
    }
}

// Fetch unsold vehicles for table
$sql_result_fill_table = $conn->query(createQuery("", ""));

// Fetch column names for dropdown
$sql_result_for_dropdown = $conn->query("SELECT COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'mydb' 
    AND TABLE_NAME = 'vehicles'");

$columns = [];
while ($row = $sql_result_for_dropdown->fetch_assoc()) {
    $columns[] = $row['COLUMN_NAME'];
}

// Handle all car sale-related logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST["submit"])) {
        $enteredTerm = isset($_POST["search"]) ? $_POST["search"] : "";
        $enteredAttribute = isset($_POST["attributes"]) ? $_POST["attributes"] : "";
        $sql_result_fill_table = $conn -> query(createQuery($enteredTerm, $enteredAttribute));
    }

    if (isset($_POST['sellCarButton'])) {
        // Fetch car details for confirmation
        $lotId = htmlspecialchars($_POST['lot_id']);
        $sql_result = $conn->prepare("SELECT * FROM vehicles WHERE lot_id = ?");
        $sql_result->bind_param("s", $lotId);
        $sql_result->execute();
        $vehicle = $sql_result->get_result()->fetch_assoc();

        if ($vehicle) {
            $areYouSureMessage = 'Do you wish to sell the car with details: ' .
                htmlspecialchars($vehicle['make'] . " " . $vehicle['model'] . " (" . $vehicle['year'] . "), MSRP: $" . $vehicle['MSRP']);
            $confirmButton = '
                <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
                    <input type="hidden" name="lot_id" value="' . htmlspecialchars($lotId) . '">
                    <input type="hidden" name="saleAmount" value="' . htmlspecialchars($vehicle['MSRP']) . '">
                    <button type="submit" name="confirmSale">Yes</button>
                </form>';
            $denyButton = '
                <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
                    <button type="submit" name="denySale">No</button>
                </form>';
        } else {
            $areYouSureMessage = 'Car details could not be found.';
        }
    }

    if (isset($_POST['denySale'])) {
        // Reset confirmation area
        $areYouSureMessage = '';
        $confirmButton = '';
        $denyButton = '';
    }

    if (isset($_POST['confirmSale'])) {
        // Display sale finalization form
        $lotId = htmlspecialchars($_POST['lot_id']);
        $saleAmount = htmlspecialchars($_POST['saleAmount']);
        echo '
        <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
            <input type="hidden" name="lot_id" value="' . $lotId . '">
            <label>Customer ID of Sale: </label>
            <input type="text" name="customerIDField" required>
            <label>Sale Amount: </label>
            <input type="number" name="saleAmount" value="' . $saleAmount . '" required step="0.01">
            <button type="submit" name="finalizeCarSale">Sell Car</button>
        </form>
        ';
    }

    if (isset($_POST['finalizeCarSale'])) {
        // Finalize sale process
        $lotId = htmlspecialchars($_POST['lot_id']);
        $clientId = htmlspecialchars($_POST['customerIDField']);
        $saleAmount = htmlspecialchars($_POST['saleAmount']);
        $theDate = date('Y-m-d');
        $empId = $_SESSION['loginEId'];

        // Determine the next sale_id
        $saleIdQuery = "SELECT sale_id FROM vehicle_sales ORDER BY sale_id DESC LIMIT 1";
        $result = $conn->query($saleIdQuery);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastSaleId = $row['sale_id'];
            $numericPart = (int)substr($lastSaleId, 1);
            $newSaleId = "S" . str_pad($numericPart + 1, 5, "0", STR_PAD_LEFT);
        } else {
            $newSaleId = "S00001";
        }

        // Insert sale into vehicle_sales table
        $insertSaleQuery = $conn->prepare(
            "INSERT INTO vehicle_sales (sale_id, sale_amount, theDate, emp_id, lot_id, client_id) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insertSaleQuery->bind_param("ssssss", $newSaleId, $saleAmount, $theDate, $empId, $lotId, $clientId);

        if ($insertSaleQuery->execute()) {
            // Mark vehicle as sold
            $updateVehicleQuery = $conn->prepare("UPDATE vehicles SET is_sold = 1 WHERE lot_id = ?");
            $updateVehicleQuery->bind_param("s", $lotId);
            if ($updateVehicleQuery->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error marking vehicle as sold: " . $conn->error;
            }
        } else {
            echo "Error recording sale: " . $conn->error;
        }
    }
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



<link rel="stylesheet" href="dealershipStyles.css">
<h1>Search vehicles on the lot</h1>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <div class="searchTextGroup">
        <label>Search </label>
        <input type="text" name="search"></input>
        <!--    <span class="error" style="color: red">* --><?php //echo $searchBarErr;?><!--</span>-->
        <?php echo $areYouSureMessage . "<br>" . $confirmButton . $denyButton . "<br>"; ?>
        <label>Search by: </label>
        <select name="attributes">
            <option value="lot_id"><?php echo formatColumnDropdown($columns[0]) ?></option>
            <option value="make"><?php echo formatColumnDropdown($columns[1]) ?></option>
            <option value="model"><?php echo formatColumnDropdown($columns[2]) ?></option>
            <option value="year"><?php echo formatColumnDropdown($columns[3]) ?></option>
            <option value="VIN"><?php echo formatColumnDropdown($columns[4]) ?></option>
            <option value="miles"><?php echo formatColumnDropdown($columns[5]) ?></option>
            <option value="color"><?php echo formatColumnDropdown($columns[6]) ?></option>
            <option value="type"><?php echo formatColumnDropdown($columns[7]) ?></option>
            <option value="co_cost"><?php echo formatColumnDropdown($columns[8]) ?></option>
            <option value="car_name"><?php echo formatColumnDropdown($columns[9]) ?></option>
            <option value="MSRP"><?php echo formatColumnDropdown($columns[10]) ?></option>
        </select>
        <button type="submit" name="submit">Submit</button>
        <br><br>
        <a href="addVehicle.php">Add new vehicle</a>
    </div>
    <div class="table-container">
        <div class="table-row">
            <div class="table-col">
                <div class="card">
                    <div class="card-header">
                        <h2 class="display">Vehicles on lot</h2>
                    </div>
                    <div class="card-body">
                        <table class="vehicle-table">
                            <tr class="table-headers">
                                <!-- Column headers-->
                                <td><?php echo formatColumnDropdown($columns[0]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[1]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[2]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[3]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[4]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[5]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[6]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[7]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[8]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[9]) ?></td>
                                <td><?php echo formatColumnDropdown($columns[10]) ?></td>
                            </tr>
                            <tr>
                                <!-- While loop to create rows of data-->
                                <?php
                                while ($row = mysqli_fetch_assoc($sql_result_fill_table)) {
                                ?>
                                <td><?php echo $row[$columns[0]]; ?></td>
                                <td><?php echo $row[$columns[1]]; ?></td>
                                <td><?php echo $row[$columns[2]]; ?></td>
                                <td><?php echo $row[$columns[3]]; ?></td>
                                <td><?php echo $row[$columns[4]]; ?></td>
                                <td><?php echo $row[$columns[5]]; ?></td>
                                <td><?php echo $row[$columns[6]]; ?></td>
                                <td><?php echo $row[$columns[7]]; ?></td>
                                <td><?php echo $row[$columns[8]]; ?></td>
                                <td><?php echo $row[$columns[9]]; ?></td>
                                <td><?php echo $row[$columns[10]]; ?></td>
                                <td>
                                    <!-- Add the button -->
                                    <!-- Add the form -->
                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                        <input type="hidden" name="lot_id" value="<?php echo $row[$columns[0]]; ?>">
                                        <button type="submit" name="sellCarButton">Sell Car</button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</body>
</html>