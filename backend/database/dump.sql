BEGIN;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS pets;
DROP TABLE IF EXISTS users;

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

    CONSTRAINT users_login_check
        CHECK (login = 'Admin' OR login ~ '^[A-Za-z0-9]{6,}$')
);

COMMENT ON TABLE  users              IS 'Пользователи портала';
COMMENT ON COLUMN users.password     IS 'Хеш пароля (password_hash), открытый пароль не хранится';
COMMENT ON COLUMN users.role         IS 'Роль: user — владелец питомца, admin — администратор';

CREATE TABLE pets (
    id          SERIAL       PRIMARY KEY,
    user_id     INTEGER      NOT NULL,
    name        VARCHAR(100) NOT NULL,
    animal_type VARCHAR(50)  NOT NULL,
    breed       VARCHAR(100),
    birth_date  DATE,
    note        VARCHAR(255),
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pets_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT pets_user_name_uniq UNIQUE (user_id, name),

    CONSTRAINT pets_name_check
        CHECK (name ~ '^.{1,100}$'),

    CONSTRAINT pets_animal_type_check
        CHECK (animal_type IN ('кошка', 'собака', 'грызун', 'птица', 'другое')),

    CONSTRAINT pets_birth_date_check
        CHECK (birth_date IS NULL OR birth_date <= CURRENT_DATE)
);

CREATE INDEX pets_user_idx ON pets (user_id);

COMMENT ON TABLE  pets             IS 'Питомцы владельцев: заполняются в профиле один раз';
COMMENT ON COLUMN pets.user_id    IS 'Владелец питомца';
COMMENT ON COLUMN pets.name       IS 'Кличка питомца, уникальна в пределах владельца';
COMMENT ON COLUMN pets.animal_type IS 'Вид животного из справочника: кошка, собака, грызун, птица, другое';
COMMENT ON COLUMN pets.birth_date IS 'Дата рождения питомца либо NULL';
COMMENT ON COLUMN pets.note       IS 'Заметка владельца о питомце: аллергии, характер, пожелания';

CREATE TABLE applications (
    id              SERIAL        PRIMARY KEY,
    user_id         INTEGER       NOT NULL,
    pet_id          INTEGER       NOT NULL,
    service         VARCHAR(200)  NOT NULL,
    appt_date       DATE          NOT NULL,
    appt_time       VARCHAR(5)    NOT NULL,
    payment_method  VARCHAR(30)   NOT NULL,
    status          VARCHAR(20)   NOT NULL DEFAULT 'Новая',
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT applications_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT applications_pet_fk
        FOREIGN KEY (pet_id) REFERENCES pets (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT applications_time_check
        CHECK (appt_time ~ '^(?:[01][0-9]|2[0-3]):[0-5][0-9]$'),

    CONSTRAINT applications_time_range_check
        CHECK (appt_time >= '09:00' AND appt_time <= '20:00'),

    CONSTRAINT applications_service_check
        CHECK (service IN ('первичный осмотр', 'вакцинация', 'стрижка когтей', 'УЗИ')),

    CONSTRAINT applications_payment_check
        CHECK (payment_method IN ('Наличными', 'Картой в клинике')),

    CONSTRAINT applications_status_check
        CHECK (status IN ('Новая', 'Подтверждена', 'Приём завершён', 'Отменена'))
);

CREATE INDEX applications_user_idx  ON applications (user_id);
CREATE INDEX applications_status_idx ON applications (status);
CREATE INDEX applications_pet_idx   ON applications (pet_id);

COMMENT ON TABLE  applications              IS 'Заявки владельцев питомцев на приём в клинику';
COMMENT ON COLUMN applications.user_id      IS 'Владелец питомца';
COMMENT ON COLUMN applications.pet_id       IS 'Питомец из профиля владельца';
COMMENT ON COLUMN applications.service     IS 'Услуга из справочника: первичный осмотр, вакцинация, стрижка когтей, УЗИ';
COMMENT ON COLUMN applications.appt_time   IS 'Время приёма ЧЧ:ММ в пределах работы клиники 09:00–20:00';
COMMENT ON COLUMN applications.status       IS 'Новая → Подтверждена → Приём завершён | Отменена. «Новую» заявку отменяет и владелец';
COMMENT ON COLUMN applications.payment_method IS 'Наличными или Картой в клинике';

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

    CONSTRAINT reviews_application_uniq UNIQUE (application_id),

    CONSTRAINT reviews_rating_check
        CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)
);

CREATE INDEX reviews_user_idx ON reviews (user_id);

COMMENT ON TABLE  reviews               IS 'Отзывы о работе клиники';
COMMENT ON COLUMN reviews.application_id IS 'Заявка, по которой оставлен отзыв';
COMMENT ON COLUMN reviews.rating        IS 'Оценка от 1 до 5 либо NULL';

COMMIT;
