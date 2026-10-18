<?php
declare(strict_types=1);

/**
 * Служебный скрипт: наполняет базу начальными данными.
 * Запуск:  php backend/database/seed.php
 *
 * Создаёт администратора (Admin / VetLapa1), владельца питомцев,
 * его питомцев и демонстрационные заявки в разных статусах и услугах.
 * Идемпотентен — повторный запуск не создаёт дубликаты.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// --- Администратор ---------------------------------------------------
if (User::loginExists(ADMIN_LOGIN)) {
    echo "Администратор «" . ADMIN_LOGIN . "» уже существует.\n";
} else {
    User::create(
        ADMIN_LOGIN,
        ADMIN_PASSWORD,
        'Соколова Мария Петровна',
        '+7(900)000-00-01',
        'admin@hvost-i-lapy.ru'
    );
    Database::execute("UPDATE users SET role = 'admin' WHERE login = :login", ['login' => ADMIN_LOGIN]);
    echo "Создан администратор: " . ADMIN_LOGIN . " / " . ADMIN_PASSWORD . "\n";
}

// --- Владелец питомцев ----------------------------------------------
if (!User::loginExists('petrov01')) {
    User::create('petrov01', 'vetpass1', 'Петров Сергей Николаевич', '+7(912)345-67-89', 'petrov@example.com');
    echo "Создан пользователь: petrov01 / vetpass1\n";
}

$owner = User::findByLogin('petrov01');

if ($owner === null) {
    exit("Не удалось получить владельца заявок.\n");
}

$uid = (int) $owner['id'];

// --- Питомцы владельца -----------------------------------------------
/**
 * Демонстрационный набор питомцев.
 * Кличка — ключ массива: добавление идемпотентно, а заявки ниже
 * ссылаются на питомцев по этой же кличке.
 */
$petrovPets = [
    'Барсик' => ['кошка',  'Метис',            '2021-04-12', 'Боится громких звуков'],
    'Шарик'  => ['собака', 'Лабрадор',         '2019-09-30', ''],
    'Рыжик'  => ['грызун', 'Сирийский хомяк',   '2024-02-18', ''],
    'Персик' => ['кошка',  'Персидская',        '2023-06-05', 'Аллергия на курицу'],
    'Луна'   => ['собака', 'Самоед',           '2022-11-21', ''],
    'Чижик'  => ['птица',  'Волнистый попугай', '2023-03-14', ''],
    'Ёжик'   => ['другое', 'Африканский ёж',   '2025-01-09', ''],
    'Тузик'  => ['кошка',  'Домашняя короткошёрстная', '2020-08-02', ''],
    // Питомец без заявок — показывает в профиле доступное удаление.
    'Мурзик' => ['кошка',  'Метис', '2025-11-30', 'Ещё ни разу не был в клинике'],
];

/** Кличка → id питомца. */
$petIds = [];

foreach ($petrovPets as $petName => [$animalType, $breed, $birthDate, $note]) {
    $existing = Database::fetchOne(
        'SELECT id FROM pets WHERE user_id = :user_id AND name = :name',
        ['user_id' => $uid, 'name' => $petName]
    );

    if ($existing !== null) {
        $petIds[$petName] = (int) $existing['id'];
        continue;
    }

    $petIds[$petName] = Pet::create($uid, $petName, $animalType, $breed, $birthDate, $note);
}

echo "Питомцев у petrov01: " . count($petIds) . "\n";

// --- Демонстрационные заявки ----------------------------------------
$empty = array_fill_keys(STATUSES, 0);

if (Application::countByStatus() === $empty) {
    // Завершённый приём с отзывом — чтобы было видно форму и блокировку отзыва.
    $doneId = Application::create($uid, $petIds['Барсик'], 'вакцинация', '2026-06-12', '10:30', PAYMENT_CARD);
    Application::changeStatus($doneId, STATUS_CONFIRMED);
    Application::changeStatus($doneId, STATUS_DONE);
    Review::create($doneId, $uid, 'Врач очень внимательный, кота не боялся.', 5);

    // Ещё один завершённый приём без отзыва — чтобы показать доступную форму.
    $doneId2 = Application::create($uid, $petIds['Шарик'], 'УЗИ', '2026-06-20', '15:00', PAYMENT_CASH);
    Application::changeStatus($doneId2, STATUS_CONFIRMED);
    Application::changeStatus($doneId2, STATUS_DONE);

    // Подтверждённая заявка — отзыв и отмена недоступны.
    $confId = Application::create($uid, $petIds['Рыжик'], 'первичный осмотр', '2026-07-05', '09:15', PAYMENT_CASH);
    Application::changeStatus($confId, STATUS_CONFIRMED);

    // Новые заявки разных услуг — для проверки фильтра и пагинации.
    Application::create($uid, $petIds['Персик'], 'стрижка когтей', '2026-07-18', '11:45', PAYMENT_CARD);
    Application::create($uid, $petIds['Луна'],   'вакцинация',     '2026-07-22', '13:00', PAYMENT_CASH);
    Application::create($uid, $petIds['Чижик'],  'первичный осмотр', '2026-07-29', '16:20', PAYMENT_CASH);
    Application::create($uid, $petIds['Ёжик'],   'УЗИ',            '2026-08-02', '09:45', PAYMENT_CARD);

    // Отменённая заявка — терминальный статус.
    $cancId = Application::create($uid, $petIds['Тузик'], 'стрижка когтей', '2026-07-01', '12:10', PAYMENT_CASH);
    Application::changeStatus($cancId, STATUS_CANCELLED);

    echo "Создано 8 демонстрационных заявок и 1 отзыв.\n";
}

echo "Готово. Пользователей в системе: " . User::count() . "\n";
echo "Питомцев: " . Pet::count() . "\n";
echo "Заявок: " . array_sum(Application::countByStatus()) . "\n";