<?php require 'includes/init.php';
$er=[];$v=['name'=>'','email'=>'','year'=>''];
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_check();
 $v=['name'=>trim($_POST['name']??''),'email'=>trim($_POST['email']??''),'year'=>$_POST['year']??''];$p=$_POST['password']??'';
 if($v['name']==='')$er['name']='Name is required.';
 if(!filter_var($v['email'],FILTER_VALIDATE_EMAIL))$er['email']='Enter a valid email.';
 elseif(q('SELECT 1 FROM users WHERE email=?',[$v['email']])->fetch())$er['email']='Email already registered.';
 if(strlen($p)<6)$er['password']='At least 6 characters.';
 if($p!==($_POST['confirm']??''))$er['confirm']='Passwords do not match.';
 if(!$er){q('INSERT INTO users(name,email,password_hash,academic_year) VALUES(?,?,?,?)',[$v['name'],$v['email'],password_hash($p,PASSWORD_DEFAULT),$v['year']]);flash('Account created. Please log in.');header('Location: login.php');exit;}}
function fe($k){global $er;return isset($er[$k])?'<div class="text-danger small">'.e($er[$k]).'</div>':'';}
head('Register');?>
<div class="row justify-content-center"><div class="col-md-6 col-lg-5"><div class="card p-4"><h3>Create account</h3>
<form method="post"><?=csrf_field()?>
<label class="form-label mt-2">Full name</label><input class="form-control" name="name" value="<?=e($v['name'])?>"><?=fe('name')?>
<label class="form-label mt-2">Email</label><input class="form-control" name="email" value="<?=e($v['email'])?>"><?=fe('email')?>
<label class="form-label mt-2">Academic year</label><select class="form-select" name="year"><?php foreach(['Year 1','Year 2','Year 3','Year 4','Graduate'] as $y)echo '<option'.($v['year']===$y?' selected':'').'>'.$y.'</option>';?></select>
<label class="form-label mt-2">Password</label><input class="form-control" type="password" name="password"><?=fe('password')?>
<label class="form-label mt-2">Confirm password</label><input class="form-control" type="password" name="confirm"><?=fe('confirm')?>
<button class="btn btn-primary w-100 mt-3">Register</button></form><p class="mt-3 mb-0 small">Have an account? <a href="login.php">Login</a></p></div></div></div>
<?php foot();
