<?php
require_once 'config.php';
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    header("Location: UserLogin.html");
    exit();
}

if (!isset($_GET['product_id'])) {
    header("Location: Shop.html");
    exit();
}

$product_id = $_GET['product_id'];

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

// Get product details
  $stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.product_description, p.quantity, p.product_price, 
    p.product_image, p.status, p.upload_date, 
    u.name as uploader_name, u.email as uploader_email, t.team_name 
    FROM products p 
    JOIN users u ON p.uploader_id = u.id 
    LEFT JOIN teams t ON p.team_id = t.id 
    WHERE p.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: Shop.html");
    exit();
}

$product = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Product Details - Rawspark</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* ---------- RESET ---------- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f5f6fb;
      color: #333;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* ---------- HEADER ---------- */
    .header {
      background: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .logo img {
      height: 48px;
      width: 48px;
      border-radius: 10px;
      object-fit: cover;
    }

    .search-container {
      position: relative;
      display: flex;
      align-items: center;
    }

    .search-container input {
      padding: 8px 30px 8px 30px;
      border: 1px solid #ccc;
      border-radius: 20px;
      outline: none;
      width: 200px;
      transition: 0.3s;
    }

    .search-container input:focus {
      border-color: #4b6cb7;
      box-shadow: 0 0 4px rgba(75,108,183,0.3);
    }

    .search-icon {
      position: absolute;
      left: 10px;
      font-size: 16px;
      color: #555;
    }

    .clear-search {
      position: absolute;
      right: 8px;
      background: none;
      border: none;
      color: #888;
      cursor: pointer;
      font-size: 16px;
      display: none;
    }

    .header-nav {
      display: flex;
      align-items: center;
      gap: 25px;
    }

    .header-nav a {
      font-weight: 500;
      color: #333;
      transition: 0.3s;
    }

    .header-nav a:hover {
      color: #4b6cb7;
    }

    .profile-icon svg {
      width: 30px;
      height: 30px;
      fill: #4b6cb7;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logout-btn {
      background: linear-gradient(135deg, #4b6cb7, #182848);
      color: #fff;
      padding: 6px 14px;
      border-radius: 6px;
      font-size: 14px;
    }

    .logout-btn:hover {
      opacity: 0.9;
    }

    /* ---------- MAIN PRODUCT SECTION ---------- */
    .main-content {
      padding: 40px 10%;
    }

    .product-detail-container {
      display: flex;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.1);
      overflow: hidden;
      flex-wrap: wrap;
    }

    .product-image-container {
      flex: 1 1 45%;
      background: #f8f8f8;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 350px;
      padding: 20px;
      border-radius: 8px;
      position: relative;
      overflow: hidden;
    }

    .product-detail-image {
      max-width: 100%;
      max-height: 400px;
      height: auto;
      object-fit: contain;
      border-radius: 4px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }

    .product-detail-image:hover {
      transform: scale(1.02);
    }

    .product-info {
      flex: 1 1 55%;
      padding: 30px;
    }

    .product-title {
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 10px;
      color: #182848;
    }

    .product-price {
      font-size: 22px;
      color: #4b6cb7;
      margin-bottom: 15px;
    }

    .product-description {
      font-size: 15px;
      line-height: 1.6;
      color: #555;
      margin-bottom: 20px;
    }

    .quantity-section {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
    }

    .quantity-section label {
      font-weight: 500;
    }

    .quantity-section input {
      width: 70px;
      padding: 5px 8px;
      text-align: center;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .total-price-section {
      margin-bottom: 15px;
      font-size: 16px;
      color: #333;
    }

    .product-meta p {
      font-size: 14px;
      color: #666;
      margin-bottom: 5px;
    }

    .buy-now-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      background: linear-gradient(135deg, #4b6cb7, #182848);
      color: white;
      cursor: pointer;
      transition: 0.3s;
    }

    .buy-now-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 950px) {
      .product-detail-container {
        flex-direction: column;
      }

      .product-image-container,
      .product-info {
        flex: 1 1 100%;
      }
    }

    @media (max-width: 600px) {
      .product-info {
        padding: 20px;
      }

      .product-title {
        font-size: 22px;
      }

      .product-price {
        font-size: 18px;
      }
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="header-left">
      <div class="logo">
        <img src="images/weblogo.jpg" alt="Logo">
      </div>
      <div class="search-container">
        <span class="search-icon">🔍</span>
        <input type="text" placeholder="Search products...">
        <button class="clear-search">✕</button>
      </div>
    </div>
    <nav class="header-nav">
      <a href="Shop.html">Shop</a>
      <a href="#">About</a>
      <a href="#">Contact</a>
      <a href="Profile.php"><div class="profile-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4zm0 2c-2.67 0-8 
          1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
      </div></a>
      <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </nav>
  </header>

  <main class="main-content">
    <section class="product-detail-section">
      <div class="product-detail-container">
        <div class="product-image-container">
          <img 
            src="<?php echo $product['product_image'] ? 'get_product_image.php?id=' . $product['product_id'] : 'https://via.placeholder.com/400x300?text=No+Image'; ?>" 
            alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
            class="product-detail-image"
            onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=No+Image';"
          >
        </div>

        <div class="product-info">
          <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
          <p class="product-price">₹<?php echo htmlspecialchars($product['product_price']); ?></p>
          <p class="product-description">
            <?php echo htmlspecialchars($product['product_description']); ?>
          </p>

          <div class="quantity-section">
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['quantity']); ?>">
          </div>

          <div class="total-price-section">
            <p><strong>Total Price:</strong> ₹<span id="total-price"><?php echo htmlspecialchars($product['product_price']); ?></span></p>
          </div>

          <div class="product-meta">
            <p><strong>Uploaded by:</strong> <?php echo htmlspecialchars($product['uploader_email']); ?></p>
            <p><strong>Team:</strong> <?php echo htmlspecialchars($product['team_name'] ?? 'N/A'); ?></p>
            <p><strong>Upload Date:</strong> <?php echo htmlspecialchars($product['upload_date']); ?></p>
          </div>

          <button class="buy-now-btn">Buy Now</button>
        </div>
      </div>
    </section>
  </main>

  <script>
    (function(){
      const quantityInput = document.getElementById('quantity');
      const totalPriceEl = document.getElementById('total-price');
      const buyBtn = document.querySelector('.buy-now-btn');
      const price = parseFloat(<?php echo json_encode($product['product_price']); ?>);
      const maxQty = parseInt(<?php echo json_encode($product['quantity']); ?>, 10) || 0;
      const productId = <?php echo json_encode($product['product_id']); ?>;

      function updateTotal() {
        let qty = parseInt(quantityInput.value, 10) || 0;
        if (qty < 1) qty = 1;
        if (maxQty && qty > maxQty) qty = maxQty;
        quantityInput.value = qty;
        const total = (qty * price).toFixed(2);
        totalPriceEl.textContent = total;
        buyBtn.disabled = qty < 1 || (maxQty && qty > maxQty);
      }

      quantityInput.addEventListener('input', updateTotal);
      updateTotal();

      buyBtn.addEventListener('click', function() {
        const quantity = parseInt(quantityInput.value, 10) || 0;
        if (quantity < 1) {
          alert('Please select a valid quantity.');
          return;
        }
        if (maxQty && quantity > maxQty) {
          alert('Selected quantity exceeds available stock.');
          return;
        }

        buyBtn.disabled = true;
        const originalText = buyBtn.textContent;
        buyBtn.textContent = 'Placing order...';

        fetch('process_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            product_id: productId,
            quantity: quantity
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Order placed successfully!');
            window.location.href = 'Profile.php?tab=orders';
          } else {
            alert('Error: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while placing the order.');
        })
        .finally(() => {
          buyBtn.disabled = false;
          buyBtn.textContent = originalText;
        });
      });
    })();
  </script>
</body>
</html>
