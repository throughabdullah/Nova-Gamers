<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();
if (!is_post()) redirect('transactions.php');
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$st = q('DELETE FROM transactions WHERE id = ? AND user_id = ?', [$id, $u['id']]);   // only the owner can delete
flash($st->rowCount() ? 'Transaction deleted.' : 'Transaction not found.', $st->rowCount() ? 'success' : 'danger');
redirect('transactions.php');
