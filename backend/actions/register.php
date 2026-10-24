<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (!isPost()) {
    redirect('frontend/register.php');
}

$login     = post('login');
$fullName  = post('full_name');
$phone     = post('phone');
$email     = post('email');
$password  = (string) ($_POST['password'] ?? '');

$validator = Validator::make($_POST)
    ->login()
    ->fullName()
    ->phone()
    ->email()
    ->password();

setOld([
    'login'     => $login,
    'full_name' => $fullName,
    'phone'     => $phone,
    'email'     => $email,
]);

if ($validator->fails()) {
    setErrors($validator->errors());
    redirect('frontend/register.php');
}

if (User::loginExists($login)) {
    setErrors(['login' => 'Пользователь с таким логином уже зарегистрирован.']);
    redirect('frontend/register.php');
}

if (User::emailExists($email)) {
    setErrors(['email' => 'Пользователь с такой электронной почтой уже зарегистрирован.']);
    redirect('frontend/register.php');
}

User::create($login, $password, $fullName, $phone, $email);

setOld([]);
setErrors([]);
flash('success', 'Регистрация выполнена. Теперь войдите под своим логином.');
redirect('frontend/login.php');
