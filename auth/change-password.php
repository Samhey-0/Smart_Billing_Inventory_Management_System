<?php
/**
 * Change Password Page
 * @package InspireShoes
 * @version 1.2
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$page_title = 'Change Password';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($new) || empty($confirm)) {
            $error = 'All fields are required.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $new)) {
            $error = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $new)) {
            $error = 'New password must contain at least one number.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $new_hash = password_hash($new, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_hash, $_SESSION['user_id']]);
                setFlashMessage('success', 'Password changed successfully!');
                header('Location: ' . BASE_URL . '/index.php');
                exit();
            }
        }
    }
}

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-key"></i> Change Password</h1>
    <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<div class="card" style="max-width:500px;">
    <div class="card-header"><h3>Update Your Password</h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">

            <div class="form-group">
                <label>Current Password *</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password *</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required>
                <small class="text-muted">Min 8 characters with uppercase and a number</small>
            </div>
            <div class="form-group">
                <label>Confirm New Password *</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                <small id="matchMsg" style="display:none;"></small>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="fas fa-save"></i> Change Password
            </button>
        </form>
    </div>
</div>

<script>
// Live match check
document.getElementById('confirm_password').addEventListener('input', function() {
    const np = document.getElementById('new_password').value;
    const msg = document.getElementById('matchMsg');
    msg.style.display = 'block';
    if (this.value === np) {
        msg.textContent = '✓ Passwords match';
        msg.style.color = '#27ae60';
    } else {
        msg.textContent = '✗ Passwords do not match';
        msg.style.color = '#e74c3c';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
