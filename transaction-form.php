<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login(); $uid = (int)$u['id'];

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'expense';
$txn = null;
if ($id) {
    $txn = row('SELECT * FROM transactions WHERE id = ? AND user_id = ?', [$id, $uid]);   // ownership check
    if (!$txn) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
    $type = $txn['type'];
}
if (!in_array($type, ['income', 'expense'], true)) $type = 'expense';
$cats = user_categories($uid, $type);
$catIds = array_map('intval', array_column($cats, 'id'));

$f = ['amount' => $txn['amount'] ?? '', 'txn_date' => $txn['txn_date'] ?? date('Y-m-d'), 'category_id' => $txn['category_id'] ?? '', 'description' => $txn['description'] ?? '',
      'is_recurring' => (int)($txn['is_recurring'] ?? 0), 'recurring_interval' => $txn['recurring_interval'] ?? 'monthly'];
$errors = []; $dup = false;

if (is_post()) {
    csrf_check();
    $f['amount'] = post('amount'); $f['txn_date'] = post('txn_date'); $f['category_id'] = (int)post('category_id'); $f['description'] = post('description');
    $f['is_recurring'] = isset($_POST['is_recurring']) ? 1 : 0; $f['recurring_interval'] = post('recurring_interval', 'monthly');
    if (!is_numeric($f['amount']) || (float)$f['amount'] <= 0 || (float)$f['amount'] > 99999999) $errors['amount'] = 'Enter an amount greater than 0.';
    if (!valid_date($f['txn_date'])) $errors['txn_date'] = 'Choose a valid date.';
    elseif ($f['txn_date'] > date('Y-m-d', strtotime('+1 year'))) $errors['txn_date'] = 'Date is too far in the future.';
    if (!in_array($f['category_id'], $catIds, true)) $errors['category_id'] = 'Choose a valid ' . $type . ' category.';   // blocks wrong-type / other users' categories
    if (mb_strlen($f['description']) > 255) $errors['description'] = 'Keep the description under 255 characters.';
    if (!in_array($f['recurring_interval'], ['weekly', 'monthly'], true)) $f['recurring_interval'] = 'monthly';

    if (!$errors && !$id && empty($_POST['confirm_dup'])) {       // duplicate detection
        $dup = (bool)val('SELECT id FROM transactions WHERE user_id=? AND type=? AND amount=? AND txn_date=? AND category_id=? AND description=? LIMIT 1',
            [$uid, $type, $f['amount'], $f['txn_date'], $f['category_id'], $f['description']]);
    }
    if (!$errors && !$dup) {
        $rec = $f['is_recurring'] && !($txn && $txn['parent_id']) ? 1 : 0;
        $int = $rec ? $f['recurring_interval'] : null;
        if ($id) {
            q('UPDATE transactions SET category_id=?, amount=?, txn_date=?, description=?, is_recurring=?, recurring_interval=? WHERE id=? AND user_id=?',
                [$f['category_id'], $f['amount'], $f['txn_date'], $f['description'], $txn['parent_id'] ? 0 : $rec, $txn['parent_id'] ? null : $int, $id, $uid]);
            flash(ucfirst($type) . ' updated.', 'success');
        } else {
            q('INSERT INTO transactions(user_id,category_id,type,amount,txn_date,description,is_recurring,recurring_interval) VALUES (?,?,?,?,?,?,?,?)',
                [$uid, $f['category_id'], $type, $f['amount'], $f['txn_date'], $f['description'], $rec, $int]);
            flash(ucfirst($type) . ' added.', 'success');
        }
        if ($type === 'expense') {
            $ym = substr($f['txn_date'], 0, 7);
            foreach (budget_progress($uid, $ym) as $b) if ((int)$b['category_id'] === $f['category_id']) {
                if ($b['state'] === 'over') flash($b['name'] . ' budget exceeded (' . $b['pct'] . '%).', 'warning');
                elseif ($b['state'] === 'warn') flash($b['name'] . ' budget is at ' . $b['pct'] . '%.', 'warning');
            }
            $s = row("SELECT AVG(amount) a, COUNT(*) n FROM transactions WHERE user_id=? AND type='expense' AND category_id=? AND id<>?", [$uid, $f['category_id'], $id ?: 0]);
            if ($s && $s['n'] >= 5 && (float)$f['amount'] > 3 * (float)$s['a']) flash('That is an unusually large amount for this category.', 'warning');
        }
        redirect('transactions.php');
    }
}

page_start(($id ? 'Edit ' : 'Add ') . $type, $id ? 'transactions' : $type);
?>
<div class="row justify-content-center"><div class="col-xl-7 col-lg-8">
<div class="cc-card static">
  <?php if (!$id): ?><ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item"><a class="btn <?= $type === 'income' ? 'btn-brand' : 'btn-ghost' ?>" href="?type=income"><i class="bi bi-arrow-down-left"></i> Income</a></li>
    <li class="nav-item"><a class="btn <?= $type === 'expense' ? 'btn-brand' : 'btn-ghost' ?>" href="?type=expense"><i class="bi bi-arrow-up-right"></i> Expense</a></li></ul><?php endif; ?>

  <?php if ($dup): ?><div class="alert alert-warning"><b>Possible duplicate.</b> A transaction with the same amount, date, category and description already exists.
    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="confirm_dup" value="1" id="cd" form="txnForm"><label class="form-check-label" for="cd">Save it anyway</label></div></div><?php endif; ?>

  <form method="post" id="txnForm" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="amount">Amount (<?= trim(CURRENCY) ?>)</label>
        <input type="number" step="0.01" min="0.01" class="form-control form-control-lg <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" id="amount" name="amount" value="<?= e($f['amount']) ?>" required autofocus><?= field_err($errors, 'amount') ?></div>
      <div class="col-md-6"><label class="form-label" for="txn_date">Date</label>
        <input type="date" class="form-control form-control-lg <?= isset($errors['txn_date']) ? 'is-invalid' : '' ?>" id="txn_date" name="txn_date" value="<?= e($f['txn_date']) ?>" required><?= field_err($errors, 'txn_date') ?></div>
      <div class="col-12"><label class="form-label" for="description">Description</label>
        <input class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" maxlength="255" value="<?= e($f['description']) ?>" placeholder="<?= $type === 'expense' ? 'e.g. Campus Cafe' : 'e.g. Monthly allowance' ?>" autocomplete="off"><?= field_err($errors, 'description') ?>
        <div id="aiSuggest" class="mt-2 small d-none"></div></div>
      <div class="col-12"><label class="form-label" for="category_id">Category</label>
        <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="category_id" name="category_id" required><option value="">Choose a category…</option>
          <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$f['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?= field_err($errors, 'category_id') ?></div>
      <?php if (!($txn && $txn['parent_id'])): ?>
      <div class="col-12"><div class="cc-card static p-3" style="background:var(--card2)"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="rec" name="is_recurring" value="1" <?= $f['is_recurring'] ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="rec">Recurring transaction</label></div>
        <div id="recOpts" class="<?= $f['is_recurring'] ? '' : 'd-none' ?>"><select class="form-select form-select-sm" name="recurring_interval" style="max-width:200px"><option value="monthly" <?= $f['recurring_interval'] === 'monthly' ? 'selected' : '' ?>>Every month</option><option value="weekly" <?= $f['recurring_interval'] === 'weekly' ? 'selected' : '' ?>>Every week</option></select>
          <div class="small text-secondary mt-1">Future entries are created automatically when they fall due.</div></div></div></div>
      <?php endif; ?>
    </div>
    <div class="d-flex gap-2 mt-4"><button class="btn btn-brand" type="submit"><i class="bi bi-check-lg"></i> <?= $id ? 'Save changes' : 'Save ' . $type ?></button><a class="btn btn-ghost" href="<?= url('transactions.php') ?>">Cancel</a></div>
  </form>
</div></div></div>
<?php
$js = 'var rec=document.getElementById("rec");if(rec)rec.addEventListener("change",function(){document.getElementById("recOpts").classList.toggle("d-none",!rec.checked)});
var desc=document.getElementById("description"),sel=document.getElementById("category_id"),box=document.getElementById("aiSuggest"),timer;
function suggest(){var v=desc.value.trim();if(v.length<3){box.classList.add("d-none");return}
fetch(' . json_encode(url('api/suggest-category.php')) . '+"?type=' . $type . '&q="+encodeURIComponent(v),{credentials:"same-origin"}).then(function(r){return r.json()}).then(function(r){
 if(!r.ok||!r.category){box.classList.add("d-none");return}
 if(String(sel.value)===String(r.category.id)){box.classList.add("d-none");return}
 box.innerHTML=\'<i class="bi bi-stars text-warning"></i> Suggested category: <b></b> <span class="text-secondary"></span> <button type="button" class="btn btn-ghost btn-sm ms-1" id="useSug">Use</button>\';
 box.querySelector("b").textContent=r.category.name;box.querySelector("span").textContent="("+r.category.source+")";
 box.classList.remove("d-none");document.getElementById("useSug").onclick=function(){sel.value=r.category.id;box.classList.add("d-none")}}).catch(function(){box.classList.add("d-none")})}
desc.addEventListener("input",function(){clearTimeout(timer);timer=setTimeout(suggest,400)});';
page_end(['js' => $js]);
