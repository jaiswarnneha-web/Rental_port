<?php
session_start();
require 'conn.php';
if(!isset($_SESSION['user_id']) || $_SESSION['userType']!=='Tenant'){
    header("Location: access.html");
    exit();
}

$tenant_id = $_SESSION['user_id'];

// Handle Booking
if(isset($_POST['book'])){
    $property_id = $_POST['property_id'];
    $duration_days = $_POST['duration_days'];
    $proposed_price = $_POST['proposed_price'];

    $stmt = $con->prepare("INSERT INTO bookings (tenant_id, property_id, duration_days, proposed_price, status, booking_date) VALUES (?,?,?,?, 'Pending', NOW())");
    $stmt->bind_param("iiid", $tenant_id, $property_id, $duration_days, $proposed_price);
    $stmt->execute();
    $stmt->close();

    $success_msg = "Booking request sent successfully!";
}

// Handle Deletion of Booking
if(isset($_POST['delete_booking'])){
    $booking_id = $_POST['booking_id'];
    $stmt = $con->prepare("DELETE FROM bookings WHERE id=? AND tenant_id=?");
    $stmt->bind_param("ii", $booking_id, $tenant_id);
    $stmt->execute();
    $stmt->close();

    $success_msg = "Booking application has been withdrawn.";
}

$properties = $con->query("SELECT p.*, u.name AS owner_name FROM properties p JOIN users u ON p.owner_id=u.id ORDER BY p.id DESC");

$bookings = $con->query("
    SELECT b.id, p.property_name, p.type, p.address, p.image, b.booking_date, b.duration_days, b.proposed_price, b.status
    FROM bookings b
    JOIN properties p ON b.property_id = p.id
    WHERE b.tenant_id = '$tenant_id'
    ORDER BY b.booking_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tenant Dashboard</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
body {font-family:'Segoe UI',sans-serif; background:#f5f5f5; margin:0; padding:0; background-color: antiquewhite;}
.container {padding:40px;}
.card-property {border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); margin-bottom:20px; overflow:hidden;background:white;}
.card-property img {width:100%; height:200px; object-fit:cover;}
.card-property-body {padding:15px;}
.card-property-body h5 {margin-bottom:5px;}
.card-property-body p {margin-bottom:5px; font-size:0.9rem;}
.btn-book {width:100%;}
.alert-success {margin-bottom:20px;}
</style>
</head>
<body>

<!-- Navbar -->
<div id="nav-container"></div>

<div class="container">
<h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Tenant)</h2>
<a href="access.html" class="btn btn-secondary mb-3">Logout</a>

<?php if(isset($success_msg)): ?>
<div class="alert alert-success"><?php echo $success_msg; ?></div>
<?php endif; ?>

<h4>Available Properties</h4>
<div class="row">
<?php while($p = $properties->fetch_assoc()): ?>
<div class="col-md-4">
    <div class="card-property">
        <?php if($p['image'] && file_exists('uploads/'.$p['image'])): ?>
            <img src="uploads/<?php echo $p['image']; ?>" alt="Property Image">
        <?php else: ?>
            <img src="default.jpg" alt="No Image">
        <?php endif; ?>
        <div class="card-property-body">
            <h5><?php echo htmlspecialchars($p['property_name']); ?></h5>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($p['type']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($p['address']); ?></p>
            <p><strong>Price:</strong> ₹<?php echo $p['price']; ?></p>

            <!-- Booking Form -->
            <form method="POST">
                <input type="hidden" name="property_id" value="<?php echo $p['id']; ?>">
                <div class="mb-2">
                    <label>Duration (days):</label>
                    <input type="number" name="duration_days" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Proposed Price:</label>
                    <input type="number" name="proposed_price" class="form-control" required>
                </div>
                <button type="submit" name="book" class="btn btn-success btn-book">Book</button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>

<h4 class="mt-4">My Bookings</h4>
<table class="table table-bordered">
<thead>
<tr>
    <th>Property</th><th>Type</th><th>Image</th><th>Booking Date</th><th>Duration</th><th>Proposed Price</th><th>Status</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php while($b = $bookings->fetch_assoc()): ?>
<tr>
    <td><?php echo htmlspecialchars($b['property_name']); ?></td>
    <td><?php echo htmlspecialchars($b['type']); ?></td>
    <td>
        <?php if($b['image'] && file_exists('uploads/'.$b['image'])): ?>
            <img src="uploads/<?php echo $b['image']; ?>" style="width:80px; height:auto; border-radius:5px;">
        <?php else: ?>No Image<?php endif; ?>
    </td>
    <td><?php echo $b['booking_date']; ?></td>
    <td><?php echo $b['duration_days']; ?></td>
    <td>₹<?php echo $b['proposed_price']; ?></td>
    <td><?php echo $b['status']; ?></td>
    <td>
        <form method="POST">
            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
            <button type="submit" name="delete_booking" class="btn btn-danger btn-sm">Withdraw</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>

<!-- Footer -->
<div id="footer-container"></div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
fetch("navbar.html").then(r=>r.text()).then(d=>document.getElementById("nav-container").innerHTML=d);
fetch("footer.html").then(r=>r.text()).then(d=>document.getElementById("footer-container").innerHTML=d);
</script>

</body>
</html>
