<?php
declare(strict_types=1);

/**
 * backend/actions/admin_status.php
 * Обработка смены статуса заявки администратором.
 *
 * Доступ только для роли admin, переход проверяется на сервере.
 * После обработки администратор возвращается на ту же страницу
 * панели, с теми же фильтрами и номером страницы.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireAdmin();

if (!isPost()) {
    redirect('frontend/admin.php');
}

$applicationId = (int) post('application_id');
$status        = post('status');

/**
 * Куда вернуться после обработки.
 * Принимается только путь внутри панели администратора,
 * чтобы подделанное поле не превращалось в ссылку на другой сайт.
 */
$back = 'frontend/admin.php';

if (preg_match('#^frontend/admin\.php\?[A-Za-z0-9_=&%.\-]*$#', (string) post('return'))) {
    $back = (string) post('return');
}

$error = Application::changeStatus($applicationId, $status);

if ($error !== null) {
    flash('danger', $error);
    redirect($back);
}

flash('success', 'Статус заявки №' . $applicationId . ' обновлён: «' . $status . '».');
redirect($back);
