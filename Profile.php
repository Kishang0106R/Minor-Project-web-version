<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');
if (!isset($_SESSION['user_id'])) {
    header("Location: UserLogin.html");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database configuration
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty)
$dbname = "school_management_system"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
$conn->query($sql);

// Select the database
$conn->select_db($dbname);

// Create table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$conn->query($table_sql);

// Add missing columns if they don't exist
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(15) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(10) DEFAULT ''");

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, phone, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $gender);
$stmt->fetch();

$userData = [
    'user_id' => $user_id,
    'name' => $name,
    'email' => $email,
    'phone' => $phone ?: '',
    'gender' => $gender ?: '',
    'profile_photo' => 'images/profilePhoto.png'
];

$stmt->close();

// Check if user is part of any team
$is_in_team = false;
$stmt_team = $conn->prepare("SELECT COUNT(*) as team_count FROM team_members WHERE user_id = ?");
$stmt_team->bind_param("i", $user_id);
$stmt_team->execute();
$result_team = $stmt_team->get_result();
if ($result_team->num_rows > 0) {
    $row_team = $result_team->fetch_assoc();
    $is_in_team = $row_team['team_count'] > 0;
}
$stmt_team->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Profile</title>

  <style>
    /* Base styles */
    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background-color: #f5f5f5;
    }

    /* Header styles */
    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .header-left .logo-image {
      height: 50px;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .profile-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .logout-btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 4px;
      background-color: #ff4444;
      color: white;
      cursor: pointer;
    }

    /* Main container styles */
    .main-container {
      display: flex;
      margin: 2rem;
      gap: 2rem;
    }

    /* Sidebar styles */
    .sidebar {
      flex: 0 0 250px;
      background-color: white;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .sidebar h2 {
      margin-bottom: 1rem;
      color: #333;
    }

    .sidebar nav {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .sidebar button {
      padding: 0.75rem 1rem;
      border: none;
      background-color: transparent;
      text-align: left;
      cursor: pointer;
      border-radius: 4px;
      transition: background-color 0.3s;
    }

    .sidebar button:hover {
      background-color: #f0f0f0;
    }

    .sidebar button.active {
      background-color: #007bff;
      color: white;
    }

    /* Content section styles */
    .content {
      flex: 1;
      background-color: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Form styles */
    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #333;
      font-weight: 500;
    }

    .form-group label::after {
      content: " *";
      color: #dc3545;
      display: none;
    }

    .form-group label.required::after {
      display: inline;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
      transition: all 0.2s ease-in-out;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    .form-group input:disabled,
    .form-group input[readonly] {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    .field-hint {
      font-size: 0.875rem;
      color: #6c757d;
      margin-top: 0.25rem;
    }

    .error-message {
      color: #dc3545;
      font-size: 0.875rem;
      margin-top: 0.25rem;
      display: none;
    }

    .alert {
      padding: 1rem;
      margin-bottom: 1rem;
      border: 1px solid transparent;
      border-radius: 0.25rem;
    }

    .alert.success {
      color: #155724;
      background-color: #d4edda;
      border-color: #c3e6cb;
    }

    .alert.error {
      color: #721c24;
      background-color: #f8d7da;
      border-color: #f5c6cb;
    }

    .save-btn {
      padding: 0.75rem 1.5rem;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 500;
      transition: all 0.2s ease-in-out;
    }

    .save-btn:hover {
      background-color: #218838;
      transform: translateY(-1px);
    }

    .save-btn:disabled {
      background-color: #6c757d;
      cursor: not-allowed;
      transform: none;
    }

    /* Loading animation for button */
    .save-btn:disabled::after {
      content: '';
      display: inline-block;
      width: 1rem;
      height: 1rem;
      margin-left: 0.5rem;
      border: 2px solid #fff;
      border-radius: 50%;
      border-top-color: transparent;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
  <script>
    var userData = <?php echo json_encode($userData); ?>;

    // Function to load content based on button click
    function loadContent(contentType) {
      const contentSection = document.querySelector('.content');
      
      switch(contentType) {
        case 'personal':
          // Get CSRF token from PHP session
          const csrfToken = '<?php echo $_SESSION["csrf_token"] ?? "" ?>';
          
          contentSection.innerHTML = `
            <h2>Personal Information</h2>
            <div id="alertMessage" style="display: none;"></div>
            <form id="personalInfoForm">
              <input type="hidden" name="csrf_token" value="${csrfToken}">
              
              <div class="form-group">
                <label for="name" class="required">Full Name</label>
                <input type="text" id="name"
                       value="${userData.name}"
                       required>
                <div class="error-message" id="nameError"></div>
              </div>

              <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" 
                       value="${userData.email}" 
                       readonly>
                <div class="field-hint">Contact administrator to update email address</div>
              </div>

              <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone"
                       value="${userData.phone}"
                       pattern="^(\+\d{1,3}[- ]?)?\d{10}$"
                       placeholder="10 digit phone number">
                <div class="field-hint">Enter 10 digits (e.g., 9876543210). Country code is optional.</div>
                <div class="error-message" id="phoneError"></div>
              </div>

              <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender">
                  <option value="">Prefer not to say</option>
                  <option value="male" ${userData.gender === 'male' ? 'selected' : ''}>Male</option>
                  <option value="female" ${userData.gender === 'female' ? 'selected' : ''}>Female</option>
                  <option value="other" ${userData.gender === 'other' ? 'selected' : ''}>Other</option>
                </select>
                <div class="field-hint">Optional</div>
              </div>

              <button type="submit" id="submitBtn" class="save-btn">Save Changes</button>
            </form>
          `;
          break;

        case 'addresses':
          // Load addresses UI into the content area (AJAX fragment)
          fetch('get_addresses_content.php')
            .then(response => response.text())
            .then(html => {
              const contentSection = document.querySelector('.content');
              // Insert HTML
              contentSection.innerHTML = html;

              // Execute any inline scripts included in the fragment
              // (Browsers don't run scripts when inserted via innerHTML)
              const temp = document.createElement('div');
              temp.innerHTML = html;
              const scripts = temp.querySelectorAll('script');
              scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                // copy attributes
                for (let i = 0; i < oldScript.attributes.length; i++) {
                  const attr = oldScript.attributes[i];
                  newScript.setAttribute(attr.name, attr.value);
                }
                newScript.text = oldScript.textContent;
                // append to content so it runs in page context
                contentSection.appendChild(newScript);
              });
            })
            .catch(err => {
              console.error('Error loading addresses content:', err);
              alert('Could not load addresses.');
            });
          break;

        case 'upload':
          // Load upload content into the content area (AJAX fragment)
          fetch('get_upload_content.php')
            .then(response => response.text())
            .then(html => {
              const contentSection = document.querySelector('.content');
              // Insert HTML
              contentSection.innerHTML = html;

              // Execute any inline scripts included in the fragment
              // (Browsers don't run scripts when inserted via innerHTML)
              const temp = document.createElement('div');
              temp.innerHTML = html;
              const scripts = temp.querySelectorAll('script');
              scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                // copy attributes
                for (let i = 0; i < oldScript.attributes.length; i++) {
                  const attr = oldScript.attributes[i];
                  newScript.setAttribute(attr.name, attr.value);
                }
                newScript.text = oldScript.textContent;
                // append to content so it runs in page context
                contentSection.appendChild(newScript);
              });
            })
            .catch(err => {
              console.error('Error loading upload content:', err);
              alert('Could not load upload form.');
            });
          break;

        case 'products':
          // First fetch the user's team information
          fetch('get_team_info.php')
            .then(response => response.json())
            .then(data => {
              if (data.success && data.team_id) {
                // Fetch products content
                fetch(`get_products_content.php?team_id=${data.team_id}`)
                  .then(response => response.text())
                  .then(html => {
                    const contentSection = document.querySelector('.content');
                    contentSection.innerHTML = html;
                    
                    // Add necessary styles
                    const style = document.createElement('style');
                    style.textContent = `
                      .products-section {
                        background: white;
                        border-radius: 8px;
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
                      .product-info { flex: 1; }
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
                      .status-approved { color: #28a745; font-weight: bold; }
                      .status-pending { color: #ffc107; font-weight: bold; }
                      .status-rejected { color: #dc3545; font-weight: bold; }
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
                      .edit-form textarea { height: 80px; }
                      .btn {
                        padding: 8px 16px;
                        border: none;
                        border-radius: 4px;
                        background: #007bff;
                        color: white;
                        cursor: pointer;
                      }
                      .btn:hover { background: #0056b3; }
                      .delete-btn {
                        background: #dc3545;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                      }
                      .delete-btn:hover { background: #c82333; }
                    `;
                    document.head.appendChild(style);
                  });
              } else {
                const contentSection = document.querySelector('.content');
                contentSection.innerHTML = `
                  <div class="alert alert-warning">
                    <h3>Team Required</h3>
                    <p>You must be a member of a team to manage products. Please join a team first.</p>
                  </div>
                `;
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert('Error fetching team information');
            });
          break;

        case 'orders':
          // Load orders into the content div via AJAX
          fetch('get_orders.php')
            .then(r => r.json())
            .then(data => {
              const contentSection = document.querySelector('.content');
              if (!data || !data.success) {
                contentSection.innerHTML = '<p>Could not load orders.</p>';
                return;
              }

              const orders = data.orders || [];
              if (orders.length === 0) {
                contentSection.innerHTML = '<h2>Your Orders</h2><p>No orders found.</p>';
                return;
              }

              let html = '<h2>Your Orders</h2>';
              html += '<div class="orders-list">';
              orders.forEach(o => {
                html += `
                  <div class="order-card" data-order-id="${o.id}" data-product-id="${o.product_id}">
                    <h3>Order #${o.order_id}</h3>
                    <p><strong>Date:</strong> ${o.order_date}</p>
                    <p><strong>Product:</strong> ${o.product_name}</p>
                    <p><strong>Quantity:</strong> ${o.quantity}</p>
                    <p><strong>Price:</strong> ₹${parseFloat(o.price).toFixed(2)}</p>
                    <p><strong>Total:</strong> ₹${parseFloat(o.total_amount).toFixed(2)}</p>
                    <p><strong>Status:</strong> ${o.status}</p>
                    ${o.can_rate ? `
                      <div class="rate-section">
                        <button class="btn rate-toggle">Rate this product</button>
                        <div class="rate-form" style="display:none; margin-top:10px">
                          <label>Rating:
                            <select class="rating-select">
                              <option value="1">1</option>
                              <option value="2">2</option>
                              <option value="3">3</option>
                              <option value="4">4</option>
                              <option value="5">5</option>
                            </select>
                          </label>
                          <br>
                          <textarea class="review-text" placeholder="Write a short review (optional)" rows="3" style="width:100%; margin-top:8px"></textarea>
                          <br>
                          <button class="btn submit-rating">Submit Rating</button>
                        </div>
                      </div>
                    ` : ''}
                  </div>
                `;
              });
              html += '</div>';
              contentSection.innerHTML = html;

              // Attach event handlers for rating
              document.querySelectorAll('.rate-toggle').forEach(btn => {
                btn.addEventListener('click', function() {
                  const parent = this.closest('.order-card');
                  const form = parent.querySelector('.rate-form');
                  form.style.display = form.style.display === 'none' ? 'block' : 'none';
                });
              });

              document.querySelectorAll('.submit-rating').forEach(btn => {
                btn.addEventListener('click', function() {
                  const parent = this.closest('.order-card');
                  const orderId = parent.getAttribute('data-order-id');
                  const productId = parent.getAttribute('data-product-id');
                  const rating = parent.querySelector('.rating-select').value;
                  const review = parent.querySelector('.review-text').value;

                  const formData = new URLSearchParams();
                  formData.append('order_id', orderId);
                  formData.append('product_id', productId);
                  formData.append('rating', rating);
                  formData.append('review', review);

                  // Disable form while submitting
                  const submitBtn = this;
                  const originalText = submitBtn.textContent;
                  submitBtn.disabled = true;
                  submitBtn.textContent = 'Submitting...';

                  fetch('submit_rating.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                  })
                  .then(r => r.json())
                  .then(resp => {
                    if (resp.success) {
                      const form = parent.querySelector('.rate-form');
                      form.innerHTML = '<p style="color: #28a745">Thank you for your rating!</p>';
                      // Reload orders list after a moment to update can_rate flags
                      setTimeout(() => loadContent('orders'), 1500);
                    } else {
                      submitBtn.disabled = false;
                      submitBtn.textContent = originalText;
                      alert('Error submitting rating: ' + (resp.message || 'Unknown error'));
                    }
                  })
                  .catch(err => {
                    console.error('Rating error:', err);
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    alert('Could not submit rating. Please try again.');
                  });
                });
              });
            })
            .catch(err => {
              console.error('Error fetching orders:', err);
              const contentSection = document.querySelector('.content');
              contentSection.innerHTML = `
                <div style="padding: 20px;">
                  <h2>Orders</h2>
                  <p style="color: #dc3545;">Error loading orders. Please try again.</p>
                  <button onclick="loadContent('orders')" class="btn" style="margin-top: 10px;">Retry</button>
                  <pre style="color: #666; margin-top: 10px; font-size: 12px;">${err.message || ''}</pre>
                </div>
              `;
            });
          break;
      }
    }

    // Event listeners for sidebar buttons
    document.addEventListener('DOMContentLoaded', function() {
      const buttons = document.querySelectorAll('.sidebar button');
      
      buttons.forEach((button, index) => {
        button.addEventListener('click', function() {
          // Remove active class from all buttons
          buttons.forEach(btn => btn.classList.remove('active'));
          // Add active class to clicked button
          this.classList.add('active');
          
          // Load appropriate content
          switch(index) {
            case 0: loadContent('personal'); break;
            case 1: loadContent('addresses'); break;
            case 2: loadContent('upload'); break;
            case 3: loadContent('products'); break;
            case 4: loadContent('orders'); break;
          }
        });
      });

      // Load personal information by default
      loadContent('personal');
    });

    let isSubmitting = false;

    // Handle form submission
    document.addEventListener('submit', function(e) {
      if (e.target.id === 'personalInfoForm') {
        e.preventDefault();

        if (isSubmitting) return; // Prevent multiple submissions

        // Reset error states
        document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
        document.getElementById('alertMessage').style.display = 'none';

        // Client-side validation
        let isValid = true;
        const name = document.getElementById('name').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const gender = document.getElementById('gender').value;

        // Phone validation (if provided)
        if (phone && !/^(\+\d{1,3}[- ]?)?\d{10}$/.test(phone)) {
          document.getElementById('phoneError').textContent = 'Please enter a valid 10 digit phone number. Country code is optional.';
          document.getElementById('phoneError').style.display = 'block';
          isValid = false;
        }

        if (!isValid) {
          showAlert('Please correct the errors above', 'error');
          return;
        }

        isSubmitting = true;

        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('gender', gender);
        formData.append('csrf_token', csrfToken);

        // Update UI immediately with optimistic update
        const optimisticData = {
          name: name,
          phone: phone,
          gender: gender,
          email: userData.email // Keep existing email
        };
        userData = {...userData, ...optimisticData};

        fetch('update_profile.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showAlert('Profile updated successfully!', 'success');
            // Data is already updated optimistically
          } else {
            // Revert changes on error
            userData = {...userData, ...data.userData};
            const message = data.errors ? data.errors.join('\\n') : data.message;
            showAlert(message, 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showAlert('An error occurred while updating the profile', 'error');
        })
        .finally(() => {
          isSubmitting = false;
        });
      }
    });

    function showAlert(message, type) {
      const alertDiv = document.getElementById('alertMessage');
      alertDiv.className = 'alert ' + type;
      alertDiv.textContent = message;
      alertDiv.style.display = 'block';
      
      // Scroll to alert
      alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
      
      // Auto-hide success messages after 5 seconds
      if (type === 'success') {
        setTimeout(() => {
          alertDiv.style.display = 'none';
        }, 5000);
      }
    }
  </script>
</head>

<body>
  <header class="profile-header">
    <div class="header-left">
      <a href="Shop.html">
        <img src="images/weblogo.jpg" alt="Logo" class="logo-image" />
      </a>
    </div>
    <div class="header-right">
      <div class="profile-avatar-wrapper">
        <img src="<?php echo htmlspecialchars($userData['profile_photo']); ?>" alt="User Profile Picture" class="profile-avatar" />
      </div>
      <button class="logout-btn" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='logout.php';">Logout</button>
    </div>
  </header>

  <div class="main-container">
    <aside class="sidebar">
      <h2>ACCOUNT SETTINGS</h2>
      <nav>
        <button class="active">Personal Information</button>
        <button>Manage Addresses</button>
        <button>Upload Product</button>
        <button>Manage Products</button>
        <button>Orders</button>
      </nav>
    </aside>

    <section class="content">
      <!-- Content will be dynamically loaded by JavaScript -->
    </section>
  </div>
</body>
</html>
