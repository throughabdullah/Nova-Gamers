<?php require 'includes/init.php';$u=auth();$id=$u['id'];
$cats=q("SELECT id,name,type FROM categories WHERE user_id IS NULL OR user_id=? ORDER BY name",[$id])->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_check();
 if(isset($_POST['del'])){q("DELETE FROM transactions WHERE id=? AND user_id=?",[$_POST['del'],$id]);flash('Transaction deleted.');header('Location: transactions.php');exit;}
 $ty=$_POST['type']??'';$amt=(float)($_POST['amount']??0);$d=$_POST['txn_date']??'';$c=(int)($_POST['category_id']??0);$ds=trim($_POST['description']??'');
 $ok=in_array($ty,['income','expense'])&&$amt>0&&strtotime($d)&&array_filter($cats,fn($x)=>$x['id']==$c&&$x['type']===$ty);
 if(!$ok)flash('Check amount, date, and that the category matches the type.','danger');
 else{
  if(!empty($_POST['id']))q("UPDATE transactions SET type=?,amount=?,txn_date=?,category_id=?,description=? WHERE id=? AND user_id=?",[$ty,$amt,$d,$c,$ds,$_POST['id'],$id]);
  else q("INSERT INTO transactions(user_id,type,amount,txn_date,category_id,description) VALUES(?,?,?,?,?,?)",[$id,$ty,$amt,$d,$c,$ds]);
  flash('Transaction saved.');header('Location: transactions.php');exit;}}
$ed=isset($_GET['edit'])?q("SELECT * FROM transactions WHERE id=? AND user_id=?",[$_GET['edit'],$id])->fetch():null;
$ft=$ed['type']??(($_GET['type']??'')==='income'?'income':'expense');
$w='t.user_id=?';$p=[$id];
if(($s=trim($_GET['q']??''))!==''){$w.=' AND t.description LIKE ?';$p[]="%$s%";}
if(in_array($_GET['f']??'',['income','expense'])){$w.=' AND t.type=?';$p[]=$_GET['f'];}
if(!empty($_GET['from'])){$w.=' AND t.txn_date>=?';$p[]=$_GET['from'];}
if(!empty($_GET['to'])){$w.=' AND t.txn_date<=?';$p[]=$_GET['to'];}
$so=['date'=>'t.txn_date DESC,t.id DESC','amount'=>'t.amount DESC','cat'=>'c.name'][$_GET['sort']??'date']??'t.txn_date DESC,t.id DESC';
$pg=max(1,(int)($_GET['p']??1));$per=15;$n=q("SELECT COUNT(*) FROM transactions t WHERE $w",$p)->fetchColumn();
$rows=q("SELECT t.*,c.name cat FROM transactions t JOIN categories c ON c.id=t.category_id WHERE $w ORDER BY $so LIMIT $per OFFSET ".(($pg-1)*$per),$p)->fetchAll();
$qs=$_GET;unset($qs['p'],$qs['edit']);
head('Transactions','student');?>
<div class="card p-3 mb-3"><h5><?=$ed?'Edit':'Add'?> transaction</h5>
<form method="post" class="row g-2"><?=csrf_field()?><?php if($ed)echo '<input type="hidden" name="id" value="'.$ed['id'].'">';?>
<div class="col-md-2"><select class="form-select" name="type" id="ty"><option value="income"<?=$ft=='income'?' selected':''?>>Income</option><option value="expense"<?=$ft=='expense'?' selected':''?>>Expense</option></select></div>
<div class="col-md-2"><input class="form-control" type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required value="<?=e($ed['amount']??'')?>"></div>
<div class="col-md-2"><input class="form-control" type="date" name="txn_date" required value="<?=e($ed['txn_date']??date('Y-m-d'))?>"></div>
<div class="col-md-2"><select class="form-select" name="category_id" id="cs"><?php foreach($cats as $c)echo '<option value="'.$c['id'].'" data-t="'.$c['type'].'"'.(($ed['category_id']??0)==$c['id']?' selected':'').'>'.e($c['name']).'</option>';?></select></div>
<div class="col-md-2"><input class="form-control" name="description" placeholder="Description" maxlength="255" value="<?=e($ed['description']??'')?>"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div></form></div>
<form class="row g-2 mb-3"><div class="col-md-3"><input class="form-control" name="q" placeholder="Search description" value="<?=e($_GET['q']??'')?>"></div>
<div class="col-md-2"><select class="form-select" name="f"><option value="">All types</option><option value="income"<?=($_GET['f']??'')=='income'?' selected':''?>>Income</option><option value="expense"<?=($_GET['f']??'')=='expense'?' selected':''?>>Expense</option></select></div>
<div class="col-md-2"><input class="form-control" type="date" name="from" value="<?=e($_GET['from']??'')?>"></div><div class="col-md-2"><input class="form-control" type="date" name="to" value="<?=e($_GET['to']??'')?>"></div>
<div class="col-md-2"><select class="form-select" name="sort"><option value="date">Newest</option><option value="amount"<?=($_GET['sort']??'')=='amount'?' selected':''?>>Amount</option><option value="cat"<?=($_GET['sort']??'')=='cat'?' selected':''?>>Category</option></select></div>
<div class="col-md-1"><button class="btn btn-outline-secondary w-100">Go</button></div></form>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle mb-0"><tr><th>Date</th><th>Category</th><th>Description</th><th class="text-end">Amount</th><th></th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['txn_date'])?></td><td><?=e($r['cat'])?></td><td><?=e($r['description'])?></td><td class="text-end text-<?=$r['type']=='income'?'success':'danger'?>"><?=m($r['amount'])?></td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="?edit=<?=$r['id']?>">Edit</a>
<form method="post" class="d-inline" onsubmit="return confirm('Delete this transaction?')"><?=csrf_field()?><button class="btn btn-sm btn-outline-danger" name="del" value="<?=$r['id']?>">Delete</button></form></td></tr>
<?php endforeach;if(!$rows)echo '<tr><td colspan="5" class="text-secondary">No transactions found.</td></tr>';?></table></div>
<div class="mt-3 d-flex gap-2"><?php for($i=1;$i<=max(1,ceil($n/$per));$i++)echo '<a class="btn btn-sm btn-'.($i==$pg?'primary':'outline-secondary').'" href="?'.e(http_build_query($qs+['p'=>$i])).'">'.$i.'</a>';?></div></div>
<script>const ty=document.getElementById('ty'),cs=document.getElementById('cs');
function f(){let ok=false;[...cs.options].forEach(o=>{o.hidden=o.disabled=o.dataset.t!=ty.value;if(!o.hidden&&!ok&&(!cs.selectedOptions[0]||cs.selectedOptions[0].hidden)){cs.value=o.value;ok=true}})}
ty.onchange=f;f();</script>
<?php foot();
