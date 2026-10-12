<?php
declare(strict_types=1);

/**
 * backend/actions/admin_status.php
 * Обработка смены статуса заявки администратором.
 *
 * Доступ только для роли admin, переход проверяется на сервере.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireAdmin();

if (!isPost()) {
    redirect('frontend/admin.php');
}

$applicationId = (int) post('application_id');
$status        = post('status');

$error = Application::changeStatus($applicationId, $status);

if ($error !== null) {
    flash('danger', $error);
    redirect('frontend/admin.php');
}

flash('success', 'Статус заявки обновлён: «' . $status . '».');
redirect('frontend/admin.php');