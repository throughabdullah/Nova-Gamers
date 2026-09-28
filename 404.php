<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/config/config.php';
http_response_code(404);
$home = BASE_URL . '/index.php';
?><!DOCTYPE html>
<html lang="en" data-bs-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404 · CampusCoin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(700px 400px at 70% 0,#1c2a5a55,transparent),#080d19;color:#e8eef9;font-family:system-ui,'Segoe UI',sans-serif;text-align:center}
.box{max-width:440px;padding:2rem}.big{font-size:6rem;font-weight:800;line-height:1;background:linear-gradient(120deg,#2ee6a6,#6d7cff);-webkit-background-clip:text;background-clip:text;color:transparent}
p{color:#93a1bb}a{display:inline-block;margin-top:1rem;padding:.7rem 1.4rem;border-radius:12px;background:linear-gradient(135deg,#2ee6a6,#22c6d8);color:#03201a;font-weight:700;text-decoration:none}i{font-size:2rem;color:#ffc857}</style></head>
<body><div class="box"><i class="bi bi-compass"></i><div class="big">404</div><h2>Page not found</h2><p>The page you are looking for does not exist or was moved.</p><a href="<?= htmlspecialchars($home) ?>">Back to CampusCoin</a></div></body></html>
