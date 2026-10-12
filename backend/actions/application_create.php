<?php
declare(strict_types=1);

/**
 * backend/actions/application_create.php
 * Обработка формы создания заявки на приём.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireLogin();

if (!isPost()) {
    redirect('frontend/application_create.php');
}

$petName    = post('pet_name');
$animalType = post('animal_type');
$service    = post('service');
$apptDate   = post('appt_date');
$apptTime   = post('appt_time');
$payment    = post('payment_method');

setOld([
    'pet_name'       => $petName,
    'animal_type'    => $animalType,
    'service'        => $service,
    'appt_date'      => $apptDate,
    'appt_time'      => $apptTime,
    'payment_method' => $payment,
]);

$validator = Validator::make($_POST)
    ->text('pet_name', 'Кличка питомца', 1, 100)
    ->text('animal_type', 'Вид животного', 1, 50)
    ->text('service', 'Услуга', 3, 200)
    ->date('appt_date')
    ->time('appt_time')
    ->paymentMethod();

if ($validator->fails()) {
    setErrors($validator->errors());
    redirect('frontend/application_create.php');
}

// Дата приёма не может быть в прошлом.
$date = DateTime::createFromFormat('!d.m.Y', $apptDate);

if ($date < new DateTime('today')) {
    setErrors(['appt_date' => 'Дата приёма не может быть в прошлом.']);
    redirect('frontend/application_create.php');
}

Application::create(
    (int) Auth::id(),
    $petName,
    $animalType,
    $service,
    $apptDate,
    $apptTime,
    $payment
);

setOld([]);
setErrors([]);
flash('success', 'Заявка отправлена администратору и получила статус «' . STATUS_NEW . '».');
redirect('frontend/applications.php');