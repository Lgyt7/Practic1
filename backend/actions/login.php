<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (!isPost()) {
    redirect('frontend/login.php');
}

$login    = post('login');
$password = (string) ($_POST['password'] ?? '');

setOld(['login' => $login]);

$validator = Validator::make($_POST)
    ->text('login', 'Логин', 1, 50)
    ->password();

if ($validator->fails()) {
    setErrors($validator->errors());
    redirect('frontend/login.php');
}

if (!Auth::attempt($login, $password)) {
    setErrors(['password' => 'Неверный логин или пароль.']);
    redirect('frontend/login.php');
}

setOld([]);
setErrors([]);

if (Auth::isAdmin()) {
    flash('success', 'Вход выполнен. Панель администратора.');
    redirect('frontend/admin.php');
}

flash('success', 'Вход выполнен.');
redirect('frontend/applications.php');
