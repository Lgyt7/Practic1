<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_PORT = 5432;
const DB_NAME = 'vetclinic';
const DB_USER = 'postgres';
const DB_PASS = 'postgres';

const ADMIN_LOGIN    = 'Admin';
const ADMIN_PASSWORD = 'VetLapa1';

const APP_NAME    = 'Хвост и Лапы';
const APP_TAGLINE = 'Ветеринарная клиника и онлайн-запись на приём';

const CLINIC_PHONE = '+7(900)000-00-01';
const CLINIC_EMAIL = 'clinic@hvost-i-lapy.ru';

const STATUS_NEW       = 'Новая';
const STATUS_CONFIRMED = 'Подтверждена';
const STATUS_DONE      = 'Приём завершён';
const STATUS_CANCELLED = 'Отменена';

const STATUS_FOR_REVIEW = 'Приём завершён';

const STATUSES = [STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED];

const PAYMENT_CASH = 'Наличными';
const PAYMENT_CARD = 'Картой в клинике';

const ANIMAL_TYPES = ['кошка', 'собака', 'грызун', 'птица', 'другое'];

const SERVICES = ['первичный осмотр', 'вакцинация', 'стрижка когтей', 'УЗИ'];

const CLINIC_OPEN  = '09:00';
const CLINIC_CLOSE = '20:00';

const ADMIN_PAGE_SIZE = 5;

const STATUS_TRANSITIONS = [
    STATUS_NEW       => [STATUS_CONFIRMED, STATUS_CANCELLED],
    STATUS_CONFIRMED => [STATUS_DONE, STATUS_CANCELLED],
    STATUS_DONE      => [],
    STATUS_CANCELLED => [],
];
