<?php
declare(strict_types=1);

/**
 * Служебный скрипт: наполняет базу начальными данными.
 * Запуск:  php backend/database/seed.php
 *
 * Создаёт администратора (Admin / VetLapa1) и демонстрационные записи.
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

// --- Демонстрационные заявки ----------------------------------------
if ($owner !== null && Application::countByStatus() === array_fill_keys(
    [STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED],
    0
)) {
    // Завершённый приём — чтобы было видно рабочее поле отзыва.
    $doneId = Application::create(
        (int) $owner['id'],
        'Барсик',
        'Кошка',
        'Вакцинация от бешенства',
        '12.06.2026',
        '10:30',
        PAYMENT_CARD
    );
    Application::changeStatus($doneId, STATUS_CONFIRMED);
    Application::changeStatus($doneId, STATUS_DONE);

    Review::create($doneId, (int) $owner['id'], 'Врач очень внимательный, кота не боялся.', 5);

    Application::create(
        (int) $owner['id'],
        'Шарик',
        'Собака',
        'Стрижка и обработка когтей',
        '05.07.2026',
        '15:00',
        PAYMENT_CASH
    );
    Application::create(
        (int) $owner['id'],
        'Рыжик',
        'Хомяк',
        'Консультация орнитолога',
        '20.07.2026',
        '09:15',
        PAYMENT_CASH
    );

    echo "Создано 3 демонстрационные заявки и 1 отзыв.\n";
}

echo "Готово. Обычных пользователей в системе: " . User::count() . "\n";