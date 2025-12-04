<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">User not logged in</div>';
    exit();
}

$team_id = intval($_GET['team_id'] ?? 0);
if ($team_id <= 0) {
    echo '<div class="alert alert-danger">Invalid team ID</div>';
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
    echo '<div class="alert alert-danger">Database connection failed</div>';
    exit();
}

// Verify user is member of the team
$stmt_verify = $conn->prepare("SELECT t.team_name FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE t.id = ? AND tm.user_id = ?");
$stmt_verify->bind_param("ii", $team_id, $_SESSION['user_id']);
$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();

if ($result_verify->num_rows == 0) {
    echo '<div class="alert alert-danger">Access denied</div>';
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
?>

<div class="products-section">
    <h2>Products (<?php echo count($products); ?>)</h2>
    <button onclick="showUploadForm()" class="btn" style="margin-bottom: 20px;">Upload New Product</button>
    <div id="upload-form-container" style="display: none;">
        <div class="edit-form" style="display: block; margin-bottom: 20px;">
            <h3>Upload New Product</h3>
            <form id="upload-product-form" onsubmit="uploadProduct(event)">
                <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" name="product_name" id="product_name" required>
                </div>
                <div class="form-group">
                    <label for="product_description">Description</label>
                    <textarea name="product_description" id="product_description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="product_price">Price (₹)</label>
                    <input type="number" name="product_price" id="product_price" min="0.01" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    <input type="file" name="product_image" id="product_image" accept="image/*" required>
                </div>
                <button type="submit" class="btn green">Upload Product</button>
                <button type="button" class="btn" style="background: #6c757d;" onclick="hideUploadForm()">Cancel</button>
            </form>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <p style="text-align: center; color: #666; padding: 40px;">No products uploaded yet. Click "Upload New Product" to get started.</p>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="product-card" id="product-<?php echo $product['product_id']; ?>">
                <div class="product-header">
                    <img 
                        src="product_images/<?php echo htmlspecialchars($product['product_image']); ?>" 
                        alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                        class="product-image"
                        onerror="this.src='images/no-image.png'"
                    >
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars($product['product_description']); ?></p>
                        <p class="product-meta">
                            Quantity: <span class="product-quantity"><?php echo $product['quantity']; ?></span> | 
                            Price: ₹<span class="product-price"><?php echo number_format($product['product_price'], 2); ?></span>
                        </p>
                        <p class="product-meta">
                            Status: <span class="status-badge status-<?php echo $product['status']; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                            | Uploaded: <?php echo date('d M Y', strtotime($product['upload_date'])); ?>
                        </p>
                    </div>
                </div>

                <?php if ($product['status'] === 'pending'): ?>
                <div class="product-actions">
                    <button class="btn edit-btn" onclick="toggleEdit(<?php echo $product['product_id']; ?>)">Edit</button>
                    <button class="delete-btn" onclick="deleteProduct(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_name'], ENT_QUOTES); ?>')">Delete</button>
                </div>

                <div class="edit-form" id="edit-form-<?php echo $product['product_id']; ?>" style="display: none;">
                    <form onsubmit="return updateProduct(<?php echo $product['product_id']; ?>)" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="product_description" required><?php echo htmlspecialchars($product['product_description']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Price (₹)</label>
                            <input type="number" name="product_price" value="<?php echo $product['product_price']; ?>" min="0.01" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>New Image (optional)</label>
                            <input type="file" name="product_image" accept="image/*">
                            <small>Leave empty to keep current image</small>
                        </div>
                        <div class="form-buttons">
                            <button type="submit" class="btn edit-btn">Save Changes</button>
                            <button type="button" class="btn" onclick="toggleEdit(<?php echo $product['product_id']; ?>)">Cancel</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function updateProduct(productId) {
        const form = document.querySelector(`#edit-form-${productId} form`);
        const formData = new FormData(form);
        formData.append('product_id', productId);

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        fetch('update_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the UI with new values
                const card = document.getElementById('product-' + productId);
                if (card) {
                    card.querySelector('.product-name').textContent = data.product.product_name;
                    card.querySelector('.product-description').textContent = data.product.product_description;
                    card.querySelector('.product-quantity').textContent = data.product.quantity;
                    card.querySelector('.product-price').textContent = parseFloat(data.product.product_price).toFixed(2);
                    
                    // If there's a new image
                    if (formData.get('product_image').size > 0) {
                        const img = card.querySelector('.product-image');
                        if (img) {
                            // Force reload the image to bypass cache
                            img.src = `product_images/${data.product.product_image}?t=${Date.now()}`;
                        }
                    }
                }
                toggleEdit(productId);
                alert('Product updated successfully');
            } else {
                alert('Error: ' + (data.message || 'Could not update product'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating product');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });

        return false; // Prevent form submission
    }

    function deleteProduct(productId, productName) {
        if (!confirm(`Are you sure you want to delete "${productName}"?`)) {
            return;
        }

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
                // Remove the product card from UI
                const card = document.getElementById('product-' + productId);
                if (card) {
                    card.remove();
                }
                alert('Product deleted successfully');
            } else {
                alert('Error: ' + (data.message || 'Could not delete product'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting product');
        });
    }

    function toggleEdit(productId) {
        const form = document.getElementById('edit-form-' + productId);
        const isVisible = form.style.display === 'block';
        
        // Hide all edit forms first
        document.querySelectorAll('.edit-form').forEach(form => {
            form.style.display = 'none';
        });
        
        // Toggle this form
        form.style.display = isVisible ? 'none' : 'block';
    }

    function showUploadForm() {
        document.getElementById('upload-form-container').style.display = 'block';
    }

    function hideUploadForm() {
        document.getElementById('upload-form-container').style.display = 'none';
    }

    function uploadProduct(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        fetch('process_upload_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product uploaded successfully!');
                hideUploadForm();
                loadContent('products'); // Reload the products list
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while uploading the product.');
        });
    }
</script>

<style>
.products-section {
    padding: 20px;
}

.product-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
}

.product-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

.product-info {
    margin-left: 15px;
    flex: 1;
}

.product-name {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    margin: 0 0 5px 0;
}

.product-description {
    color: #666;
    margin-bottom: 10px;
}

.product-meta {
    color: #888;
    font-size: 14px;
    margin: 5px 0;
}

.product-actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.btn:hover {
    opacity: 0.9;
}

.edit-btn {
    background: #007bff;
    color: white;
}

.delete-btn {
    background: #dc3545;
    color: white;
}

.edit-form {
    margin-top: 15px;
    padding: 15px;
    background: #f8f8f8;
    border-radius: 8px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 14px;
}

.form-group textarea {
    height: 100px;
    resize: vertical;
}

.form-group input[type="file"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
}

.form-group small {
    display: block;
    color: #666;
    margin-top: 4px;
    font-size: 12px;
}

.form-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}
</style>