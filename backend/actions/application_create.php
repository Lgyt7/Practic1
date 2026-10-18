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

$petId   = post('pet_id');
$service = post('service');
$apptDate = post('appt_date');
$apptTime = post('appt_time');
$payment = post('payment_method');

setOld([
    'pet_id'         => $petId,
    'service'        => $service,
    'appt_date'      => $apptDate,
    'appt_time'      => $apptTime,
    'payment_method' => $payment,
]);

// Услуга принимается только из справочника (backend/config.php),
// свободный текст отклоняется сервером.
$validator = Validator::make($_POST)
    ->id('pet_id', 'Питомец')
    ->inList('service', SERVICES, 'Услуга')
    ->date('appt_date')
    ->notInPast('appt_date', 'Дата приёма')
    ->time('appt_time')
    ->paymentMethod();

if ($validator->fails()) {
    setErrors($validator->errors());
    redirect('frontend/application_create.php');
}

// Питомец должен принадлежать владельцу: подсунутый чужой id
// не должен превращаться в запись на чужого питомца.
if (Pet::findForUser((int) $petId, (int) Auth::id()) === null) {
    setErrors(['pet_id' => 'Выберите питомца из своего профиля.']);
    redirect('frontend/application_create.php');
}

Application::create(
    (int) Auth::id(),
    (int) $petId,
    $service,
    $apptDate,
    $apptTime,
    $payment
);

setOld([]);
setErrors([]);
flash('success', 'Заявка отправлена администратору и получила статус «' . STATUS_NEW . '».');
redirect('frontend/applications.php');