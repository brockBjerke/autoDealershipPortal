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


$enteredName = $enteredPassword = "";

$nameErr = $passErr = "";

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
}

$sql_result = $conn->prepare("SELECT * FROM login WHERE username = (?)");
$sql_result->bind_param("s", $enteredName);
$sql_result->execute();
$result = $sql_result->get_result();
$resultArray = $result->fetch_array();

function toNextPage($url)
{
    header($url);
}

if (($enteredName && $enteredPassword) != "") {
    if ($resultArray === null) {
        // Trigger register page
//        toNextPage("Location: http://localhost:8797/WebBasedInterfaceToMySQL/register.php");
        echo "not an authorized user";
    } else {

        $password_from_table = $resultArray[1];
        $id_from_table = $resultArray[2];

        if ($enteredPassword != "") {
            if ($password_from_table === $enteredPassword) {
                // Trigger 'Welcome' page query with id from table to
                $sql_resultWorker = $conn->prepare("SELECT f_name, l_name, position, emp_id
                                                    FROM login as l
                                                    JOIN employee as e ON l.Employee_emp_id = e.emp_id
                                                    WHERE l.Employee_emp_id = (?)");
                $sql_resultWorker->bind_param("s", $id_from_table);
                $sql_resultWorker->execute();
                $resultWorker = $sql_resultWorker->get_result();
                $resultWorkerArray = $resultWorker->fetch_array();
                $_SESSION['loginName'] = $resultWorkerArray[0];
                $_SESSION['loginLastname'] = $resultWorkerArray[1];
                $_SESSION['loginPosition'] = $resultWorkerArray[2];
                $_SESSION['loginEId'] = $resultWorkerArray[3];
                toNextPage("Location: http://localhost:63342/CS485CloneProj/CS485%20Final%20Project/homePage.php?_ijt=jcgc5833qo26pkp48bphntl7kj&_ij_reload=RELOAD_ON_SAVE");
            } else {
                echo "not an authorized user";
            }
        }
    }
}

$conn->close();

?>

<h1>Login to your account:</h1>
<div id="loginBox">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label>User Name: </label>
        <input type="text" name="uname"></input>
        <span class="error" style="color: red">* <?php echo $nameErr; ?></span>
        <br>
        <label>Password: </label>
        <input type="password" name="pass"></input>
        <span class="error" style="color: red">* <?php echo $passErr; ?></span>
        <br> <br>
        <button type="submit" name="submit">Submit</button>
    </form>
</div>
</body>
</html>