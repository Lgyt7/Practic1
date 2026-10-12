<?php
declare(strict_types=1);

/**
 * frontend/admin.php
 * Панель администратора клиники.
 *
 * Доступ только для роли admin — проверка выполняется на сервере
 * (Auth::requireAdmin), неавторизованный пользователь сюда не попадёт.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

Auth::requireAdmin();

$__pageTitle = 'Панель администратора';
$__apps      = Application::all();
$__counts    = Application::countByStatus();

/** Фильтр по статусу из GET-параметра. */
$__filter = isset($_GET['status']) ? (string) $_GET['status'] : '';

if ($__filter !== '' && !in_array($__filter, [STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED], true)) {
    $__filter = '';
}

$__visible = $__filter === ''
    ? $__apps
    : array_values(array_filter($__apps, static fn (array $a): bool => $a['status'] === $__filter));

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">

        <div class="page-head">
            <h1 class="page-title">Панель администратора</h1>
            <span class="badge badge--new"><?= e(Auth::isAdmin() ? 'роль: admin' : '') ?></span>
        </div>

        <!-- Сводка -->
        <div class="stats">
            <?php foreach ($__counts as $__status => $__count): ?>
                <div class="stat">
                    <p class="stat__value"><?= (int) $__count ?></p>
                    <p class="stat__label"><?= e($__status) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Фильтр -->
        <div class="filters">
            <a class="chip<?= $__filter === '' ? ' chip--active' : '' ?>"
               href="<?= url('frontend/admin.php') ?>">Все</a>
            <?php foreach ([STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED] as $__status): ?>
                <a class="chip<?= $__filter === $__status ? ' chip--active' : '' ?>"
                   href="<?= url('frontend/admin.php?status=' . urlencode($__status)) ?>">
                    <?= e($__status) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($__visible === []): ?>
            <div class="card card--empty"><p>Заявок нет.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Владелец</th>
                            <th>Питомец</th>
                            <th>Услуга</th>
                            <th>Дата</th>
                            <th>Время</th>
                            <th>Оплата</th>
                            <th>Статус</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($__visible as $__app): ?>
                        <?php $__next = Application::availableTransitions($__app['status']); ?>
                        <tr>
                            <td><?= (int) $__app['id'] ?></td>
                            <td>
                                <span class="table__name"><?= e($__app['full_name']) ?></span>
                                <span class="table__sub"><?= e($__app['login']) ?></span>
                            </td>
                            <td>
                                <span class="table__name"><?= e($__app['pet_name']) ?></span>
                                <span class="table__sub"><?= e($__app['animal_type']) ?></span>
                            </td>
                            <td><?= e($__app['service']) ?></td>
                            <td><?= e($__app['appt_date']) ?></td>
                            <td><?= e($__app['appt_time']) ?></td>
                            <td><?= e($__app['payment_method']) ?></td>
                            <td>
                                <span class="badge <?= e(statusBadgeClass($__app['status'])) ?>">
                                    <?= e($__app['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($__next === []): ?>
                                    <span class="muted">—</span>
                                <?php else: ?>
                                    <form method="post" action="<?= url('backend/actions/admin_status.php') ?>"
                                          class="inline-form">
                                        <input type="hidden" name="application_id" value="<?= (int) $__app['id'] ?>">
                                        <select class="field__input field__input--sm" name="status">
                                            <?php foreach ($__next as $__status): ?>
                                                <option value="<?= e($__status) ?>"><?= e($__status) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn--sm btn--primary">OK</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>