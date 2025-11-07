<?php
session_start();
require 'conn.php';

// Check session and user type
if (!isset($_SESSION['user_id']) || $_SESSION['userType'] !== 'Landlord') {
    header("Location: access.html");
    exit();
}

$landlord_id = $_SESSION['user_id'];

// Add Property
if (isset($_POST['add'])) {
    $name    = $_POST['property_name'];
    $address = $_POST['address'];
    $price   = $_POST['price'];
    $type    = $_POST['property_type'];

    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '.' . $ext;
        if (!is_dir('uploads')) mkdir('uploads', 0755, true);
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
    }

    $q = "INSERT INTO properties (owner_id, property_name, address, price, type, image) 
          VALUES ('$landlord_id', '$name', '$address', '$price', '$type', '$image_name')";
    mysqli_query($con, $q);

    header("Location: landlord.php");
    exit();
}

// Delete Property
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $q = "DELETE FROM properties WHERE id='$id' AND owner_id='$landlord_id'";
    mysqli_query($con, $q);

    header("Location: landlord.php");
    exit();
}

// Accept or Reject Booking
if (isset($_GET['booking_id'], $_GET['action'])) {
    $booking_id = $_GET['booking_id'];
    $status     = ($_GET['action'] === 'accept') ? 'Confirmed' : 'Cancelled';

    $q = "UPDATE bookings b 
          JOIN properties p ON b.property_id = p.id 
          SET b.status = '$status' 
          WHERE b.id = '$booking_id' AND p.owner_id = '$landlord_id'";
    mysqli_query($con, $q);

    header("Location: landlord.php");
    exit();
}

// Fetch Properties
$properties = mysqli_query($con, "SELECT * FROM properties WHERE owner_id='$landlord_id'");

// Fetch Bookings
$bookings = mysqli_query($con, "
    SELECT b.id, u.name AS tenant_name, p.property_name, p.type, p.image, b.booking_date, b.duration_days, b.status, b.proposed_price 
    FROM bookings b 
    JOIN properties p ON b.property_id = p.id 
    JOIN users u ON b.tenant_id = u.id
    WHERE p.owner_id = '$landlord_id'
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            background-color: antiquewhite;
        }

        .container {
            padding: 40px;
        }

        .card {
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
            border-radius: 10px;
        }

        table th,
        table td {
            text-align: center;
            vertical-align: middle;
            background: #000;
        }

        img.img-thumb {
            width: 100px;
            height: auto;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div id="nav-container"></div>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Landlord)</h2>

        <!-- Logout Button -->
        <a href="access.html" class="btn btn-secondary mb-3">Logout</a>

        <!-- Add Property -->
        <div class="card">
            <h4>Add Property</h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="property_name" placeholder="Property Name" class="form-control mb-2" required>
                <input type="text" name="address" placeholder="Address" class="form-control mb-2" required>
                <input type="number" name="price" placeholder="Price" class="form-control mb-2" required>

                <div class="mb-2">
                    <label>Property Type:</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="property_type" id="apartment" value="Apartment" required>
                        <label class="form-check-label" for="apartment">Apartment</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="property_type" id="hostel" value="Hostel" required>
                        <label class="form-check-label" for="hostel">Hostel</label>
                    </div>
                </div>

                <input type="file" name="image" class="form-control mb-2" accept="image/*" required>
                <button type="submit" name="add" class="btn btn-success">Insert Property</button>
            </form>
        </div>

        <!-- My Properties -->
        <div class="card">
            <h4>My Properties</h4>
            <table class="table table-bordered">
                <thead>
                    <tr><th>Image</th><th>Name</th><th>Type</th><th>Address</th><th>Price</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while ($p = mysqli_fetch_assoc($properties)): ?>
                    <tr>
                        <td>
                            <?php if ($p['image'] && file_exists('uploads/'.$p['image'])): ?>
                                <img src="uploads/<?php echo $p['image']; ?>" class="img-thumb">
                            <?php else: ?>No Image<?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['property_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['type']); ?></td>
                        <td><?php echo htmlspecialchars($p['address']); ?></td>
                        <td><?php echo $p['price']; ?></td>
                        <td><a href="?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete property?')">Delete</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Manage Bookings -->
        <div class="card">
            <h4>Tenant Bookings</h4>
            <table class="table table-bordered ">
                <thead>
                    <tr>
                        <th>Tenant</th><th>Property</th><th>Type</th><th>Image</th><th>Date</th><th>Duration</th><th>Proposed Price</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['tenant_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['property_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['type']); ?></td>
                        <td>
                            <?php if ($b['image'] && file_exists('uploads/'.$b['image'])): ?>
                                <img src="uploads/<?php echo $b['image']; ?>" class="img-thumb">
                            <?php else: ?>No Image<?php endif; ?>
                        </td>
                        <td><?php echo $b['booking_date']; ?></td>
                        <td><?php echo $b['duration_days']; ?></td>
                        <td><?php echo $b['proposed_price'] ? $b['proposed_price'] : '-'; ?></td>
                        <td><?php echo $b['status']; ?></td>
                        <td>
                            <?php if ($b['status'] === 'Pending'): ?>
                                <a href="?booking_id=<?php echo $b['id']; ?>&action=accept" class="btn btn-success btn-sm">Accept</a>
                                <a href="?booking_id=<?php echo $b['id']; ?>&action=reject" class="btn btn-danger btn-sm">Reject</a>
                            <?php else: ?>No Action<?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Footer -->
    <div id="footer-container"></div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        fetch("navbar.html").then(r => r.text()).then(d => document.getElementById("nav-container").innerHTML = d);
        fetch("footer.html").then(r => r.text()).then(d => document.getElementById("footer-container").innerHTML = d);
    </script>
</body>
</html>
