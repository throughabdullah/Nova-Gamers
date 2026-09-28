<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth_layout.php';
no_cache();
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$reset = preg_match('/^[a-f0-9]{64}$/', $token)
    ? row('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()', [hash('sha256', $token)]) : null;
$errors = [];
if ($reset && is_post()) {
    csrf_check();
    $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['confirm_password'] ?? '');
    if (strlen($pw) < 6) $errors['password'] = 'Password must be at least 6 characters.';
    if ($pw !== $pw2) $errors['confirm_password'] = 'Passwords do not match.';
    if (!$errors) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $reset['user_id']]);
        q('UPDATE password_resets SET used = 1 WHERE user_id = ?', [$reset['user_id']]);
        flash('Password updated. You can now log in with your new password.', 'success');
        redirect('login.php');
    }
}
simple_auth_start('Reset password', 'Choose a new password', 'Pick something you have not used before.');
if (!$reset): ?>
  <div class="alert alert-danger">This reset link is invalid or has expired.</div>
  <a class="btn btn-brand" href="<?= url('forgot-password.php') ?>">Request a new link</a>
<?php else: ?>
<form method="post" novalidate>
  <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
  <?php password_input('password', 'New password', 'new-password', $errors); password_input('confirm_password', 'Confirm new password', 'new-password', $errors); ?>
  <button class="btn btn-brand" type="submit">Update password</button>
</form>
<?php endif;
simple_auth_end(); ?>
