<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireLogin();

if (!isPost()) {
    redirect('frontend/applications.php');
}

$applicationId = (int) post('application_id');

$validator = Validator::make($_POST)->id('application_id', 'Заявка');

if ($validator->fails()) {
    flash('danger', 'Заявка не указана.');
    redirect('frontend/applications.php');
}

$error = Application::cancelByUser($applicationId, (int) Auth::id());

if ($error !== null) {
    flash('danger', $error);
    redirect('frontend/applications.php');
}

flash('success', 'Заявка №' . $applicationId . ' отменена.');
redirect('frontend/applications.php');
