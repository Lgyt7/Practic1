<?php
declare(strict_types=1);

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

$back = 'frontend/admin.php';
$parts = parse_url($return);

if (
    is_array($parts)
    && ($parts['path'] ?? '') === url('frontend/admin.php')
    && !isset($parts['host'])
    && preg_match('#^[A-Za-z0-9_=&%+.\-]*$#', $parts['query'] ?? '')
) {
    $back .= isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
}

flash($error === null ? 'success' : 'danger', $error ?? 'Статус заявки №' . $applicationId . ' обновлён: «' . $status . '».');
redirect($back);
