<?php
require_once __DIR__ . '/../helpers.php';
session_destroy();
header('Location: ' . base_url());
exit;
