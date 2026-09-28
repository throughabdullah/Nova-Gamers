<?php require 'includes/init.php';$u=auth();$id=$u['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_check();$n=trim($_POST['name']??'');$ty=$_POST['type']??'';
 try{
  if(isset($_POST['del'])){q("DELETE FROM categories WHERE id=? AND user_id=?",[$_POST['del'],$id]);flash('Category deleted.');}
  elseif($n===''||strlen($n)>60)flash('Enter a valid name.','danger');
  elseif(!empty($_POST['id'])){q("UPDATE categories SET name=? WHERE id=? AND user_id=?",[$n,$_POST['id'],$id]);flash('Category updated.');}
  elseif(in_array($ty,['income','expense'])){q("INSERT INTO categories(user_id,name,type) VALUES(?,?,?)",[$id,$n,$ty]);flash('Category added.');}
 }catch(PDOException $x){flash('That category is used by transactions and cannot be deleted.','danger');}
 header('Location: categories.php');exit;}
$cs=q("SELECT * FROM categories WHERE user_id IS NULL OR user_id=? ORDER BY type,name",[$id])->fetchAll();
head('Categories','student');?>
<div class="card p-3 mb-3"><h5>Add category</h5><form method="post" class="row g-2"><?=csrf_field()?><div class="col-md-5"><input class="form-control" name="name" placeholder="Name" required maxlength="60"></div>
<div class="col-md-4"><select class="form-select" name="type"><option value="expense">Expense</option><option value="income">Income</option></select></div><div class="col-md-3"><button class="btn btn-primary w-100">Add</button></div></form></div>
<div class="card p-3"><table class="table align-middle mb-0"><?php foreach($cs as $c):?><tr><td class="text-capitalize"><?=$c['type']?></td>
<?php if($c['user_id']):?><td><form method="post" class="d-flex gap-2"><?=csrf_field()?><input type="hidden" name="id" value="<?=$c['id']?>"><input class="form-control form-control-sm" name="name" value="<?=e($c['name'])?>"><button class="btn btn-sm btn-outline-primary">Save</button></form></td>
<td class="text-end"><form method="post" onsubmit="return confirm('Delete category?')"><?=csrf_field()?><button class="btn btn-sm btn-outline-danger" name="del" value="<?=$c['id']?>">Delete</button></form></td>
<?php else:?><td><?=e($c['name'])?></td><td class="text-end text-secondary small">Default</td><?php endif;?></tr><?php endforeach;?></table></div>
<?php foot();
