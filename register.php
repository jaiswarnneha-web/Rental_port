<?php
require 'conn.php'; // DB connection

if (isset($_POST['name'], $_POST['email'], $_POST['pno'], $_POST['password'], $_POST['userType'], $_POST['address'], $_POST['city'], $_POST['state'], $_POST['zip'], $_POST['gender'])) {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $pno      = trim($_POST['pno']);
    $password = $_POST['password'];
    $userType = $_POST['userType'];
    $address  = trim($_POST['address']);
    $city     = $_POST['city'];
    $state    = $_POST['state'];
    $zip      = trim($_POST['zip']);
    $gender   = $_POST['gender'];

    $check = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($con, $check);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Email already registered'); window.location.href='register.html';</script>";
        exit();
    }

    $q = "INSERT INTO users (name, email, pno, password, userType, address, city, state, zip, gender) 
          VALUES ('$name', '$email', '$pno', '$password', '$userType', '$address', '$city', '$state', '$zip', '$gender')";

    if (mysqli_query($con, $q)) {
        echo "<script>alert('Registration successful'); window.location.href='access.html';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($con) . "'); window.location.href='register.html';</script>";
    }

} else {
    echo "<script>alert('All fields are required'); window.location.href='register.html';</script>";
}
?>
