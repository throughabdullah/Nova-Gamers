<?php
require __DIR__.'/config/config.php';
try{$pdo=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}catch(Throwable $e){exit('Start MySQL in XAMPP first.');}
$sql=file_get_contents(__DIR__.'/database/campuscoin_db.sql');
foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $s){ if($s!=='' && !preg_match('/^(--|\/\*)/',$s)) { try{$pdo->exec($s);}catch(Throwable $e){} } }
$pdo->exec('USE `'.DB_NAME.'`');
$ins=$pdo->prepare("INSERT INTO users(name,email,password_hash,role,academic_year,status) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash),role=VALUES(role),academic_year=VALUES(academic_year),status='active'");
$ins->execute(['CampusCoin Admin','admin@campuscoin.local',password_hash('CampusAdmin@123',PASSWORD_DEFAULT),'admin','', 'active']);
$ins->execute(['Saif Haider','saif.haider@campuscoin.local',password_hash('123456',PASSWORD_DEFAULT),'student','Year 1','active']);
echo '<!doctype html><meta charset="utf-8"><title>CampusCoin Installed</title><body style="font-family:system-ui;max-width:700px;margin:60px auto;padding:20px"><h1>CampusCoin is ready.</h1><p>Database: <b>campuscoin_db</b></p><p>Student: <b>saif.haider@campuscoin.local</b> / <b>123456</b></p><p>Admin: <b>admin@campuscoin.local</b> / <b>CampusAdmin@123</b></p><p><a href="login.php">Open student login</a> · <a href="admin/login.php">Open admin login</a></p><p><b>After setup, delete or rename install.php.</b></p></body>';
