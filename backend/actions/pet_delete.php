<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireLogin();

if (Auth::isAdmin()) {
    redirect('frontend/admin.php');
}

if (!isPost()) {
    redirect('frontend/pets.php');
}

$validator = Validator::make($_POST)->id('pet_id', 'Питомец');

if ($validator->fails()) {
    flash('danger', 'Питомец не указан.');
    redirect('frontend/pets.php');
}

$petId = (int) post('pet_id');
$error = Pet::delete($petId, (int) Auth::id());

if ($error !== null) {
    flash('danger', $error);
    redirect('frontend/pets.php');
}

flash('success', 'Питомец удалён из профиля.');
redirect('frontend/pets.php');
