<?php
session_start();
require 'conn.php'; 

if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['userType'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $userType = $_POST['userType'];
    $email = mysqli_real_escape_string($con, $email);

    // Simple query to check user
    $query = "SELECT * FROM users WHERE email='$email' AND userType='$userType' LIMIT 1";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if ($password === $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['userType'] = $row['userType'];

            // Redirect based on user type
            if ($row['userType'] === 'Tenant') {
                header("Location: tenant.php");
                exit();
            } elseif ($row['userType'] === 'Landlord') {
                header("Location: landlord.php");
                exit();
            }

        } else {
            echo "Wrong password!";
        }

    } else {
        echo "User not found or incorrect user type!";
    }

} else {
    echo "All fields are required!";
}
?>
