<?php
$page_title = "My Profile";
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$errors = [];
$success = false;

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Check if email already exists (excluding current user)
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already in use";
        }
    }
    
    // Password change validation
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    if (empty($errors)) {
        if (!empty($new_password)) {
            // Update with password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $hashed_password, $user_id);
        } else {
            // Update without password
            $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = true;
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update profile";
        }
    }
}

closeDBConnection($conn);
?>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-person-circle display-1 text-primary"></i>
                <h5 class="mt-3"><?php echo $user['full_name']; ?></h5>
                <p class="text-muted mb-0">@<?php echo $user['username']; ?></p>
                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?> mt-2">
                    <?php echo ucfirst($user['role']); ?>
                </span>
            </div>
            <div class="list-group list-group-flush">
                <a href="profile.php" class="list-group-item list-group-item-action active">
                    <i class="bi bi-person"></i> Profile
                </a>
                <a href="orders.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-bag"></i> My Orders
                </a>
                <a href="cart.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-cart"></i> Shopping Cart
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-gear"></i> Edit Profile</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success">
                    Profile updated successfully!
                </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <h6 class="mb-3">Account Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?php echo $user['full_name']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo $user['email']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" 
                                   value="<?php echo $user['phone']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3">Change Password (Optional)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password">
                            <small class="text-muted">Min. 6 characters</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h6>Account Statistics</h6>
                <div class="row text-center mt-3">
                    <div class="col-md-4">
                        <i class="bi bi-calendar display-6 text-primary"></i>
                        <p class="mb-0 mt-2"><strong>Member Since</strong></p>
                        <p><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-bag-check display-6 text-success"></i>
                        <p class="mb-0 mt-2"><strong>Total Orders</strong></p>
                        <p>
                            <?php
                            $conn = getDBConnection();
                            $order_count = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id")->fetch_assoc();
                            echo $order_count['total'];
                            closeDBConnection($conn);
                            ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-star display-6 text-warning"></i>
                        <p class="mb-0 mt-2"><strong>Status</strong></p>
                        <p><?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>