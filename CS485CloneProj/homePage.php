<?php
session_start();
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


$registerLink = ''; // Initialize the variable
if (isset($_SESSION['loginPosition']) && $_SESSION['loginPosition'] == 'm') {
    $registerLink = '<a href="register.php">Register Page</a>';

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
    <h1>Welcome to the Car Sales Portal</h1>
</head>
<body>
<nav>

    <?php if ($_SESSION['loginPosition'] === 's') {
        echo "<a href='vehicleSearch.php'>Car Search</a>";
        echo "<a href='addVehicle.php'>Add Vehicle</a>";
        echo "<a href='schedule.php'>Schedule</a>";
        echo "<a href='appointment.php'>Appointment</a>";
    }?>
    <?php if ($_SESSION['loginPosition'] === 't') {
        echo "<a href='partsSearch.php'>Parts Search</a>";
        echo "<a href='schedule.php'>Schedule</a>";
        echo "<a href='appointment.php'>Appointment</a>";
    }?>
    <?php if ($_SESSION['loginPosition'] === 'm') {
        echo "<a href='vehicleSearch.php'>Car Search</a>";
        echo "<a href='addVehicle.php'>Add Vehicle</a>";
        echo "<a href='partsSearch.php'>Parts Search</a>";
        echo "<a href='schedule.php'>Schedule</a>";
        echo "<a href='appointment.php'>Appointment</a>";
    }?>
    <?php echo $registerLink; ?>
    <!-- Display user info and logout button next to each other -->
    <span class="user-info"><?php echo $introText; ?></span> <!-- Display user info -->
    <a href="?logout=true" class="logout-btn">Logout</a> <!-- Logout Link -->
</nav>
<br> <br>

<div class="textArea">
    <img src="sleezy-car-dealership.jpg" alt="Car City Picture" class="small-image" />
    <h2>About Us</h2>

    <p>Welcome to Car City – your go-to destination for cars at unbeatable prices! We specialize in getting cars off our lot fast. guaranteed 30-day warranty (terms and conditions apply… don’t ask us what they are).</p>

    <p>Our expert mechanics make sure every car is "as good as new"! Our goal is simple: get you in a car, get you out the door  no fuss, no questions asked. Drive away with a smile.</p>

    <p>Here at Car City, you’ll find deals that are so good, they might make you question reality. Why pay more at the dealer when you can roll off with a car that’s a bargain? Call us today at 320-438-8164.</p>

    <p>Ready to take the plunge? Visit us and see our collection of vehicles.</p>

    <p>Car City – Because everyone deserves a car.</p>
</div>



<!-- Link to the Car Search Page -->



</body>
</html>