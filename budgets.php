<?php require 'includes/init.php';$u=auth();$id=$u['id'];
$cats=q("SELECT id,name FROM categories WHERE type='expense' AND (user_id IS NULL OR user_id=?) ORDER BY name",[$id])->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_check();
 if(isset($_POST['del'])){q("DELETE FROM budgets WHERE id=? AND user_id=?",[$_POST['del'],$id]);flash('Budget deleted.');}
 else{$c=(int)$_POST['category_id'];$mo=$_POST['month']??'';$a=(float)$_POST['amount'];
  if(!array_filter($cats,fn($x)=>$x['id']==$c)||!preg_match('/^\d{4}-\d{2}$/',$mo)||$a<=0)flash('Invalid budget details.','danger');
  else{q("INSERT INTO budgets(user_id,category_id,budget_month,amount) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE amount=VALUES(amount)",[$id,$c,$mo,$a]);flash('Budget saved.');}}
 header('Location: budgets.php');exit;}
$mo=$_GET['month']??date('Y-m');if(!preg_match('/^\d{4}-\d{2}$/',$mo))$mo=date('Y-m');
$a=$mo.'-01';$b=date('Y-m-d',strtotime("$a +1 month"));
$bs=q("SELECT b.id,b.amount,c.name,COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id=b.user_id AND category_id=b.category_id AND type='expense' AND txn_date>=? AND txn_date<?),0) act FROM budgets b JOIN categories c ON c.id=b.category_id WHERE b.user_id=? AND b.budget_month=? ORDER BY c.name",[$a,$b,$id,$mo])->fetchAll();
head('Budgets','student');?>
<div class="card p-3 mb-3"><h5>Set monthly budget (saving again for the same category/month edits it)</h5>
<form method="post" class="row g-2"><?=csrf_field()?><div class="col-md-3"><select class="form-select" name="category_id"><?php foreach($cats as $c)echo '<option value="'.$c['id'].'">'.e($c['name']).'</option>';?></select></div>
<div class="col-md-3"><input class="form-control" type="month" name="month" value="<?=e($mo)?>" required></div>
<div class="col-md-3"><input class="form-control" type="number" step="0.01" min="0.01" name="amount" placeholder="Budget amount" required></div><div class="col-md-3"><button class="btn btn-primary w-100">Save budget</button></div></form></div>
<form class="mb-3"><input type="month" name="month" value="<?=e($mo)?>" onchange="this.form.submit()" class="form-control w-auto"></form>
<?php foreach($bs as $r):$pc=round($r['act']/$r['amount']*100);$cl=$pc>100?'danger':($pc>=80?'warning':'success');?>
<div class="card p-3 mb-2"><div class="d-flex justify-content-between"><b><?=e($r['name'])?></b>
<form method="post" onsubmit="return confirm('Delete budget?')"><?=csrf_field()?><button class="btn btn-sm btn-outline-danger" name="del" value="<?=$r['id']?>">Delete</button></form></div>
<div class="progress my-2"><div class="progress-bar bg-<?=$cl?>" style="width:<?=min(100,$pc)?>%"><?=$pc?>%</div></div>
<small><?=m($r['act'])?> of <?=m($r['amount'])?> - remaining <?=m(max(0,$r['amount']-$r['act']))?></small>
<?php if($pc>100)echo '<div class="text-danger small">Budget exceeded by '.m($r['act']-$r['amount']).'.</div>';elseif($pc>=80)echo '<div class="text-warning small">Approaching budget limit.</div>';?></div>
<?php endforeach;if(!$bs)echo '<p class="text-secondary">No budgets for this month.</p>';
foot();
