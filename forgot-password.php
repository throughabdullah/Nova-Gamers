<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth_layout.php';
no_cache();
$errors = []; $sent = false; $link = null; $email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $u = row("SELECT id, name FROM users WHERE email = ? AND status = 'active'", [$email]);
        if ($u) {
            $token = bin2hex(random_bytes(32));
            q('UPDATE password_resets SET used = 1 WHERE user_id = ?', [$u['id']]);
            q('INSERT INTO password_resets(user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))', [$u['id'], hash('sha256', $token)]);
            $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
            $link = $scheme . '://' . $_SERVER['HTTP_HOST'] . url('reset-password.php?token=' . $token);
            // A real deployment would email $link. XAMPP has no mail server, so we log it and (in demo mode) show it.
            @file_put_contents(__DIR__ . '/storage/mail.log', '[' . date('c') . "] Password reset for {$email}: {$link}\n", FILE_APPEND);
            if (!MAIL_DEMO) $link = null;
        }
        $sent = true;
    }
}
simple_auth_start('Forgot password', 'Forgot your password?', 'Enter your email and we will create a secure reset link that expires in 1 hour.');
if ($sent): ?>
  <div class="alert alert-success">If an account exists for <b><?= e($email) ?></b>, a reset link has been generated.</div>
  <?php if ($link): ?>
    <div class="cc-card static p-3 mb-2"><div class="small text-secondary mb-2"><i class="bi bi-info-circle"></i> Demo mode – no mail server is configured, so here is your link:</div>
      <a class="btn btn-brand btn-sm" href="<?= e($link) ?>">Reset my password</a></div>
  <?php endif; ?>
<?php else: ?>
<form method="post" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label" for="email">Email</label>
    <div class="input-icon"><i class="bi bi-envelope lead"></i><input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($email) ?>" required></div><?= field_err($errors, 'email') ?></div>
  <button class="btn btn-brand" type="submit">Send reset link</button>
</form>
<?php endif;
simple_auth_end(); ?>
