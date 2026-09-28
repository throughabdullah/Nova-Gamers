<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="campuscoin_transactions_' . date('Ymd') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['date', 'type', 'category', 'amount', 'description']);
foreach (rows('SELECT t.txn_date, t.type, c.name, t.amount, t.description FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? ORDER BY t.txn_date DESC, t.id DESC', [$u['id']]) as $r) {
    $r['description'] = preg_match('/^[=+\-@]/', $r['description']) ? "'" . $r['description'] : $r['description'];   // block CSV formula injection
    fputcsv($out, array_values($r));
}
fclose($out);
