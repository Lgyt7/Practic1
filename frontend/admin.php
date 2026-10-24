<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

Auth::requireAdmin();

$__pageTitle = 'Панель администратора';

$__filter = ApplicationFilter::fromArray($_GET);

$__status = $__filter->status;
$__service = $__filter->service;
$__query = $__filter->query;

$__statuses = STATUSES;

$__result = Application::search($__status, $__service, $__query, $__filter->page);
$__rows   = $__result['rows'];
$__pages  = $__result['pages'];
$__page   = $__result['page'];
$__total  = $__result['total'];
$__counts = Application::countByStatus();

$__back = urlQuery('frontend/admin.php', $__filter->paramsWithPage($__page));

$__filtered = $__filter->isActive();

$__from = $__total === 0 ? 0 : ($__page - 1) * ADMIN_PAGE_SIZE + 1;
$__to   = min($__page * ADMIN_PAGE_SIZE, $__total);

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">

        <div class="page-head">
            <h1 class="page-title">
                <i class="bi bi-clipboard2-pulse page-title__icon"></i>Панель администратора
            </h1>
            <span class="badge badge--confirmed">
                <i class="bi bi-person-badge"></i> администратор
            </span>
        </div>

        <div class="stats" data-stats>
            <?php foreach ($__counts as $__s => $__count): ?>
                <?php $__isActive = $__status === $__s; ?>
                <a class="stat<?= $__isActive ? ' stat--active' : '' ?>"
                   data-stat="<?= e($__s) ?>"
                   href="<?= urlQuery('frontend/admin.php', ['status' => $__isActive ? '' : $__s, 'service' => $__service, 'q' => $__query]) ?>"
                   aria-label="<?= e($__s) ?>: <?= (int) $__count ?> заявок">
                    <p class="stat__value"><?= (int) $__count ?></p>
                    <p class="stat__label"><?= e($__s) ?></p>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="admin-filters" method="get" action="<?= url('frontend/admin.php') ?>">

            <div class="admin-filters__row">
                <div class="field field--grow">
                    <label class="field__label" for="q">Поиск</label>
                    <span class="field__control">
                        <i class="bi bi-search field__control-icon"></i>
                        <input class="field__input field__input--icon" type="search" id="q" name="q"
                               value="<?= e($__query) ?>" maxlength="60"
                               placeholder="Кличка питомца или ФИО владельца">
                    </span>
                </div>

                <div class="field">
                    <label class="field__label" for="status">Статус</label>
                    <select class="field__input" id="status" name="status">
                        <option value="">Все статусы</option>
                        <?php foreach ($__statuses as $__s): ?>
                            <option value="<?= e($__s) ?>" <?= $__status === $__s ? 'selected' : '' ?>>
                                <?= e($__s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label class="field__label" for="service">Услуга</label>
                    <select class="field__input" id="service" name="service">
                        <option value="">Все услуги</option>
                        <?php foreach (SERVICES as $__s): ?>
                            <option value="<?= e($__s) ?>" <?= $__service === $__s ? 'selected' : '' ?>>
                                <?= e(ucfirst($__s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-filters__actions">
                    <button type="submit" class="btn btn--primary">
                        <i class="bi bi-funnel"></i> Найти
                    </button>
                    <?php if ($__filtered): ?>
                        <a class="btn btn--ghost" href="<?= url('frontend/admin.php') ?>">
                            <i class="bi bi-x-circle"></i> Сбросить
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="filters">
            <a class="chip<?= $__status === '' ? ' chip--active' : '' ?>"
               href="<?= urlQuery('frontend/admin.php', ['service' => $__service, 'q' => $__query]) ?>">
                <i class="bi bi-list-ul"></i> Все
            </a>
            <?php foreach ($__statuses as $__s): ?>
                <a class="chip<?= $__status === $__s ? ' chip--active' : '' ?>"
                   href="<?= urlQuery('frontend/admin.php', ['status' => $__s, 'service' => $__service, 'q' => $__query]) ?>">
                    <span class="chip__dot <?= e(statusBadgeClass($__s)) ?>"></span>
                    <?= e($__s) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <p class="result-line">
            <i class="bi bi-info-circle"></i>
            <span>
                Показано <strong><?= $__from ?>–<?= $__to ?></strong>
                из <strong data-result-total><?= $__total ?></strong>&nbsp;<?= e(pluralForms($__total, 'заявка', 'заявки', 'заявок')) ?><?php
                if ($__filtered) {
                    echo ' по выбранным фильтрам';
                } ?>

                <?php if ($__pages > 1): ?>
                    · стр. <?= $__page ?> из <?= $__pages ?>
                <?php endif; ?>
            </span>
        </p>

        <?php if ($__rows === []): ?>
            <div class="card card--empty">
                <i class="bi bi-inbox card--empty__icon"></i>
                <p>Заявок не найдено.</p>
                <?php if ($__filtered): ?>
                    <a class="btn btn--ghost" href="<?= url('frontend/admin.php') ?>">Показать все заявки</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
 ?>
            <div class="table-wrap" data-status-filter="<?= e($__status) ?>">
                <table class="table table--responsive">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Владелец</th>
                            <th>Питомец</th>
                            <th>Услуга</th>
                            <th>Дата</th>
                            <th>Оплата</th>
                            <th>Статус</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($__rows as $__app): ?>
                        <?php $__next = Application::availableTransitions($__app['status']); ?>
                        <tr data-application-row="<?= (int) $__app['id'] ?>">
                            <td data-label="№"><?= (int) $__app['id'] ?></td>
                            <td data-label="Владелец">
                                <span class="table__name"><?= e($__app['full_name']) ?></span>
                                <span class="table__sub"><?= e($__app['login']) ?></span>
                                <span class="table__sub"><?= e($__app['phone']) ?></span>
                            </td>
                            <td data-label="Питомец">
                                <span class="table__name"><?= e($__app['pet_name']) ?></span>
                                <span class="table__sub"><?= e(ucfirst($__app['animal_type'])) ?></span>
                            </td>
                            <td data-label="Услуга"><?= e(ucfirst($__app['service'])) ?></td>
                            <td data-label="Дата и время">
                                <span class="table__name"><?= e(formatDate($__app['appt_date'])) ?></span>
                                <span class="table__sub">
                                    <i class="bi bi-clock"></i> <?= e($__app['appt_time']) ?>
                                </span>
                            </td>
                            <td data-label="Оплата"><?= e($__app['payment_method']) ?></td>
                            <td data-label="Статус">
                                <span class="badge <?= e(statusBadgeClass($__app['status'])) ?>"
                                      data-status-badge>
                                    <?= e($__app['status']) ?>
                                </span>
                                <?php if ((int) $__app['has_review'] > 0): ?>
                                    <span class="table__flag" title="Есть отзыв">
                                        <i class="bi bi-chat-quote-fill"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Действие" data-status-actions>
                                <?php if ($__next === []): ?>
                                    <span class="muted">—</span>
                                <?php else: ?>
                                    <form method="post" action="<?= url('backend/actions/admin_status.php') ?>"
                                          class="inline-form" data-status-form>
                                        <input type="hidden" name="application_id" value="<?= (int) $__app['id'] ?>">
                                        <input type="hidden" name="return" value="<?= e($__back) ?>">
                                        <select class="field__input field__input--sm" name="status"
                                                aria-label="Новый статус заявки №<?= (int) $__app['id'] ?>">
                                            <?php foreach ($__next as $__s): ?>
                                                <option value="<?= e($__s) ?>"><?= e($__s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn--sm btn--primary">
                                            <i class="bi bi-check-lg"></i> OK
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($__pages > 1): ?>
            <nav class="pagination" aria-label="Страницы списка заявок">
                <?php if ($__page > 1): ?>
                    <a class="pagination__btn"
                       href="<?= urlQuery('frontend/admin.php', $__filter->paramsWithPage($__page - 1)) ?>"
                       aria-label="Предыдущая страница"><i class="bi bi-chevron-left"></i></a>
                <?php else: ?>
                    <span class="pagination__btn pagination__btn--off"><i class="bi bi-chevron-left"></i></span>
                <?php endif; ?>

                <?php for ($__p = 1; $__p <= $__pages; $__p++): ?>
                    <?php if ($__p === $__page): ?>
                        <span class="pagination__btn pagination__btn--current" aria-current="page"><?= $__p ?></span>
                    <?php else: ?>
                        <a class="pagination__btn"
                           href="<?= urlQuery('frontend/admin.php', $__filter->paramsWithPage($__p)) ?>"><?= $__p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($__page < $__pages): ?>
                    <a class="pagination__btn"
                       href="<?= urlQuery('frontend/admin.php', $__filter->paramsWithPage($__page + 1)) ?>"
                       aria-label="Следующая страница"><i class="bi bi-chevron-right"></i></a>
                <?php else: ?>
                    <span class="pagination__btn pagination__btn--off"><i class="bi bi-chevron-right"></i></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
