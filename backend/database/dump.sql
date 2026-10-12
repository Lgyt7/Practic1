-- =====================================================================
-- Портал «Хвост и Лапы» — ветеринарная клиника, онлайн-запись на приём
-- Модуль 1. Проектирование и разработка информационных систем
-- СУБД: PostgreSQL
-- Файл: backend/database/dump.sql
-- =====================================================================

BEGIN;

-- Таблицы удаляются в обратном порядке зависимостей.
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- users — пользователи портала (владельцы питомцев и администратор)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id         SERIAL       PRIMARY KEY,
    login      VARCHAR(50)  NOT NULL,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  NOT NULL,
    email      VARCHAR(120) NOT NULL,
    role       VARCHAR(10)  NOT NULL DEFAULT 'user',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT users_login_uniq  UNIQUE (login),
    CONSTRAINT users_email_uniq  UNIQUE (email),
    CONSTRAINT users_role_check  CHECK (role IN ('user', 'admin')),

    -- Для регистрации действует правило «латиница + цифры, не менее 6 символов».
    -- Логин администратора "Admin" задан условием задания и содержит 5 символов,
    -- поэтому для него сделано явное исключение.
    CONSTRAINT users_login_check
        CHECK (login = 'Admin' OR login ~ '^[A-Za-z0-9]{6,}$')
);

COMMENT ON TABLE  users              IS 'Пользователи портала';
COMMENT ON COLUMN users.password     IS 'Хеш пароля (password_hash), открытый пароль не хранится';
COMMENT ON COLUMN users.role         IS 'Роль: user — владелец питомца, admin — администратор';

-- ---------------------------------------------------------------------
-- applications — заявки на приём
-- ---------------------------------------------------------------------
CREATE TABLE applications (
    id              SERIAL        PRIMARY KEY,
    user_id         INTEGER       NOT NULL,
    pet_name        VARCHAR(100)  NOT NULL,
    animal_type     VARCHAR(50)   NOT NULL,
    service         VARCHAR(200)  NOT NULL,
    appt_date       DATE          NOT NULL,
    appt_time       VARCHAR(5)    NOT NULL,
    payment_method  VARCHAR(30)   NOT NULL,
    status          VARCHAR(20)   NOT NULL DEFAULT 'Новая',
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT applications_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT applications_time_check
        CHECK (appt_time ~ '^(?:[01][0-9]|2[0-3]):[0-5][0-9]$'),

    CONSTRAINT applications_payment_check
        CHECK (payment_method IN ('Наличными', 'Картой в клинике')),

    CONSTRAINT applications_status_check
        CHECK (status IN ('Новая', 'Подтверждена', 'Приём завершён', 'Отменена'))
);

CREATE INDEX applications_user_idx  ON applications (user_id);
CREATE INDEX applications_status_idx ON applications (status);

COMMENT ON TABLE  applications              IS 'Заявки владельцев питомцев на приём в клинику';
COMMENT ON COLUMN applications.user_id      IS 'Владелец питомца';
COMMENT ON COLUMN applications.status       IS 'Новая → Подтверждена → Приём завершён | Отменена';
COMMENT ON COLUMN applications.payment_method IS 'Наличными или Картой в клинике';

-- ---------------------------------------------------------------------
-- reviews — отзывы о работе клиники (только по завершённым приёмам)
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id             SERIAL      PRIMARY KEY,
    application_id INTEGER     NOT NULL,
    user_id        INTEGER     NOT NULL,
    review         TEXT        NOT NULL,
    rating         SMALLINT,
    created_at     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT reviews_application_fk
        FOREIGN KEY (application_id) REFERENCES applications (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT reviews_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Один отзыв на одну заявку
    CONSTRAINT reviews_application_uniq UNIQUE (application_id),

    CONSTRAINT reviews_rating_check
        CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)
);

CREATE INDEX reviews_user_idx ON reviews (user_id);

COMMENT ON TABLE  reviews               IS 'Отзывы о работе клиники';
COMMENT ON COLUMN reviews.application_id IS 'Заявка, по которой оставлен отзыв';
COMMENT ON COLUMN reviews.rating        IS 'Оценка от 1 до 5 либо NULL';

COMMIT;