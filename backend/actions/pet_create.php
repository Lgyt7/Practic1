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

$name       = post('name');
$animalType = post('animal_type');
$breed      = post('breed');
$birthDate  = post('birth_date');
$note       = post('note');

setOld([
    'name'        => $name,
    'animal_type' => $animalType,
    'breed'       => $breed,
    'birth_date'  => $birthDate,
    'note'        => $note,
]);

$validator = Validator::make($_POST)
    ->text('name', 'Кличка питомца', 1, 100)
    ->inList('animal_type', ANIMAL_TYPES, 'Вид животного')
    ->optionalText('breed', 'Порода', 100)
    ->optionalDate('birth_date', 'Дата рождения')
    ->optionalText('note', 'Заметка', Pet::NOTE_MAX_LENGTH);

if ($validator->fails()) {
    setErrors($validator->errors());
    redirect('frontend/pets.php');
}

$userId = (int) Auth::id();

if (Pet::nameExists($userId, $name)) {
    setErrors(['name' => 'Питомец с такой кличкой уже есть в вашем профиле.']);
    redirect('frontend/pets.php');
}

$created = new DateTimeImmutable('today');
$birth   = $birthDate === '' ? null : new DateTimeImmutable($birthDate);

if ($birth !== null && $birth > $created) {
    setErrors(['birth_date' => 'Дата рождения питомца не может быть в будущем.']);
    redirect('frontend/pets.php');
}

Pet::create($userId, $name, $animalType, $breed, $birthDate === '' ? null : $birthDate, $note);

setOld([]);
setErrors([]);
flash('success', 'Питомец «' . $name . '» добавлен в профиль. Теперь его можно выбрать при записи.');
redirect('frontend/pets.php');
