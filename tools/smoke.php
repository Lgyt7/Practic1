<?php
declare(strict_types=1);

const BASE = 'http://127.0.0.1:8088';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        require_once __DIR__ . '/../backend/config.php';

        $pdo = new PDO(
            'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    return $pdo;
}

function petId(string $name, string $login = 'petrov01'): int
{
    $stmt = db()->prepare(
        'SELECT p.id FROM pets p JOIN users u ON u.id = p.user_id
          WHERE p.name = :name AND u.login = :login'
    );
    $stmt->execute(['name' => $name, 'login' => $login]);

    return (int) ($stmt->fetchColumn() ?: 0);
}

function lastApplicationId(): int
{
    return (int) db()->query('SELECT MAX(id) FROM applications')->fetchColumn();
}

function resetDatabase(string $root): void
{
    $dump = file_get_contents($root . '/backend/database/dump.sql');

    if ($dump === false) {
        exit('Не удалось прочитать dump.sql' . PHP_EOL);
    }

    db()->exec($dump);

    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/backend/database/seed.php') . ' 2>&1');

    echo 'База пересоздана, демо-данные загружены.' . PHP_EOL;
}

$failures = 0;

function request(string $path, ?string $jar = null, ?array $post = null, bool $follow = true): array
{
    $ch = curl_init(BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 15,
    ]);

    if ($jar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [$code, (string) $body];
}

function section(string $title): void
{
    echo PHP_EOL . $title . PHP_EOL . str_repeat('-', mb_strlen($title)) . PHP_EOL;
}

function page(string $path, string $label, ?string $jar = null): string
{
    global $failures;

    [$code, $body] = request($path, $jar);

    printf("  %-42s HTTP %d  %6d байт%s", $label, $code, mb_strlen($body), PHP_EOL);

    if ($code !== 200 || $body === '') {
        $failures++;
        echo "    FAIL: страница не отдалась" . PHP_EOL;
    }

    return $body;
}

function has(string $html, string $needle, string $label): void
{
    global $failures;

    if (str_contains($html, $needle)) {
        echo "    OK    $label" . PHP_EOL;
    } else {
        $failures++;
        echo "    FAIL  $label" . PHP_EOL;
    }
}

function lacks(string $html, string $needle, string $label): void
{
    global $failures;

    if (str_contains($html, $needle)) {
        $failures++;
        echo "    FAIL  $label" . PHP_EOL;
    } else {
        echo "    OK    $label" . PHP_EOL;
    }
}

$root = dirname(__DIR__);

resetDatabase($root);

$probe = request('/index.php');

if ($probe[0] !== 200) {
    exit('Сервер не отвечает по адресу ' . BASE
        . PHP_EOL . 'Запустите его в другом окне: php -S 127.0.0.1:8088 -t .' . PHP_EOL);
}

section('Гость');
$index = page('/index.php', 'index.php — слайдер');
$login = page('/frontend/login.php', 'login.php');
$reg   = page('/frontend/register.php', 'register.php');

echo "  -- содержимое главной --" . PHP_EOL;
has($index, 'slide-1.jpg', 'слайдер: изображение 1 из 4');
has($index, 'slide-4.jpg', 'слайдер: изображение 4 из 4');
has($index, 'slider__icon', 'слайдер: иконка-эмблема');
has($index, 'bi-shield-fill-check', 'слайдер: иконка «Вакцинация» существует');
has($index, 'data-slider-prev', 'слайдер: кнопка «назад»');
has($index, 'data-slider-next', 'слайдер: кнопка «вперёд»');
has($index, 'card__icon', 'главная: иконки карточек преимуществ');
has($index, 'Отзывы владельцев', 'главная: блок отзывов');

has($login, 'bi-person field__control-icon', 'вход: иконка поля логина');
has($reg, 'bi-lock field__control-icon', 'регистрация: иконка поля пароля');

section('Владелец питомца');
$jar = sys_get_temp_dir() . '/smoke_user.cookie';
@unlink($jar);

request('/backend/actions/login.php', $jar, ['login' => 'petrov01', 'password' => 'vetpass1']);

$apps = page('/frontend/applications.php', 'applications.php — мои записи', $jar);
$form = page('/frontend/application_create.php', 'application_create.php', $jar);
$pets = page('/frontend/pets.php', 'pets.php — профиль питомцев', $jar);

echo "  -- содержимое кабинета --" . PHP_EOL;
has($apps, 'details__row', 'карточка заявки: список «ключ — значение»');
has($apps, 'Оставить отзыв', 'форма отзыва доступна после «Приём завершён»');
has($apps, 'Ваш отзыв', 'у одной заявки отзыв уже есть');
has($apps, 'Отменить заявку', 'у заявки со статусом «Новая» есть кнопка отмены');
has($apps, 'data-confirm', 'отмена заявки спрашивает подтверждение');
has($apps, 'data-reveal', 'карточки заявок появляются с анимацией');

echo "  -- содержимое формы записи --" . PHP_EOL;
has($form, 'value="вакцинация"', 'форма записи: услуга из справочника');
has($form, 'Барсик', 'форма записи: питомец выбирается из профиля');
has($form, 'type="date"', 'форма записи: нативный выбор даты');
has($form, 'type="time"', 'форма записи: нативный выбор времени');
has($form, 'min="' . CLINIC_OPEN . '"', 'форма записи: нижняя граница часов работы');
has($form, 'max="' . CLINIC_CLOSE . '"', 'форма записи: верхняя граница часов работы');
has($form, 'Клиника работает с 09:00 до 20:00', 'форма записи: подсказка о часах работы');

echo "  -- профиль питомцев --" . PHP_EOL;
has($pets, 'Записей', 'профиль: показано число записей питомца');
has($pets, 'Есть заявки', 'профиль: удаление питомца с заявками заблокировано');
has($pets, 'data-reveal', 'профиль: карточки питомцев появляются с анимацией');

section('Администратор');
$ajar = sys_get_temp_dir() . '/smoke_admin.cookie';
@unlink($ajar);

request('/backend/actions/login.php', $ajar, ['login' => 'Admin', 'password' => 'VetLapa1']);

$adm  = page('/frontend/admin.php', 'admin.php — без фильтров', $ajar);
$st   = page('/frontend/admin.php?status=' . rawurlencode('Новая'), 'admin.php — фильтр по статусу', $ajar);
$sv   = page('/frontend/admin.php?service=' . rawurlencode('УЗИ'), 'admin.php — фильтр по услуге', $ajar);
$q    = page('/frontend/admin.php?q=' . rawurlencode('Барс'), 'admin.php — поиск по кличке', $ajar);
$qf   = page('/frontend/admin.php?q=' . rawurlencode('Петров'), 'admin.php — поиск по ФИО', $ajar);
$p1   = page('/frontend/admin.php?page=1', 'admin.php — страница 1', $ajar);
$p2   = page('/frontend/admin.php?page=2', 'admin.php — страница 2', $ajar);
$bad  = page('/frontend/admin.php?status=bogus&service=nope&page=-3', 'admin.php — мусорные параметры', $ajar);
$like = page('/frontend/admin.php?q=%25', 'admin.php — «%» в поиске', $ajar);
$none = page('/frontend/admin.php?q=' . rawurlencode('ТакогоПитомцаНет'), 'admin.php — пустой результат', $ajar);

echo "  -- фильтры и пагинация --" . PHP_EOL;
has($adm, 'Панель администратора', 'админка: заголовок');
has($adm, 'admin-filters', 'админка: форма поиска и фильтров');
has($adm, 'data-label="Владелец"', 'админка: подписи ячеек для мобильной вёрстки');
has($adm, 'name="return"', 'админка: возврат с фильтрами после смены статуса');
has($adm, 'Показано', 'админка: строка «показано N–M из K»');
has($adm, 'pagination__btn', 'админка: пагинация');

has($st, 'Персик', 'фильтр по статусу: показана заявка «Новая»');
lacks($st, 'Барсик', 'фильтр по статусу: «Приём завершён» отфильтрован');

has($sv, 'Шарик', 'фильтр по услуге: остались заявки на «УЗИ»');
lacks($sv, 'Барсик', 'фильтр по услуге: «вакцинация» отфильтрована');

has($q, 'Барсик', 'поиск по кличке найден');
lacks($q, 'Шарик', 'поиск по кличке отсекает лишние записи');

has($qf, 'Персик', 'поиск по ФИО владельца найден');
has($qf, 'Показано', 'поиск по ФИО: верная пагинация результата');
has($like, 'Заявок не найдено', '«%» в поиске не превращается в шаблон LIKE');
has($none, 'Заявок не найдено', 'пустой результат оформлен');
has($bad, 'Панель администратора', 'мусорные параметры не ломают страницу');
has($p2, 'pagination__btn--current', 'пагинация: вторая страница открыта');

section('Создание заявки: валидация');

$jar2 = sys_get_temp_dir() . '/smoke_user2.cookie';
@unlink($jar2);
request('/backend/actions/login.php', $jar2, ['login' => 'petrov01', 'password' => 'vetpass1']);

$barshik = petId('Барсик');

request('/backend/actions/application_create.php', $jar2, [
    'pet_id'         => (string) $barshik,
    'service'        => 'Массаж',
    'appt_date'      => '2020-01-01',
    'appt_time'      => '07:30',
    'payment_method' => 'Наличными',
], false);

$bad = page('/frontend/application_create.php', 'после отказа валидации', $jar2);
has($bad, 'Недопустимое значение поля «Услуга»', 'ошибка: услуга не из справочника');
has($bad, 'дата не может быть в прошлом', 'ошибка: дата приёма в прошлом');
has($bad, 'Клиника работает с 09:00 до 20:00', 'ошибка: время вне часов работы');
has($bad, 'field--invalid', 'невалидные поля подсвечены');
has($bad, 'value="' . $barshik . '"', 'выбранный питомец сохранён в форме');

request('/backend/actions/application_create.php', $jar2, [
    'pet_id'         => '999999',
    'service'        => 'УЗИ',
    'appt_date'      => '2026-12-20',
    'appt_time'      => '18:45',
    'payment_method' => 'Картой в клинике',
], false);

$foreign = page('/frontend/application_create.php', 'чужой питомец', $jar2);
has($foreign, 'Выберите питомца из своего профиля', 'ошибка: питомец чужого профиля отклонён');

request('/backend/actions/application_create.php', $jar2, [
    'pet_id'         => (string) $barshik,
    'service'        => 'УЗИ',
    'appt_date'      => '2026-12-20',
    'appt_time'      => '18:45',
    'payment_method' => 'Картой в клинике',
], false);

$ok = page('/frontend/applications.php', 'после успешной отправки', $jar2);
has($ok, 'Заявка отправлена администратору', 'всплывающее сообщение об успешной отправке');
has($ok, 'Барсик', 'заявка появилась в личном кабинете');
has($ok, 'Новая', 'новой заявке присвоен статус «Новая»');

section('Отмена заявки владельцем');

$newId = lastApplicationId();

has($ok, 'Отменить заявку', 'у свежей заявки есть кнопка отмены');

request('/backend/actions/application_cancel.php', $jar2, ['application_id' => (string) $newId], false);

$cancelled = page('/frontend/applications.php', 'после отмены', $jar2);
has($cancelled, 'Заявка №' . $newId . ' отменена', 'отмена: сообщение об успехе');
has($cancelled, 'badge--cancelled', 'отмена: статус стал «Отменена»');

request('/backend/actions/application_cancel.php', $jar2, ['application_id' => (string) $newId], false);

$again = page('/frontend/applications.php', 'повторная отмена', $jar2);
has($again, 'Отменить можно только заявку со статусом «Новая»', 'отмена: повторно отменить нельзя');

request('/backend/actions/application_cancel.php', $jar2, ['application_id' => '3'], false);

$confirmed = page('/frontend/applications.php', 'отмена подтверждённой', $jar2);
has($confirmed, 'Отменить можно только заявку со статусом «Новая»', 'отмена: подтверждённую заявку нельзя');

request('/backend/actions/application_cancel.php', $jar2, ['application_id' => '999999'], false);

$missing = page('/frontend/applications.php', 'отмена несуществующей', $jar2);
has($missing, 'Заявка не найдена', 'отмена: несуществующая заявка отклонена');

section('Профиль питомцев');

$jar4 = sys_get_temp_dir() . '/smoke_user4.cookie';
@unlink($jar4);
request('/backend/actions/login.php', $jar4, ['login' => 'petrov01', 'password' => 'vetpass1']);

request('/backend/actions/pet_delete.php', $jar4, ['pet_id' => (string) $barshik], false);

$busy = page('/frontend/pets.php', 'удаление питомца с заявками', $jar4);
has($busy, 'Нельзя удалить питомца', 'профиль: питомца с заявками удалить нельзя');

request('/backend/actions/pet_create.php', $jar4, [
    'name'        => 'Тестовый',
    'animal_type' => 'птица',
    'breed'       => 'Волнистый попугай',
    'birth_date'  => '2024-05-10',
    'note'        => 'Опытные владельцы',
], false);

$added = page('/frontend/pets.php', 'после добавления питомца', $jar4);
has($added, 'Питомец «Тестовый» добавлен', 'профиль: сообщение об успешном добавлении');
has($added, 'Тестовый', 'профиль: новый питомец в списке');
has($added, '10.05.2024', 'профиль: дата рождения выведена в привычном виде');

request('/backend/actions/pet_create.php', $jar4, [
    'name'        => 'Тестовый',
    'animal_type' => 'птица',
    'breed'       => '',
    'birth_date'  => '',
    'note'        => '',
], false);

$duplicate = page('/frontend/pets.php', 'повторное добавление', $jar4);
has($duplicate, 'уже есть в вашем профиле', 'профиль: дубль клички отклонён');

request('/backend/actions/pet_create.php', $jar4, [
    'name'        => 'Будущий',
    'animal_type' => 'кошка',
    'breed'       => '',
    'birth_date'  => '2099-01-01',
    'note'        => '',
], false);

$future = page('/frontend/pets.php', 'дата рождения из будущего', $jar4);
has($future, 'не может быть в будущем', 'профиль: дата рождения из будущего отклонена');

request('/backend/actions/pet_delete.php', $jar4, ['pet_id' => (string) petId('Тестовый')], false);

$removed = page('/frontend/pets.php', 'после удаления питомца', $jar4);
has($removed, 'Питомец удалён из профиля', 'профиль: питомец без заявок удаляется');
lacks($removed, 'Тестовый', 'профиль: удалённый питомец исчез из списка');

section('Смена статуса администратором');

$ajar2 = sys_get_temp_dir() . '/smoke_admin2.cookie';
@unlink($ajar2);
request('/backend/actions/login.php', $ajar2, ['login' => 'Admin', 'password' => 'VetLapa1']);

request('/backend/actions/admin_status.php', $ajar2, [
    'application_id' => '4',
    'status'         => 'Приём завершён',
    'return'         => 'frontend/admin.php',
], false);

$rejected = page('/frontend/admin.php', 'после недопустимого перехода', $ajar2);
has($rejected, 'Недопустимый переход: «Новая» → «Приём завершён»', 'переход через ступеньку отклонён на сервере');
has($rejected, 'badge--new', 'статус заявки не изменился');

request('/backend/actions/admin_status.php', $ajar2, [
    'application_id' => '4',
    'status'         => 'Подтверждена',
    'return'         => 'frontend/admin.php?status=' . rawurlencode('Подтверждена'),
], false);

$accepted = page('/frontend/admin.php?status=' . rawurlencode('Подтверждена'), 'после допустимого перехода', $ajar2);
has($accepted, 'Статус заявки №4 обновлён', 'всплывающее сообщение об успешной смене статуса');
has($accepted, 'Персик', 'фильтр сохранён после смены статуса');
has($accepted, 'badge--confirmed', 'статус в таблице обновился');

section('Регистрация');

$guest = sys_get_temp_dir() . '/smoke_guest.cookie';
@unlink($guest);

request('/backend/actions/register.php', $guest, [
    'login'     => 'иван',
    'full_name' => 'Иванов1 Иван',
    'phone'     => '12345',
    'email'     => 'не-почта',
    'password'  => '123',
], false);

$regErr = page('/frontend/register.php', 'после отказа валидации', $guest);
has($regErr, 'Логин должен состоять из латинских букв и цифр', 'ошибка: недопустимый логин');
has($regErr, 'ФИО указывается кириллицей и пробелами', 'ошибка: недопустимое ФИО');
has($regErr, 'Телефон в формате +7(XXX)XXX-XX-XX', 'ошибка: недопустимый телефон');
has($regErr, 'Электронная почта указана в неверном формате', 'ошибка: недопустимая почта');
has($regErr, 'Пароль должен быть не короче 6 символов', 'ошибка: короткий пароль');
has($regErr, 'value="Иванов1 Иван"', 'введённые значения сохранены в форме');

request('/backend/actions/register.php', $guest, [
    'login'     => 'petrov01',
    'full_name' => 'Новый Иванов Иван',
    'phone'     => '+7(999)111-22-33',
    'email'     => 'newuser@example.com',
    'password'  => 'vetpass2',
], false);

$dup = page('/frontend/register.php', 'занятый логин', $guest);
has($dup, 'уже зарегистрирован', 'ошибка: логин не уникален');

request('/backend/actions/register.php', $guest, [
    'login'     => 'novikov02',
    'full_name' => 'Новиков Пётр Иванович',
    'phone'     => '+7(999)111-22-33',
    'email'     => 'novikov@example.com',
    'password'  => 'vetpass2',
], false);

$fresh = page('/frontend/login.php', 'после успешной регистрации', $guest);
has($fresh, 'Регистрация выполнена', 'регистрация успешна — предложен вход');
has($fresh, 'name="login"', 'открыта форма входа');

request('/backend/actions/login.php', $guest, ['login' => 'novikov02', 'password' => 'vetpass2'], false);
$inbox = page('/frontend/applications.php', 'кабинет нового пользователя', $guest);
has($inbox, 'Заявок пока нет', 'новый пользователь вошёл в систему');
has($inbox, 'novikov02', 'логин нового пользователя в шапке');
lacks($inbox, 'Барсик', 'новому пользователю не показывают чужие записи');

$noPets = page('/frontend/application_create.php', 'запись без питомцев', $guest);
has($noPets, 'добавьте питомца в профиль', 'без питомцев форма записи объясняет, что делать');
lacks($noPets, 'name="service"', 'без питомцев форма записи не показывается');

$emptyPets = page('/frontend/pets.php', 'пустой профиль питомцев', $guest);
has($emptyPets, 'Питомцев пока нет', 'пустой профиль оформлен пустым состоянием');
lacks($emptyPets, 'card--pet', 'в профиле нет чужих карточек питомцев');

request('/backend/actions/application_create.php', $guest, [
    'pet_id'         => (string) $barshik,
    'service'        => 'УЗИ',
    'appt_date'      => '2026-12-20',
    'appt_time'      => '18:45',
    'payment_method' => 'Наличными',
], false);

$stolen = page('/frontend/application_create.php', 'запись на чужого питомца', $guest);
has($stolen, 'Выберите питомца из своего профиля', 'запись на чужого питомца отклонена');

section('Отзывы');

$jar3 = sys_get_temp_dir() . '/smoke_user3.cookie';
@unlink($jar3);
request('/backend/actions/login.php', $jar3, ['login' => 'petrov01', 'password' => 'vetpass1']);

request('/backend/actions/review_create.php', $jar3, [
    'application_id' => '4',
    'review'         => 'Отзыв заранее',
    'rating'         => '5',
], false);

$tooEarly = page('/frontend/applications.php', 'отзыв до приёма', $jar3);
has($tooEarly, 'Отзыв можно оставить только после завершения приёма', 'отзыв отклонён при статусе «Новая»');
has($tooEarly, 'Отзыв заранее', 'текст отзыва сохранён для повтора');

request('/backend/actions/review_create.php', $jar3, [
    'application_id' => '1',
    'review'         => 'Второй отзыв',
    'rating'         => '4',
], false);

$twice = page('/frontend/applications.php', 'повторный отзыв', $jar3);
has($twice, 'Отзыв по этой заявке уже оставлен', 'второй отзыв по заявке отклонён');

request('/backend/actions/review_create.php', $jar3, [
    'application_id' => '2',
    'review'         => 'Врач подробно объяснил, что происходит с питомцем.',
    'rating'         => '4',
], false);

$after = page('/frontend/applications.php', 'после отзыва', $jar3);
has($after, 'Спасибо за отзыв', 'всплывающее сообщение после отзыва');
has($after, 'подробно объяснил', 'отзыв сохранён и показан владельцу');

section('Права доступа');
$ch = curl_init(BASE . '/frontend/admin.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => 15,
]);
curl_exec($ch);
printf("  %-42s HTTP %d%s", 'admin.php без входа → редирект', (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), PHP_EOL);
curl_close($ch);

section('Итог');
echo $failures === 0
    ? '  Все проверки пройдены.' . PHP_EOL
    : "  Провалено проверок: $failures" . PHP_EOL;

exit($failures === 0 ? 0 : 1);
