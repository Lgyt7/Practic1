<?php
declare(strict_types=1);

/**
 * frontend/admin.php
 * Панель администратора клиники.
 *
 * Возможности: фильтр по статусу и услуге, поиск по кличке питомца
 * или ФИО владельца, пагинация, всплывающие сообщения, смена статуса.
 *
 * Доступ только для роли admin — проверка выполняется на сервере
 * (Auth::requireAdmin), неавторизованный пользователь сюда не попадёт.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

Auth::requireAdmin();

$__pageTitle = 'Панель администратора';

/* ---------- Параметры фильтрации из GET ------------------------------
   Каждый параметр проверяется по справочнику: произвольное значение
   из адресной строки не должно попасть в SQL как есть.                 */

$__statuses = [STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED];

$__status = isset($_GET['status']) ? (string) $_GET['status'] : '';

if (!in_array($__status, $__statuses, true)) {
    $__status = '';
}

$__service = isset($_GET['service']) ? (string) $_GET['service'] : '';

if (!in_array($__service, SERVICES, true)) {
    $__service = '';
}

/** Поисковая строка в том виде, в каком её видит пользователь. */
$__query = mb_substr(trim(isset($_GET['q']) ? (string) $_GET['q'] : ''), 0, 60);

$__requestedPage = max(1, (int) (isset($_GET['page']) ? $_GET['page'] : 1));

/* Поиск идёт по кличке питомца и ФИО владельца.
   Служебные символы LIKE экранируются внутри Application::search(). */
$__result = Application::search($__status, $__service, $__query, $__requestedPage);
$__rows   = $__result['rows'];
$__pages  = $__result['pages'];
$__page   = $__result['page'];
$__total  = $__result['total'];
$__counts = Application::countByStatus();

/** Текущие фильтры без номера страницы — для ссылок «чипов» и сброса. */
$__filters = ['status' => $__status, 'service' => $__service, 'q' => $__query];

/** Адрес текущей выборки — возвращается сюда после смены статуса. */
$__back = urlQuery('frontend/admin.php', $__filters + ['page' => $__page]);

$__filtered = $__status !== '' || $__service !== '' || $__query !== '';

/** Первая и последняя показанные записи: «показано 1–5 из 12». */
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

        <!-- ============ Сводка ============ -->
        <div class="stats">
            <?php foreach ($__counts as $__s => $__count): ?>
                <?php $__isActive = $__status === $__s; ?>
                <a class="stat<?= $__isActive ? ' stat--active' : '' ?>"
                   href="<?= urlQuery('frontend/admin.php', ['status' => $__isActive ? '' : $__s, 'service' => $__service, 'q' => $__query]) ?>"
                   aria-label="<?= e($__s) ?>: <?= (int) $__count ?> заявок">
                    <p class="stat__value"><?= (int) $__count ?></p>
                    <p class="stat__label"><?= e($__s) ?></p>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- ============ Поиск и фильтры ============ -->
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

        <!-- ============ Быстрые фильтры по статусу ============ -->
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

        <!-- ============ Результат ============ -->
        <p class="result-line">
            <i class="bi bi-info-circle"></i>
            <span>
                Показано <strong><?= $__from ?>–<?= $__to ?></strong>
                из <strong><?= $__total ?></strong>&nbsp;<?= e(pluralForms($__total, 'заявка', 'заявки', 'заявок')) ?><?php
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
            <div class="table-wrap">
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
                        <tr>
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
                                <span class="table__name"><?= e($__app['appt_date']) ?></span>
                                <span class="table__sub">
                                    <i class="bi bi-clock"></i> <?= e($__app['appt_time']) ?>
                                </span>
                            </td>
                            <td data-label="Оплата"><?= e($__app['payment_method']) ?></td>
                            <td data-label="Статус">
                                <span class="badge <?= e(statusBadgeClass($__app['status'])) ?>">
                                    <?= e($__app['status']) ?>
                                </span>
                                <?php if ((int) $__app['has_review'] > 0): ?>
                                    <span class="table__flag" title="Есть отзыв">
                                        <i class="bi bi-chat-quote-fill"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Действие">
                                <?php if ($__next === []): ?>
                                    <span class="muted">—</span>
                                <?php else: ?>
                                    <form method="post" action="<?= url('backend/actions/admin_status.php') ?>"
                                          class="inline-form">
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

        <!-- ============ Пагинация ============ -->
        <?php if ($__pages > 1): ?>
            <nav class="pagination" aria-label="Страницы списка заявок">
                <?php if ($__page > 1): ?>
                    <a class="pagination__btn"
                       href="<?= urlQuery('frontend/admin.php', $__filters + ['page' => $__page - 1]) ?>"
                       aria-label="Предыдущая страница"><i class="bi bi-chevron-left"></i></a>
                <?php else: ?>
                    <span class="pagination__btn pagination__btn--off"><i class="bi bi-chevron-left"></i></span>
                <?php endif; ?>

                <?php for ($__p = 1; $__p <= $__pages; $__p++): ?>
                    <?php if ($__p === $__page): ?>
                        <span class="pagination__btn pagination__btn--current" aria-current="page"><?= $__p ?></span>
                    <?php else: ?>
                        <a class="pagination__btn"
                           href="<?= urlQuery('frontend/admin.php', $__filters + ['page' => $__p]) ?>"><?= $__p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($__page < $__pages): ?>
                    <a class="pagination__btn"
                       href="<?= urlQuery('frontend/admin.php', $__filters + ['page' => $__page + 1]) ?>"
                       aria-label="Следующая страница"><i class="bi bi-chevron-right"></i></a>
                <?php else: ?>
                    <span class="pagination__btn pagination__btn--off"><i class="bi bi-chevron-right"></i></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
