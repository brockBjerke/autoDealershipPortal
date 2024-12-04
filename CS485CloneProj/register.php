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

$nameErr = $passErr = $empIdError = $fnameError = $lnameError = $phoneNumberError = $EfnameError = $ElnameError = $EphoneNumberError = $EworkNumberError = $relationError = "";
$enteredName = $enteredPassword = $enteredEmpId = $enteredFName = $enteredLName = $enteredPosition = $enteredPhoneNumber = $enteredEfname = $enteredElname = $enteredEphoneNumber = $enteredEworkNumber = $enteredRelation = "";

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST["uname"])) {
        $nameErr = "Name is required";
    } else {
        $enteredName = $_POST["uname"];
    }
    if (empty($_POST["pass"])) {
        $passErr = "Password is required";
    } else {
        $enteredPassword = $_POST["pass"];
    }
    if (empty($_POST["empId"]) || !preg_match("/^e\d{5}$/", $_POST["empId"])) {
        if (empty($_POST["empId"])) {
            $empIdError = "Employee ID is required";
        } elseif (!preg_match("/^e\d{5}$/", $_POST["empId"])) {
            $empIdError = "Employee ID is not in the correct form";
        }
    } else {
        $enteredEmpId = $_POST["empId"];
    }
    if (empty($_POST["fName"])) {
        $fnameError = "First name is required";
    } else {
        $enteredFName = $_POST["fName"];
    }
    if (empty($_POST["lName"])) {
        $lnameError = "Last name is required";
    } else {
        $enteredLName = $_POST["lName"];
    }
    if (empty($_POST["positionOptions"])) {
        $enteredPosition = "Unknown";
    } else {
        $enteredPosition = $_POST["positionOptions"];
        $enteredPosition = strtolower($enteredPosition[0]);
    }
    if (empty($_POST["phoneNumber"]) || !preg_match("/^\d{10}$/", $_POST["phoneNumber"])) {
        if (empty($_POST["phoneNumber"])) {
            $phoneNumberError = "Phone number is required";
        } elseif (!preg_match("/^\d{10}$/", $_POST["phoneNumber"])) {
            $phoneNumberError = "Phone number  is not in the correct form";
        }
    } else {
        $enteredPhoneNumber = $_POST["phoneNumber"];
    }

    // Emergency contact details validation
    if (empty($_POST["Efname"])) {
        $EfnameError = "Emergency contact first name is required";
    } else {
        $enteredEfname = $_POST["Efname"];
    }
    if (empty($_POST["Elname"])) {
        $ElnameError = "Emergency contact last name is required";
    } else {
        $enteredElname = $_POST["Elname"];
    }
    if (empty($_POST["EphoneNumber"]) || !preg_match("/^\d{10}$/", $_POST["EphoneNumber"])) {
        if (empty($_POST["EphoneNumber"])) {
            $EphoneNumberError = "Emergency contact cellphone number is required";
        } elseif (!preg_match("/^\d{10}$/", $_POST["EphoneNumber"])) {
            $EphoneNumberError = "Emergency contact cellphone number  is not in the correct form";
        }
    } else {
        $enteredEphoneNumber = $_POST["EphoneNumber"];
    }
    if (empty($_POST["EworkNumber"]) || !preg_match("/^\d{10}$/", $_POST["EworkNumber"])) {
        if (empty($_POST["EworkNumber"])) {
            $EworkNumberError = "Emergency contact work number is required";
        } elseif (!preg_match("/^\d{10}$/", $_POST["EworkNumber"])) {
            $EworkNumberError = "Emergency contact work number is not in the correct form";
        }
    } else {
        $enteredEworkNumber = $_POST["EworkNumber"];
    }
    if (empty($_POST["relation"])) {
        $relationError = "Emergency contact relation is required";
    } else {
        $enteredRelation = $_POST["relation"];
    }

    // Call createUser if all fields are valid
    if (
        empty($nameErr) && empty($passErr) && empty($empIdError) &&
        empty($fnameError) && empty($lnameError) && empty($phoneNumberError) &&
        empty($EfnameError) && empty($ElnameError) && empty($EphoneNumberError) &&
        empty($EworkNumberError) && empty($relationError)
    ) {
        createUser(
            $conn,
            $enteredName,
            $enteredPassword,
            $enteredEmpId,
            $enteredFName,
            $enteredLName,
            $enteredPosition,
            $enteredPhoneNumber,
            $enteredEfname,
            $enteredElname,
            $enteredEphoneNumber,
            $enteredEworkNumber,
            $enteredRelation
        );
    }
}


function createUser($conn,
                    string $username,
                    string $password,
                    string $empID,
                    string $fname,
                    string $lname,
                    string $position,
                    string $phoneNumber,
                    string $Efname,
                    string $Elname,
                    string $EphoneNumber,
                    string $EworkNumber,
                    string $relation)
{
    if (!checkIfIDExists($conn, $empID)) {
//        $conn -> beginTransaction();

        $stmtEmployee = $conn->prepare("INSERT INTO employee (emp_id,f_name,l_name,position, cell_phone_number) VALUES (?,?,?,?,?)");
        $stmtEmployee->bind_param("sssss", $empID, $fname, $lname, $position, $phoneNumber);
        if ($stmtEmployee->execute()) echo "employee entered";

        $stmtLogin = $conn->prepare("INSERT INTO login (username,password,Employee_emp_id) VALUES (?,?,?)");
        $stmtLogin->bind_param("sss", $username, $password, $empID);
        if ($stmtLogin->execute()) echo "login entered";

        $stmtEContact = $conn->prepare("INSERT INTO emergency_contact (contact_f_name,contac_l_name,cell_phone_number,work_phone_number, relation, emp_id) VALUES (?,?,?,?,?,?)");
        $stmtEContact->bind_param("ssssss", $Efname, $Elname, $EphoneNumber, $EworkNumber, $relation, $empID);
        if ($stmtEContact->execute()) echo "Emergency contact entered ";


    } elseif (checkIfIDExists($conn, $empID)) {
        echo "id exists already";
    }
}

function checkIfIDExists($conn, string $empId): bool
{
    // Prepare the SQL query
    $sql_result = $conn->prepare("SELECT emp_id
                                    FROM employee
                                    WHERE employee.emp_id = (?)");
    $sql_result->bind_param("s", $empId);
    $sql_result->execute();

    // Fetch the result
    $empIDresult = $sql_result->get_result();

    // Check if a row exists
    return $empIDresult->num_rows > 0;
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
<body>

<h1>Register Employee</h1>
<div class="searchForm">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <!-- Login Details -->
        <h2>Login Details</h2>
        <label>User Name: </label>
        <input type="text" name="uname" maxlength="49">
        <span class="error" style="color: red">* <?php echo $nameErr; ?></span>
        <br>

        <label>Password: </label>
        <input type="password" name="pass" maxlength="49">
        <span class="error" style="color: red">* <?php echo $passErr; ?></span>
        <br>

        <!-- Employee Details -->
        <h2>Employee Details</h2>
        <label>Employee ID: </label>
        <input type="text" name="empId">
        <span class="error" style="color: red">* <?php echo $empIdError; ?></span>
        <br>

        <label>First Name: </label>
        <input type="text" name="fName" maxlength="49">
        <span class="error" style="color: red">* <?php echo $fnameError; ?></span>
        <br>

        <label>Last Name: </label>
        <input type="text" name="lName" maxlength="49">
        <span class="error" style="color: red">* <?php echo $lnameError; ?></span>
        <br>

        <label>Position: </label>
        <select name="positionOptions">
            <option value="Sales">Sales</option>
            <option value="Technician">Technician</option>
            <option value="Manager">Manager</option>
        </select>
        <br>

        <label>Phone Number: </label>
        <input type="text" name="phoneNumber">
        <span class="error" style="color: red">* <?php echo $phoneNumberError; ?></span>
        <br>

        <!-- Emergency Contact Details -->
        <h2>Emergency Contact Details</h2>
        <label>Emergency Contact First Name: </label>
        <input type="text" name="Efname" maxlength="49">
        <span class="error" style="color: red">* <?php echo $EfnameError; ?></span>
        <br>

        <label>Emergency Contact Last Name: </label>
        <input type="text" name="Elname" maxlength="49">
        <span class="error" style="color: red">* <?php echo $ElnameError; ?></span>
        <br>

        <label>Emergency Contact Phone Number: </label>
        <input type="text" name="EphoneNumber">
        <span class="error" style="color: red">* <?php echo $EphoneNumberError; ?></span>
        <br>

        <label>Emergency Contact Work Number: </label>
        <input type="text" name="EworkNumber">
        <span class="error" style="color: red">* <?php echo $EworkNumberError; ?></span>
        <br>

        <label>Relationship to Employee: </label>
        <input type="text" name="relation" maxlength="49">
        <span class="error" style="color: red">* <?php echo $relationError; ?></span>
        <br>

        <!-- Submit Button -->
        <button type="submit" name="submit">Submit</button>
    </form>
</div>
</body>
</html>