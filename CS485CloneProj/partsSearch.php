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

$sql_result_for_headers = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'mydb' AND TABLE_NAME = 'parts'");

// Put all attributes in array to use in search dropdown
$columns = array();
$i = 0;
while ($row = $sql_result_for_headers->fetch_assoc()) {
    $column = $row['COLUMN_NAME'];
    $columns[$i] = $column;
    $i++;
}

function format($attribute)
{
    $removeUnderscores = str_replace("_", " ", $attribute);
    if ($removeUnderscores === 'co cost') {
        $removeUnderscores = 'company cost';
    }
    if ($removeUnderscores === 'part id') {
        $removeUnderscores = 'part ID';
    }
    return ucfirst($removeUnderscores);
}

function createQuery($term, $attribute)
{
    $baseQuery = "SELECT * FROM parts";
    if (empty($term)) {
        return $baseQuery;
    } else {
        return $baseQuery . " WHERE " . $attribute . " = " . "'" . trim($term, "\s") . "'";
    }
}

$sql_result_for_table = $conn->query(createQuery("", ""));

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["submit"])) {
    $enteredTerm = isset($_POST["search"]) ? $_POST["search"] : "";
    $enteredAttribute = isset($_POST["attributes"]) ? $_POST["attributes"] : "";
    $sql_result_for_table = $conn->query(createQuery($enteredTerm, $enteredAttribute));
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
<h1>Search parts</h1>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <div class="searchTextGroup">
        <label>Search </label>
        <input type="text" name="search"><br>
        <label>Search by: </label>
        <select name="attributes">
            <option value="part_id"><?php echo format($columns[0]); ?></option>
            <option value="make"><?php echo format($columns[1]); ?></option>
            <option value="model"><?php echo format($columns[2]); ?></option>
            <option value="year"><?php echo format($columns[3]); ?></option>
            <option value="type"><?php echo format($columns[4]); ?></option>
            <option value="co_cost"><?php echo format($columns[5]); ?></option>
            <option value="sale_price"><?php echo format($columns[6]); ?></option>
        </select>
        <br> <br>
        <button type="submit" name="submit">Submit</button>
    </div>
    <div class="table-container">
        <div class="table-row">
            <div class="table-col">
                <div class="card">
                    <div class="card-header">
                        <h2 class="display">Parts</h2>
                    </div>
                    <div class="card-body">
                        <table class="parts-table">
                            <tr class="table-headers">
                                <td><?php echo format($columns[0]) ?></td>
                                <td><?php echo format($columns[1]) ?></td>
                                <td><?php echo format($columns[2]) ?></td>
                                <td><?php echo format($columns[3]) ?></td>
                                <td><?php echo format($columns[4]) ?></td>
                                <td><?php echo format($columns[5]) ?></td>
                                <td><?php echo format($columns[6]) ?></td>
                            </tr>
                            <tr>
                                <?php
                                while ($row = mysqli_fetch_assoc($sql_result_for_table)) {
                                ?>
                                <td class="parts-table-row"><?php echo $row[$columns[0]]; ?></td>
                                <td class="parts-table-row"><?php echo $row[$columns[1]]; ?></td>
                                <td class="parts-table-row"><?php echo $row[$columns[2]]; ?></td>
                                <td class="parts-table-row"><?php echo $row[$columns[3]]; ?></td>
                                <td class="parts-table-row"><?php echo $row[$columns[4]]; ?></td>
                                <td class="parts-table-row">$<?php echo number_format($row[$columns[5]]); ?></td>
                                <td class="parts-table-row">$<?php echo number_format($row[$columns[6]]); ?></td>
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