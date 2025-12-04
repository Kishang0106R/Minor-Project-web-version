<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">User not logged in</div>';
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

$user_id = $_SESSION['user_id'];

// Get user's team information
$stmt = $conn->prepare("SELECT t.id as team_id, t.team_name FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE tm.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<div class="alert alert-warning">You must be a member of a team to upload products. Please join a team first.</div>';
    exit();
}

$team = $result->fetch_assoc();
$team_id = $team['team_id'];
$team_name = $team['team_name'];
$stmt->close();
$conn->close();
?>

<div class="upload-section">
  <h2>Upload Product for Team: <?php echo htmlspecialchars($team_name); ?></h2>
  <div id="upload-message"></div>
  <form id="upload-product-form" enctype="multipart/form-data">
    <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
    <div class="form-group">
      <label for="product_name">Product Name *</label>
      <input type="text" id="product_name" name="product_name" required>
    </div>
    <div class="form-group">
      <label for="product_description">Product Description *</label>
      <textarea id="product_description" name="product_description" required></textarea>
    </div>
    <div class="form-group">
      <label for="quantity">Quantity *</label>
      <input type="number" id="quantity" name="quantity" min="1" required>
    </div>
    <div class="form-group">
      <label for="product_price">Price (₹) *</label>
      <input type="number" id="product_price" name="product_price" min="0.01" step="0.01" required>
    </div>
    <div class="form-group">
      <label for="product_image">Product Image *</label>
      <input type="file" id="product_image" name="product_image" accept="image/*" required>
    </div>
    <button type="submit" class="btn">Upload Product</button>
  </form>
</div>

<style>
.upload-section { background: #fff; padding: 20px; border-radius: 8px; }
.upload-section h2 { color: #c60000; margin-bottom: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input[type="text"], .form-group input[type="number"], .form-group textarea {
  width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
}
.form-group textarea { height: 100px; resize: vertical; }
.form-group input[type="file"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background-color: white; }
.btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn:hover { background: #0056b3; }
.alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
</style>

<script>
document.getElementById('upload-product-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const messageDiv = document.getElementById('upload-message');

  fetch('process_upload_product.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
      form.reset();
      // Optionally reload products content
      loadContent('products');
    } else {
      messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    messageDiv.innerHTML = '<div class="alert alert-danger">An error occurred while uploading the product.</div>';
  });
});
</script>
