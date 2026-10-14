<?php
declare(strict_types=1);

/**
 * backend/config.php
 * Параметры подключения к БД и учётная запись администратора.
 * Здесь же хранятся справочные значения предметной области.
 */

/* ---------- Подключение к PostgreSQL ---------- */
const DB_HOST = 'localhost';
const DB_PORT = 5432;
const DB_NAME = 'vetclinic';
const DB_USER = 'postgres';
const DB_PASS = 'postgres';

/* ---------- Учётная запись администратора ---------- */
const ADMIN_LOGIN    = 'Admin';
const ADMIN_PASSWORD = 'VetLapa1';

/* ---------- Название системы ---------- */
const APP_NAME    = 'Хвост и Лапы';
const APP_TAGLINE = 'Ветеринарная клиника и онлайн-запись на приём';

/* ---------- Контакты клиники (для подвала и главной страницы) ---------- */
const CLINIC_PHONE = '+7(900)000-00-01';
const CLINIC_EMAIL = 'clinic@hvost-i-lapy.ru';

/* ---------- Статусы заявки ---------- */
const STATUS_NEW       = 'Новая';
const STATUS_CONFIRMED = 'Подтверждена';
const STATUS_DONE      = 'Приём завершён';
const STATUS_CANCELLED = 'Отменена';

/** Статус, при котором пользователю доступна форма отзыва. */
const STATUS_FOR_REVIEW = 'Приём завершён';

/* ---------- Способы оплаты ---------- */
const PAYMENT_CASH = 'Наличными';
const PAYMENT_CARD = 'Картой в клинике';

/* ---------- Справочники предметной области ---------- */

/** Виды животных — заполняются только из списка. */
const ANIMAL_TYPES = ['кошка', 'собака', 'грызун', 'птица', 'другое'];

/** Услуги клиники — заполняются только из списка. */
const SERVICES = ['первичный осмотр', 'вакцинация', 'стрижка когтей', 'УЗИ'];

/** Режим работы клиники. */
const CLINIC_OPEN  = '09:00';
const CLINIC_CLOSE = '20:00';

/** Количество заявок на одной странице панели администратора. */
const ADMIN_PAGE_SIZE = 5;

/** Разрешённые переходы между статусами заявки. */
const STATUS_TRANSITIONS = [
    STATUS_NEW       => [STATUS_CONFIRMED, STATUS_CANCELLED],
    STATUS_CONFIRMED => [STATUS_DONE, STATUS_CANCELLED],
    STATUS_DONE      => [],
    STATUS_CANCELLED => [],
];