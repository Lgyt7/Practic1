<?php
declare(strict_types=1);

/**
 * backend/actions/pet_delete.php
 * Удаление питомца из профиля владельца.
 *
 * Питомца с заявками удалить нельзя: в таблице applications
 * осталась бы ссылка на несуществующую запись.
 */

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