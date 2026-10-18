<?php
declare(strict_types=1);

/**
 * backend/actions/admin_status.php
 * Обработка смены статуса заявки администратором.
 *
 * Доступ только для роли admin, переход проверяется на сервере.
 *
 * Обработчик один и тот же для двух способов отправки:
 *  - обычная форма → редирект обратно на панель с фильтрами;
 *  - fetch из панели → JSON, страница не перезагружается,
 *    а скрипт обновляет бейдж, счётчики и форму переходов на месте.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireAdmin();

if (!isPost()) {
    redirect('frontend/admin.php');
}

$applicationId = (int) post('application_id');
$status        = post('status');
$return        = (string) post('return');

$error = Application::changeStatus($applicationId, $status);

if (isAjax()) {
    // Страница не перезагружается, поэтому скрипту нужны новые счётчики
    // и число записей в текущей выборке — с теми же фильтрами,
    // что и на открытой странице (они лежат в поле return).
    $filter = ApplicationFilter::fromReturn($return);
    $result = Application::search($filter->status, $filter->service, $filter->query, $filter->page);

    jsonResponse([
        'ok'          => $error === null,
        'message'     => $error ?? sprintf('Статус заявки №%d обновлён: «%s».', $applicationId, $status),
        'status'      => $status,
        'badge'       => statusBadgeClass($status),
        'transitions' => Application::availableTransitions($status),
        'counts'      => Application::countByStatus(),
        'total'       => $result['total'],
    ], $error === null ? 200 : 422);
}

/**
 * Куда вернуться после обработки обычной отправки формы.
 * Принимается только путь внутри панели администратора,
 * чтобы подделанное поле не превращалось в ссылку на другой сайт.
 */
$back = 'frontend/admin.php';

if (preg_match('#^frontend/admin\.php\?[A-Za-z0-9_=&%.\-]*$#', $return)) {
    $back = $return;
}

flash($error === null ? 'success' : 'danger', $error ?? 'Статус заявки №' . $applicationId . ' обновлён: «' . $status . '».');
redirect($back);