<?php
session_start();

// Check if user or teacher is logged in
$is_teacher = isset($_SESSION['teacher_id']);
$is_user = isset($_SESSION['user_id']);

if (!$is_teacher && !$is_user) {
    header("Location: UserLogin.html");
    exit();
}

// Get team_id from URL
$team_id = intval($_GET['team_id'] ?? 0);
if ($team_id <= 0) {
    header("Location: " . ($is_teacher ? "TeacherAdmin.php" : "Profile.php"));
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify access to the team
if ($is_teacher) {
    // Teacher: verify the team belongs to this teacher
    $stmt_verify = $conn->prepare("SELECT team_name FROM teams WHERE id = ? AND teacher_id = ?");
    $stmt_verify->bind_param("ii", $team_id, $_SESSION['teacher_id']);
} else {
    // User: verify the user is a member of this team
    $stmt_verify = $conn->prepare("SELECT t.team_name FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE t.id = ? AND tm.user_id = ?");
    $stmt_verify->bind_param("ii", $team_id, $_SESSION['user_id']);
}

$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();

if ($result_verify->num_rows == 0) {
    header("Location: " . ($is_teacher ? "TeacherAdmin.php" : "Profile.php"));
    exit();
}

$team = $result_verify->fetch_assoc();
$team_name = $team['team_name'];
$stmt_verify->close();

// Fetch products for this team
$products = [];
$stmt = $conn->prepare("SELECT product_id, product_name, product_description, quantity, product_price, product_image, status, upload_date FROM products WHERE team_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - <?php echo htmlspecialchars($team_name); ?></title>
    <link rel="stylesheet" href="teacher_admin.css" />
    <style>
        .products-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
        }
        .products-section h2 {
            color: #c60000;
            margin-bottom: 20px;
            font-size: 22px;
        }
        .product-card {
            display: flex;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            align-items: center;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .product-info {
            flex: 1;
        }
        .product-info h3 {
            margin: 0 0 5px 0;
            color: #c60000;
        }
        .product-info p {
            margin: 2px 0;
            font-size: 14px;
            color: #666;
        }
        .product-actions {
            display: flex;
            gap: 10px;
        }
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        .edit-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f9f9fa;
            border-radius: 6px;
        }
        .edit-form input, .edit-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .edit-form textarea {
            height: 80px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($_SESSION['school_name']); ?></h1>
        <p>Manage Products for Team: <?php echo htmlspecialchars($team_name); ?></p>
        <div class="logout">
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="products-section">
            <h2>Products (<?php echo count($products); ?>)</h2>
            <?php if (!$is_teacher): ?>
                <a href="upload_product.php?team_id=<?php echo $team_id; ?>" class="btn" style="margin-bottom: 20px;">Upload New Product</a>
            <?php endif; ?>
            <a href="<?php echo $is_teacher ? 'TeacherAdmin.php' : 'Profile.php'; ?>" class="btn" style="background: #6c757d; margin-left: 10px; margin-bottom: 20px;">Back to Teams</a>

            <?php if (empty($products)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No products uploaded yet. <a href="upload_product.php?team_id=<?php echo $team_id; ?>">Upload your first product</a>.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" id="product-<?php echo $product['product_id']; ?>">
                        <img src="product_images/<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product Image" class="product-image">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['product_description']); ?></p>
                            <p>Quantity: <?php echo htmlspecialchars($product['quantity']); ?> | Price: ₹<?php echo htmlspecialchars($product['product_price']); ?></p>
                            <p>Status: <span class="status-<?php echo $product['status']; ?>"><?php echo ucfirst($product['status']); ?></span> | Uploaded: <?php echo date('d M Y', strtotime($product['upload_date'])); ?></p>
                        </div>
                        <div class="product-actions">
                            <?php if (!$is_teacher): ?>
                                <button class="btn" onclick="toggleEdit(<?php echo $product['product_id']; ?>)">Edit</button>
                                <button class="delete-btn" onclick="deleteProduct(<?php echo $product['product_id']; ?>)">Delete</button>
                            <?php else: ?>
                                <?php if ($product['status'] === 'pending'): ?>
                                    <button class="btn green" onclick="approveProduct(<?php echo $product['product_id']; ?>)">Approve</button>
                                    <button class="btn" style="background: #dc3545;" onclick="rejectProduct(<?php echo $product['product_id']; ?>)">Reject</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="edit-form" id="edit-form-<?php echo $product['product_id']; ?>">
                        <form onsubmit="updateProduct(event, <?php echo $product['product_id']; ?>)">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                            <textarea name="product_description" required><?php echo htmlspecialchars($product['product_description']); ?></textarea>
                            <input type="number" name="quantity" value="<?php echo htmlspecialchars($product['quantity']); ?>" min="1" required>
                            <input type="number" name="product_price" value="<?php echo htmlspecialchars($product['product_price']); ?>" min="0.01" step="0.01" required>
                            <button type="submit" class="btn green">Update Product</button>
                            <button type="button" class="btn" style="background: #6c757d;" onclick="toggleEdit(<?php echo $product['product_id']; ?>)">Cancel</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEdit(productId) {
            const form = document.getElementById('edit-form-' + productId);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }

        function updateProduct(event, productId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            fetch('update_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the product.');
            });
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                fetch('delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the product.');
                });
            }
        }

        function approveProduct(productId) {
            if (confirm('Are you sure you want to approve this product?')) {
                fetch('approve_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving the product.');
                });
            }
        }

        function rejectProduct(productId) {
            if (confirm('Are you sure you want to reject this product?')) {
                fetch('reject_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product rejected successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while rejecting the product.');
                });
            }
        }
    </script>
</body>
</html>
