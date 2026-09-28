<?php
require __DIR__ . '/includes/bootstrap.php';
$groups = [
    'Public' => [['Home', 'index.php'], ['Login', 'login.php'], ['Register', 'register.php'], ['Forgot password', 'forgot-password.php'], ['Sitemap', 'sitemap.php']],
    'Student area (login required)' => [['Dashboard', 'dashboard.php'], ['Transactions', 'transactions.php'], ['Add income', 'transaction-form.php?type=income'], ['Add expense', 'transaction-form.php?type=expense'], ['Categories', 'categories.php'], ['Budgets', 'budgets.php'], ['Reports', 'reports.php'], ['Saving tips', 'tips.php'], ['Profile', 'profile.php'], ['Settings', 'settings.php']],
    'Administrator (admin role required)' => [['Admin login', 'admin/login.php'], ['Admin dashboard', 'admin/index.php'], ['Users', 'admin/users.php'], ['Default categories', 'admin/categories.php'], ['Announcements', 'admin/announcements.php'], ['Tip templates', 'admin/tips.php'], ['Statistics', 'admin/statistics.php']],
];
public_start('Sitemap');
?>
<div class="container py-5" style="max-width:900px">
  <a class="brand mb-4" href="<?= url('index.php') ?>"><span class="brand-coin">C</span><span>Campus<b>Coin</b></span></a>
  <h1 class="mb-1">Sitemap</h1><p class="text-secondary mb-4">Every page in CampusCoin. Protected pages redirect to login when you are signed out.</p>
  <div class="row g-4"><?php foreach ($groups as $g => $links): ?>
    <div class="col-md-6"><div class="cc-card static"><h5 class="mb-3"><?= e($g) ?></h5>
      <ul class="list-unstyled mb-0"><?php foreach ($links as [$l, $h]): ?><li class="py-1"><a href="<?= url($h) ?>"><i class="bi bi-chevron-right small"></i> <?= e($l) ?></a></li><?php endforeach; ?></ul></div></div>
  <?php endforeach; ?></div>
</div>
<?php public_end(); ?>
