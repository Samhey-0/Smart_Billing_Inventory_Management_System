<?php
/**
 * Staff Management Page - Admin Only
 * @package InspireShoes
 * @version 1.2
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin only.');
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$page_title = 'Staff Management';
$error = '';

// Handle create staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
        $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check duplicate username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, 'staff', 'active')");
                if ($stmt->execute([$username, $email, $hash])) {
                    setFlashMessage('success', 'Staff account created for ' . $username);
                    header('Location: ' . BASE_URL . '/admin/staff.php');
                    exit();
                } else {
                    $error = 'Failed to create staff account.';
                }
            }
        }
    }
}

// Handle toggle status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $uid = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($uid && $uid !== (int)$_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ? AND role = 'staff'");
        $stmt->execute([$uid]);
        setFlashMessage('success', 'Staff status updated.');
    }
    header('Location: ' . BASE_URL . '/admin/staff.php');
    exit();
}

// Handle delete staff
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $uid = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($uid && $uid !== (int)$_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$uid]);
        setFlashMessage('success', 'Staff account deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/staff.php');
    exit();
}

// Get all staff
$staff = $pdo->query("SELECT * FROM users WHERE role = 'staff' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users-cog"></i> Staff Management</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">

    <!-- Create Staff Form -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-plus"></i> Add Staff Account</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="e.g. staff1">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required placeholder="staff@shop.com">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="Min 6 characters">
                </div>
                <div style="background:var(--light-gray); padding:12px; border-radius:8px; margin-bottom:15px; font-size:13px;">
                    <strong>Staff can:</strong> Create invoices, view products, view customers<br>
                    <strong>Staff cannot:</strong> Delete anything, view reports, manage stock, manage other users
                </div>
                <button type="submit" name="create_staff" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-user-plus"></i> Create Staff Account
                </button>
            </form>
        </div>
    </div>

    <!-- Staff List -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-users"></i> Staff Accounts</h3></div>
        <div class="card-body">
            <?php if (empty($staff)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No staff accounts yet. Create one to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $s): ?>
                                <tr>
                                    <td><strong><?php echo h($s['username']); ?></strong></td>
                                    <td><?php echo h($s['email']); ?></td>
                                    <td>
                                        <?php if ($s['status'] === 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($s['created_at']); ?></td>
                                    <td style="display:flex; gap:5px;">
                                        <a href="?toggle=1&id=<?php echo h((string)$s['id']); ?>"
                                           class="btn btn-sm <?php echo $s['status']==='active' ? 'btn-warning' : 'btn-success'; ?>"
                                           onclick="return confirm('Toggle this staff account status?')">
                                            <i class="fas fa-<?php echo $s['status']==='active' ? 'ban' : 'check'; ?>"></i>
                                            <?php echo $s['status']==='active' ? 'Disable' : 'Enable'; ?>
                                        </a>
                                        <a href="?delete=1&id=<?php echo h((string)$s['id']); ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this staff account permanently?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
