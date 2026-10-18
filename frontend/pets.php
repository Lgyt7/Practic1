<?php
declare(strict_types=1);

/**
 * frontend/pets.php
 * Профиль питомцев владельца: список, добавление и удаление.
 *
 * Питомец добавляется здесь один раз, а при записи на приём
 * выбирается из списка — вводить кличку заново не нужно.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

Auth::requireLogin();

if (Auth::isAdmin()) {
    redirect('frontend/admin.php');
}

$__pageTitle = 'Мои питомцы';
$__errors    = takeErrors();
$__old       = takeOld();

$__pets  = Pet::forUser(Auth::id());
$__value = static fn (string $field): string => oldValue($__old, $field);
$__error = static fn (string $field): ?string => $__errors[$field] ?? null;

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">
        <div class="page-head">
            <h1 class="page-title">
                <i class="bi bi-heart-fill page-title__icon"></i>Мои питомцы
            </h1>
            <a class="btn btn--primary" href="<?= url('frontend/application_create.php') ?>">
                <i class="bi bi-calendar-plus"></i> Записать питомца
            </a>
        </div>

        <div class="page-columns">
            <!-- ============ Список питомцев ============ -->
            <section class="pets">
                <h2 class="section-title">
                    <i class="bi bi-list-check"></i> Список
                    <span class="section-title__count"><?= count($__pets) ?></span>
                </h2>

                <?php if ($__pets === []): ?>
                    <div class="card card--empty">
                        <i class="bi bi-heart card--empty__icon"></i>
                        <p>Питомцев пока нет. Добавьте первого — и он появится в списке выбора при записи.</p>
                    </div>
                <?php else: ?>
                    <div class="pets__grid">
                        <?php foreach ($__pets as $__pet): ?>
                            <?php $__hasApps = (int) $__pet['apps_count'] > 0; ?>
                            <article class="card card--pet" data-reveal>
                                <div class="card--pet__head">
                                    <span class="card__icon card__icon--round">
                                        <i class="bi <?= e(animalIcon((string) $__pet['animal_type'])) ?>"></i>
                                    </span>
                                    <div>
                                        <h3 class="card__title"><?= e($__pet['name']) ?></h3>
                                        <p class="card__title-sub">
                                            <?= e(ucfirst((string) $__pet['animal_type'])) ?>
                                            <?php if (!empty($__pet['breed'])): ?>
                                                · <?= e($__pet['breed']) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <dl class="details">
                                    <?php if (!empty($__pet['birth_date'])): ?>
                                        <div class="details__row">
                                            <dt class="details__key">
                                                <i class="bi bi-cake2"></i> Дата рождения
                                            </dt>
                                            <dd class="details__value"><?= e(formatDate((string) $__pet['birth_date'])) ?></dd>
                                        </div>
                                    <?php endif; ?>

                                    <div class="details__row">
                                        <dt class="details__key">
                                            <i class="bi bi-calendar-check"></i> Записей
                                        </dt>
                                        <dd class="details__value">
                                            <?= (int) $__pet['apps_count'] ?>
                                            <?= e(pluralForms((int) $__pet['apps_count'], 'запись', 'записи', 'записей')) ?>
                                        </dd>
                                    </div>

                                    <?php if (!empty($__pet['note'])): ?>
                                        <div class="details__row">
                                            <dt class="details__key">
                                                <i class="bi bi-sticky"></i> Заметка
                                            </dt>
                                            <dd class="details__value"><?= e($__pet['note']) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                </dl>

                                <form method="post" action="<?= url('backend/actions/pet_delete.php') ?>"
                                      class="card--pet__actions" data-confirm
                                      data-confirm-text="Удалить «<?= e($__pet['name']) ?>» из профиля?">
                                    <input type="hidden" name="pet_id" value="<?= (int) $__pet['id'] ?>">

                                    <?php if ($__hasApps): ?>
                                        <button type="button" class="btn btn--sm btn--ghost is-locked"
                                                disabled
                                                title="Нельзя удалить: на питомца оформлены заявки">
                                            <i class="bi bi-lock-fill"></i> Есть заявки
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn--sm btn--danger">
                                            <i class="bi bi-trash"></i> Удалить
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ============ Форма добавления ============ -->
            <section class="card card--form card--form--side">
                <h2 class="section-title">
                    <i class="bi bi-plus-circle"></i> Добавить питомца
                </h2>

                <form method="post" action="<?= url('backend/actions/pet_create.php') ?>" novalidate data-validate>

                    <div class="field<?= $__error('name') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="name">Кличка</label>
                        <span class="field__control">
                            <i class="bi bi-heart-fill field__control-icon"></i>
                            <input class="field__input field__input--icon" type="text" id="name" name="name"
                                   value="<?= e($__value('name')) ?>" required maxlength="100" placeholder="Барсик">
                        </span>
                        <?php if ($__e = $__error('name')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $__error('animal_type') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="animal_type">Вид животного</label>
                        <select class="field__input" id="animal_type" name="animal_type" required>
                            <option value="">— выберите вид —</option>
                            <?php foreach (ANIMAL_TYPES as $__type): ?>
                                <option value="<?= e($__type) ?>" <?= $__value('animal_type') === $__type ? 'selected' : '' ?>>
                                    <?= e(ucfirst($__type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($__e = $__error('animal_type')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $__error('breed') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="breed">Порода</label>
                        <span class="field__control">
                            <i class="bi bi-tag field__control-icon"></i>
                            <input class="field__input field__input--icon" type="text" id="breed" name="breed"
                                   value="<?= e($__value('breed')) ?>" maxlength="100" placeholder="Метис">
                        </span>
                        <?php if ($__e = $__error('breed')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $__error('birth_date') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="birth_date">Дата рождения</label>
                        <span class="field__control">
                            <i class="bi bi-cake2 field__control-icon"></i>
                            <input class="field__input field__input--icon" type="date" id="birth_date"
                                   name="birth_date" value="<?= e($__value('birth_date')) ?>" max="<?= date('Y-m-d') ?>">
                        </span>
                        <p class="field__hint">Необязательно: помогает врачу подобрать процедуру.</p>
                        <?php if ($__e = $__error('birth_date')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $__error('note') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="note">Заметка</label>
                        <span class="field__control">
                            <i class="bi bi-sticky field__control-icon"></i>
                            <textarea class="field__input field__input--icon" id="note" name="note" rows="2"
                                      maxlength="<?= Pet::NOTE_MAX_LENGTH ?>"
                                      placeholder="Аллергии, характер, пожелания"><?= e($__value('note')) ?></textarea>
                        </span>
                        <?php if ($__e = $__error('note')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--primary btn--block">
                        <i class="bi bi-plus-lg"></i> Добавить питомца
                    </button>
                </form>
            </section>
        </div>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>