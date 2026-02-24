<?php
/**
 * Global Footer File
 * @package InspireShoes
 * @version 1.0
 */
?>

<?php if (isset($_SESSION['user_id'])): ?>
        </div><!-- End content-wrapper -->

        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> Inspire Shoes. All rights reserved.</p>
            <p>Billing System v1.0</p>
        </footer>
    </main><!-- End main-content -->
<?php endif; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- ✅ FIXED: Always use JS_URL constant, never relative paths -->
<script src="<?php echo JS_URL; ?>/app.js"></script>

<script>
    // Mobile menu toggle
    document.getElementById('menuToggle')?.addEventListener('click', function () {
        document.querySelector('.sidebar')?.classList.toggle('active');
    });

    // Auto-hide flash messages after 5 seconds
    setTimeout(function () {
        document.querySelectorAll('.flash-message').forEach(function (msg) {
            msg.style.transition = 'opacity 0.5s';
            msg.style.opacity = '0';
            setTimeout(function () { msg.remove(); }, 500);
        });
    }, 5000);
</script>
</body>
</html>
