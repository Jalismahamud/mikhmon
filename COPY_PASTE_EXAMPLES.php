<?php
/*
 * COPY-PASTE CODE EXAMPLES
 * Ready-to-use code snippets following existing Mikhmon patterns
 * 
 * Simply copy and paste these into your modules
 */

// ============================================
// EXAMPLE 1: ADMIN LOGIN PAGE
// ============================================
// File: include/login_mysql.php
// Copy this to replace hardcoded login check

?>
<?php
session_start();
require_once('./include/db_config.php');
require_once('./include/auth.php');

$error = '';

if (isset($_POST['login'])) {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    
    // Use MySQL authentication
    $auth = getAuth();
    $login_result = $auth->login($user, $pass);
    
    if ($login_result) {
        // Login successful - set legacy session for backward compatibility
        $_SESSION["mikhmon"] = $user;
        echo "<script>window.location='./admin.php?id=sessions'</script>";
        exit;
    } else {
        $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger">';
        $error .= '<i class="fa fa-ban"></i> Alert!<br>';
        $error .= 'Invalid username or password.';
        $error .= '</div>';
    }
}
?>

<div style="padding-top: 5%;" class="login-box">
  <div class="card">
    <div class="card-header">
      <h3><?= $_please_login ?></h3>
    </div>
    <div class="card-body">
      <div class="text-center pd-5">
        <img src="img/favicon.png" alt="GOLDEN WIFI Logo">
      </div>
      <div class="text-center">
        <span style="font-size: 25px; margin: 10px;">GOLDEN WIFI</span>
      </div>
      <center>
        <form autocomplete="off" action="" method="post">
          <table class="table" style="width:90%">
            <tr>
              <td class="align-middle text-center">
                <input style="width: 100%; height: 35px; font-size: 16px;" 
                       class="form-control" type="text" name="user" 
                       placeholder="Username" required="1" autofocus>
              </td>
            </tr>
            <tr>
              <td class="align-middle text-center">
                <input style="width: 100%; height: 35px; font-size: 16px;" 
                       class="form-control" type="password" name="pass" 
                       placeholder="Password" required="1">
              </td>
            </tr>
            <tr>
              <td class="align-middle text-center">
                <input style="width: 100%; margin-top:20px; height: 35px; font-weight: bold; font-size: 17px;" 
                       class="btn-login bg-primary pointer" type="submit" 
                       name="login" value="Login">
              </td>
            </tr>
            <tr>
              <td class="align-middle text-center">
                <?= $error; ?>
              </td>
            </tr>
          </table>
        </form>
      </center>
    </div>
  </div>
</div>

<?php
// ============================================
// EXAMPLE 2: CREATE ADMIN FORM
// ============================================
// File: settings/admin/create.php

?>

<div class="col-md-12">
  <div class="card">
    <div class="card-header">
      <h3>Create New Admin User</h3>
    </div>
    <div class="card-body">
      
      <?php
      if ($_SESSION['mikhmon_user_role'] !== 'superadmin') {
          echo '<div class="bg-danger" style="padding: 10px; margin: 10px 0;">
                  Only superadmins can create users
                </div>';
      } else {
      ?>
      
      <form method="post" action="">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Username:</label>
              <input type="text" name="username" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Email:</label>
              <input type="email" name="email" class="form-control" required>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Password:</label>
              <input type="password" name="password" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Full Name:</label>
              <input type="text" name="full_name" class="form-control" required>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Role:</label>
              <select name="role" class="form-control">
                <option value="operator">Operator (manage users & vouchers)</option>
                <option value="admin">Admin (full access)</option>
                <option value="viewer">Viewer (reports only)</option>
              </select>
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn bg-primary">Create Admin</button>
        <button type="reset" class="btn bg-secondary">Clear</button>
      </form>
      
      <?php
      // Process form
      if ($_POST && isset($_POST['username'])) {
          require_once('./include/db_config.php');
          require_once('./include/auth.php');
          
          $auth = getAuth();
          $admin_mgr = $auth->getAdminUserManager();
          
          $result = $admin_mgr->create(array(
              'username' => $_POST['username'],
              'password' => $_POST['password'],
              'email' => $_POST['email'],
              'full_name' => $_POST['full_name'],
              'role' => $_POST['role'] ?? 'operator'
          ));
          
          if ($result) {
              echo '<div class="bg-success" style="padding: 10px; margin: 10px 0;">
                      ✓ Admin user created successfully (ID: ' . $result . ')
                    </div>';
          } else {
              echo '<div class="bg-danger" style="padding: 10px; margin: 10px 0;">
                      ✗ Failed to create admin user
                    </div>';
          }
      }
      ?>
      
      <?php } ?>
      
    </div>
  </div>
</div>

<?php
// ============================================
// EXAMPLE 3: LIST ROUTERS WITH PAGINATION
// ============================================
// File: settings/routers/list.php

?>

<div class="col-md-12">
  <div class="card">
    <div class="card-header">
      <h3>My Routers</h3>
    </div>
    <div class="card-body">
      
      <?php
      require_once('./lib/Router.class.php');
      
      $admin_id = $_SESSION['mikhmon_user_id'];
      $router_mgr = new Router($db, $admin_id);
      
      // Pagination
      $page = $_GET['page'] ?? 1;
      $per_page = 10;
      $offset = ($page - 1) * $per_page;
      
      // Get routers
      $routers = $router_mgr->getAll($per_page, $offset, ['only_active' => true]);
      $total = $router_mgr->getTotalCount(true);
      $pages = ceil($total / $per_page);
      ?>
      
      <div class="overflow">
        <table class="table">
          <thead>
            <tr>
              <th>Router Name</th>
              <th>IP Address</th>
              <th>Hotspot Name</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($routers): ?>
              <?php foreach ($routers as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['name']); ?></td>
                  <td><?= htmlspecialchars($r['ip_address']); ?></td>
                  <td><?= htmlspecialchars($r['hotspot_name']); ?></td>
                  <td>
                    <?php
                    if ($r['is_active']) {
                        echo '<span class="badge bg-success">Active</span>';
                    } else {
                        echo '<span class="badge bg-danger">Inactive</span>';
                    }
                    ?>
                  </td>
                  <td>
                    <a href="?router_id=<?= $r['id']; ?>&action=edit" class="btn btn-sm bg-primary">
                      <i class="fa fa-edit"></i> Edit
                    </a>
                    <a href="?router_id=<?= $r['id']; ?>&action=delete" 
                       onclick="return confirm('Disable this router?')" 
                       class="btn btn-sm bg-danger">
                      <i class="fa fa-trash"></i> Disable
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center">No routers found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <nav>
        <ul class="pagination">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="<?= $i == $page ? 'active' : ''; ?>">
              <a href="?page=<?= $i; ?>"><?= $i; ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>
      
    </div>
  </div>
</div>

<?php
// ============================================
// EXAMPLE 4: CREATE ROUTER FORM
// ============================================
// File: settings/routers/create.php

?>

<div class="col-md-8">
  <div class="card">
    <div class="card-header">
      <h3>Add New Router</h3>
    </div>
    <div class="card-body">
      
      <form method="post" action="">
        <div class="form-group">
          <label>Router Name:</label>
          <input type="text" name="router_name" class="form-control" 
                 placeholder="e.g., Main Router" required>
        </div>
        
        <div class="form-group">
          <label>Description:</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Router IP Address:</label>
              <input type="text" name="ip_address" class="form-control"
                     placeholder="192.168.1.1" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>API Port:</label>
              <input type="number" name="api_port" class="form-control"
                     value="8728" required>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>API Username:</label>
              <input type="text" name="api_username" class="form-control"
                     value="admin" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>API Password:</label>
              <input type="password" name="api_password" class="form-control" required>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label>Hotspot Name:</label>
          <input type="text" name="hotspot_name" class="form-control" required>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Interface Name:</label>
              <input type="text" name="interface_name" class="form-control"
                     value="ether2">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Currency:</label>
              <select name="currency" class="form-control">
                <option value="USD">USD ($)</option>
                <option value="BDT">BDT (৳)</option>
                <option value="EUR">EUR (€)</option>
                <option value="INR">INR (₹)</option>
              </select>
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn bg-primary">Add Router</button>
        <button type="reset" class="btn bg-secondary">Clear</button>
      </form>
      
      <?php
      if ($_POST && isset($_POST['router_name'])) {
          require_once('./lib/Router.class.php');
          
          $admin_id = $_SESSION['mikhmon_user_id'];
          $router_mgr = new Router($db, $admin_id);
          
          // Helper function to encrypt password  
          $encrypted_pass = base64_encode($_POST['api_password']);
          
          $result = $router_mgr->create(array(
              'name' => $_POST['router_name'],
              'description' => $_POST['description'] ?? '',
              'ip_address' => $_POST['ip_address'],
              'api_port' => $_POST['api_port'] ?? 8728,
              'api_username' => $_POST['api_username'],
              'api_password_encrypted' => $encrypted_pass,
              'hotspot_name' => $_POST['hotspot_name'],
              'interface_name' => $_POST['interface_name'] ?? 'ether2',
              'currency' => $_POST['currency'] ?? 'USD'
          ));
          
          if ($result) {
              echo '<div class="bg-success" style="padding: 10px; margin: 10px 0;">
                      ✓ Router added successfully!
                    </div>';
          } else {
              echo '<div class="bg-danger" style="padding: 10px; margin: 10px 0;">
                      ✗ Failed to add router
                    </div>';
          }
      }
      ?>
      
    </div>
  </div>
</div>

<?php
// ============================================
// EXAMPLE 5: REQUIRE AUTHENTICATION
// ============================================
// Add this to the top of any protected page

require_once('./include/db_config.php');
require_once('./include/auth.php');

$auth = getAuth();

// Check if user is authenticated
if (!$auth->isAuthenticated()) {
    header("Location: ./admin.php?id=login");
    exit;
}

// Optionally require specific permission
if (!$auth->hasPermission('manage_routers')) {
    header("HTTP/1.0 403 Forbidden");
    echo "You do not have permission to access this page.";
    exit;
}

// Get current user
$user = $auth->getCurrentUser();
echo "Welcome, " . $user['full_name'];

?>

<?php
// ============================================
// EXAMPLE 6: LOG USER ACTION
// ============================================

$db = new Database();
$query = "INSERT INTO user_logs 
          (router_id, admin_id, username, action, action_type, details, performed_by, timestamp)
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$db->execute($query, array(
    $router_id,
    $_SESSION['mikhmon_user_id'],
    'testuser',
    'create',  // The action
    'create',  // action_type
    'User was created with profile XYZ',
    $_SESSION['mikhmon_user_id']  // who performed it
));

?>

<?php
// ============================================
// EXAMPLE 7: RECORD TRANSACTION
// ============================================

$query = "INSERT INTO transaction_history 
          (router_id, admin_id, transaction_type, reference_id, description, 
           amount, currency, payment_method, payment_status, customer_name, created_by)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$db->execute($query, array(
    $router_id,
    $_SESSION['mikhmon_user_id'],
    'voucher_sale',
    'VOUCHER_CODE_123',
    'Daily plan voucher sold',
    1.00,
    'USD',
    'Cash',
    'completed',
    'Customer Name',
    $_SESSION['mikhmon_user_id']
));

?>

<?php
// ============================================
// EXAMPLE 8: CHECK USER PERMISSION
// ============================================

require_once('./include/auth.php');
$auth = getAuth();
$user_id = $auth->getUserId();

if ($auth->hasPermission('manage_routers')) {
    echo "User can manage routers";
} else {
    echo "User cannot manage routers";
}

if ($auth->hasPermission('manage_admins')) {
    echo "User can manage admins (superadmin only)";
}

// Display user info
$user = $auth->getCurrentUser();
echo "User: " . $user['username'];
echo "Role: " . $user['role'];
echo "Email: " . $user['email'];

?>

<?php
// ============================================
// EXAMPLE 9: BACKUP REFERENCE
// ============================================
// To backup database from command line:
/*
mysqldump -u mikhmon_user -p mikhmon_db > backup_2024-04-08.sql
Enter password: mikhmon_secure_pass_123
*/

// To restore:
/*
mysql -u mikhmon_user -p mikhmon_db < backup_2024-04-08.sql
Enter password: mikhmon_secure_pass_123
*/

?>

