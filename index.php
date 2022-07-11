<?php
require_once 'config/setup.php';
session_start();
echo 'Database created';
header('Location: profile.php');
