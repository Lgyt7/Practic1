<?php
declare(strict_types=1);

/**
 * Служебный скрипт: наполняет базу начальными данными.
 * Запуск:  php backend/database/seed.php
 *
 * Создаёт администратора (Admin / VetLapa1), владельца питомца
 * и демонстрационные заявки в разных статусах и услугах.
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

// --- Владелец питомца -----------------------------------------------
if (!User::loginExists('petrov01')) {
    User::create('petrov01', 'vetpass1', 'Петров Сергей Николаевич', '+7(912)345-67-89', 'petrov@example.com');
    echo "Создан пользователь: petrov01 / vetpass1\n";
}

$owner = User::findByLogin('petrov01');

if ($owner === null) {
    exit("Не удалось получить владельца заявок.\n");
}

// --- Демонстрационные заявки ----------------------------------------
$empty = array_fill_keys([STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED], 0);

if (Application::countByStatus() === $empty) {
    $uid = (int) $owner['id'];

    // Завершённый приём с отзывом — чтобы было видно форму и блокировку отзыва.
    $doneId = Application::create($uid, 'Барсик', 'кошка', 'вакцинация',     '12.06.2026', '10:30', PAYMENT_CARD);
    Application::changeStatus($doneId, STATUS_CONFIRMED);
    Application::changeStatus($doneId, STATUS_DONE);
    Review::create($doneId, $uid, 'Врач очень внимательный, кота не боялся.', 5);

    // Ещё один завершённый приём без отзыва — чтобы показать доступную форму.
    $doneId2 = Application::create($uid, 'Шарик', 'собака', 'УЗИ', '20.06.2026', '15:00', PAYMENT_CASH);
    Application::changeStatus($doneId2, STATUS_CONFIRMED);
    Application::changeStatus($doneId2, STATUS_DONE);

    // Подтверждённая заявка — отзыв недоступен.
    $confId = Application::create($uid, 'Рыжик', 'грызун', 'первичный осмотр', '05.07.2026', '09:15', PAYMENT_CASH);
    Application::changeStatus($confId, STATUS_CONFIRMED);

    // Новые заявки разных услуг — для проверки фильтра и пагинации.
    Application::create($uid, 'Персик',   'кошка',  'стрижка когтей',   '18.07.2026', '11:45', PAYMENT_CARD);
    Application::create($uid, 'Луна',     'собака', 'вакцинация',       '22.07.2026', '13:00', PAYMENT_CASH);
    Application::create($uid, 'Чижик',    'птица',  'первичный осмотр', '29.07.2026', '16:20', PAYMENT_CASH);
    Application::create($uid, 'Ёжик',     'другое', 'УЗИ',              '02.08.2026', '09:45', PAYMENT_CARD);

    // Отменённая заявка — терминальный статус.
    $cancId = Application::create($uid, 'Тузик', 'кошка', 'стрижка когтей', '01.07.2026', '12:10', PAYMENT_CASH);
    Application::changeStatus($cancId, STATUS_CANCELLED);

    echo "Создано 8 демонстрационных заявок и 1 отзыв.\n";
}

echo "Готово. Пользователей в системе: " . User::count() . "\n";
echo "Заявок: " . array_sum(Application::countByStatus()) . "\n";