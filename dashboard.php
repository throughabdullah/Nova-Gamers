<?php require 'includes/init.php';$u=auth();$id=$u['id'];
$mo=date('Y-m');$a=$mo.'-01';$b=date('Y-m-d',strtotime("$a +1 month"));
$t=['income'=>0,'expense'=>0];
foreach(q("SELECT type,SUM(amount) s FROM transactions WHERE user_id=? AND txn_date>=? AND txn_date<? GROUP BY type",[$id,$a,$b])->fetchAll() as $r)$t[$r['type']]=(float)$r['s'];
$all=q("SELECT COALESCE(SUM(IF(type='income',amount,-amount)),0) FROM transactions WHERE user_id=?",[$id])->fetchColumn();
$cat=q("SELECT c.name,SUM(t.amount) s FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND t.type='expense' AND t.txn_date>=? AND t.txn_date<? GROUP BY c.id,c.name ORDER BY s DESC",[$id,$a,$b])->fetchAll();
$bud=q("SELECT c.name,b.amount bud,COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id=b.user_id AND category_id=b.category_id AND txn_date>=? AND txn_date<?),0) act FROM budgets b JOIN categories c ON c.id=b.category_id WHERE b.user_id=? AND b.budget_month=?",[$a,$b,$id,$mo])->fetchAll();
$six=q("SELECT DATE_FORMAT(txn_date,'%Y-%m') m,SUM(amount) s FROM transactions WHERE user_id=? AND type='expense' AND txn_date>=DATE_SUB(?,INTERVAL 5 MONTH) AND txn_date<? GROUP BY m ORDER BY m",[$id,$a,$b])->fetchAll();
$rec=q("SELECT t.*,c.name cat FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? ORDER BY txn_date DESC,t.id DESC LIMIT 6",[$id])->fetchAll();
$tip='Add a few expenses to get spending tips.';
if($t['expense']>$t['income']&&$t['expense']>0)$tip='You are spending more than you earn this month. Review your largest categories.';
elseif($cat&&$t['expense']>0&&$cat[0]['s']/$t['expense']>.4)$tip=round($cat[0]['s']/$t['expense']*100).'% of your spending is on '.$cat[0]['name'].'. Setting a budget for it could help.';
elseif($t['expense']>0)$tip='Spending looks balanced this month. Consider moving some surplus to savings.';
head('Dashboard','student');?>
<h3>Hello, <?=e(explode(' ',$u['name'])[0])?> <small class="text-secondary fs-6"><?=date('F Y')?></small></h3>
<div class="mb-3"><a class="btn btn-success btn-sm" href="transactions.php?type=income">+ Income</a> <a class="btn btn-danger btn-sm" href="transactions.php?type=expense">+ Expense</a></div>
<div class="row g-3 mb-3">
<?php foreach([['Month balance',$t['income']-$t['expense']],['Total income',$t['income']],['Total expenses',$t['expense']],['Available balance',$all]] as $c)echo '<div class="col-6 col-lg-3"><div class="card p-3"><div class="text-secondary small">'.$c[0].'</div><div class="stat">'.m($c[1]).'</div></div></div>';?>
</div>
<div class="row g-3 mb-3"><div class="col-lg-6"><div class="card p-3"><b>Top spending category</b><div class="stat"><?=$cat?e($cat[0]['name']).' - '.m($cat[0]['s']):'No expenses yet'?></div></div></div>
<div class="col-lg-6"><div class="card p-3"><b>Saving tip</b><div><?=e($tip)?></div></div></div></div>
<div class="row g-3 mb-3">
<div class="col-lg-6"><div class="card p-3"><b>Income vs Expense</b><canvas id="c1" height="140"></canvas></div></div>
<div class="col-lg-6"><div class="card p-3"><b>Expense by category</b><canvas id="c2" height="140"></canvas></div></div>
<div class="col-lg-6"><div class="card p-3"><b>Budget vs Actual</b><canvas id="c3" height="140"></canvas></div></div>
<div class="col-lg-6"><div class="card p-3"><b>Six-month spending</b><canvas id="c4" height="140"></canvas></div></div></div>
<div class="card p-3"><b>Recent transactions</b><div class="table-responsive"><table class="table mb-0"><?php foreach($rec as $r)echo '<tr><td>'.e($r['txn_date']).'</td><td>'.e($r['cat']).'</td><td>'.e($r['description']).'</td><td class="text-end text-'.($r['type']==='income'?'success':'danger').'">'.m($r['amount']).'</td></tr>';if(!$rec)echo '<tr><td class="text-secondary">Nothing yet.</td></tr>';?></table></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>const D=<?=json_encode(['inc'=>$t['income'],'exp'=>$t['expense'],'cat'=>$cat,'bud'=>$bud,'six'=>$six],JSON_HEX_TAG)?>;
const mk=(i,t,l,d,o={})=>new Chart(document.getElementById(i),{type:t,data:{labels:l,datasets:d},options:o});
mk('c1','bar',['Income','Expense'],[{data:[D.inc,D.exp],backgroundColor:['#22c55e','#ef4444']}],{plugins:{legend:{display:false}}});
mk('c2','doughnut',D.cat.map(x=>x.name),[{data:D.cat.map(x=>+x.s)}]);
mk('c3','bar',D.bud.map(x=>x.name),[{label:'Budget',data:D.bud.map(x=>+x.bud),backgroundColor:'#7c3aed'},{label:'Actual',data:D.bud.map(x=>+x.act),backgroundColor:'#f59e0b'}]);
mk('c4','line',D.six.map(x=>x.m),[{label:'Spending',data:D.six.map(x=>+x.s),tension:.35,borderColor:'#a78bfa'}]);</script>
<?php foot();
