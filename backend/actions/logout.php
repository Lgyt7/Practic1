<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (!isPost()) {
    redirect('index.php');
}

Auth::logout();

flash('success', 'Вы вышли из системы.');
redirect('frontend/login.php');
