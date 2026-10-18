<?php
declare(strict_types=1);

/**
 * backend/bootstrap.php
 * Точка инициализации приложения:
 *  - подключает параметры (config.php);
 *  - открывает сессию;
 *  - регистрирует автозагрузку классов из backend/classes/;
 *  - объявляет вспомогательные функции-хелперы.
 *
 * Подключается из index.php, всех страниц frontend/ и обработчиков backend/actions/.
 */

require_once __DIR__ . '/config.php';

/* ---------- Сессия ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Базовый URL приложения ----------
   Приложение может лежать в подкаталоге веб-корня (например /Practic/),
   поэтому базовый путь вычисляется, а не задаётся вручную.           */
if (!defined('BASE_URL')) {
    $fsRoot = str_replace('\\', '/', dirname(__DIR__));
    $base   = '';

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

    if (is_string($docRoot) && $docRoot !== '') {
        $resolved    = realpath($docRoot);
        $docRoot     = rtrim(str_replace('\\', '/', $resolved ?: $docRoot), '/');
        $realProject = str_replace('\\', '/', (realpath($fsRoot) ?: $fsRoot));

        if ($realProject !== $docRoot && str_starts_with($realProject, $docRoot . '/')) {
            $base = substr($realProject, strlen($docRoot) + 1);
        }
    }

    // Запасной вариант: убираем служебные подпапки из пути текущего скрипта.
    if ($base === '') {
        $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));

        foreach (['/backend/actions', '/frontend/partials', '/backend', '/frontend'] as $suffix) {
            if (str_ends_with($dir, $suffix)) {
                $dir  = substr($dir, 0, -strlen($suffix));
                $base = rtrim($dir, '/');
                break;
            }
        }
    }

    define('BASE_URL', rtrim($base, '/') . '/');
}

/* ---------- Автозагрузка классов ---------- */
spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

/* =====================================================================
   ХЕЛПЕРЫ
   ===================================================================== */

/** Экранирование пользовательского вывода. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Проверка: текущий запрос — POST. */
function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Запрос сделан скриптом через fetch, а не обычной отправкой формы.
 * Обработчики отвечают такому запросу JSON-ом, а не редиректом:
 * страница не перезагружается, статус меняется на месте.
 */
function isAjax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

/** Ответ JSON и выход — для запросов из app.js. */
function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Чтение обрезанного значения из POST. */
function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Flash-сообщения: записать. */
function flash(string $type, string $text): void
{
    $_SESSION['flash'][] = ['type' => $type, 'text' => $text];
}

/** Flash-сообщения: забрать и очистить. */
function flashTake(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/** Ошибки валидации текущей попытки: записать. */
function setErrors(array $errors): void
{
    $_SESSION['errors'] = $errors;
}

/** Ошибки валидации: забрать и очистить. */
function takeErrors(): array
{
    $items = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);
    return $items;
}

/**
 * Введённые значения: сохранить для повторного показа формы.
 * Поля паролей не сохраняются — иначе хеш или открытый пароль
 * оказался бы в сессии и мог бы утечь через журнал.
 */
function setOld(array $data): void
{
    unset($data['password'], $data['password_confirm']);
    $_SESSION['old'] = $data;
}

/** Введённые значения: забрать. */
function takeOld(): array
{
    $items = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return $items;
}

/** Значение поля из сохранённой формы. */
function oldValue(array $old, string $key, string $default = ''): string
{
    return (string) ($old[$key] ?? $default);
}

/**
 * Дата из базы (ГГГГ-ММ-ДД) в привычный пользователю вид: 15.06.2026.
 * В форме нативное поле type="date" требует ISO-формат,
 * а читать запись на странице удобнее в привычном.
 */
function formatDate(string $value): string
{
    if ($value === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

    return $date === false ? $value : $date->format('d.m.Y');
}

/** Собрать абсолютный URL страницы приложения. */
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/**
 * URL страницы с GET-параметрами.
 * Пустые значения отбрасываются, поэтому «фильтр не выбран»
 * не превращается в ?status= — ссылка остаётся чистой.
 *
 * @param array<string, string|int> $params
 */
function urlQuery(string $path, array $params = []): string
{
    $clean = [];

    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }

        $clean[$key] = $value;
    }

    return url($path) . ($clean === [] ? '' : '?' . http_build_query($clean));
}

/**
 * URL статического файла из frontend/assets/ с меткой версии.
 * Метка меняется при изменении файла, поэтому браузер
 * не показывает устаревшую версию CSS/JS из своего кеша.
 */
function asset(string $path): string
{
    $relative = 'frontend/assets/' . ltrim($path, '/');
    $file     = dirname(__DIR__) . '/' . $relative;
    $version  = is_file($file) ? '?v=' . filemtime($file) : '';

    return url($relative) . $version;
}

/** Редирект на страницу приложения. */
function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/** Текущий авторизованный пользователь (null — гость). */
function currentUser(): ?array
{
    static $cache = null;
    static $loaded = false;

    if ($loaded) {
        return $cache;
    }

    $loaded = true;
    $id = Auth::id();

    if ($id !== null) {
        $cache = User::findById($id);
    }

    return $cache;
}

/**
 * Иконка bootstrap-icons для типа уведомления.
 * Типы задаёт flash()/jsonResponse(), неизвестный трактуется как ошибка.
 */
function alertIcon(string $type): string
{
    return $type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
}

/** Человекочитаемое представление статуса заявки → CSS-класс бейджа. */
function statusBadgeClass(string $status): string
{
    return match ($status) {
        STATUS_NEW       => 'badge--new',
        STATUS_CONFIRMED => 'badge--confirmed',
        STATUS_DONE      => 'badge--done',
        STATUS_CANCELLED => 'badge--cancelled',
        default          => 'badge--new',
    };
}

/**
 * Иконка bootstrap-icons для вида животного.
 * Справочник ANIMAL_TYPES используется и при валидации, и здесь —
 * поэтому неизвестное значение не ломает страницу.
 */
function animalIcon(string $type): string
{
    return match ($type) {
        'кошка'    => 'bi-heart-fill',
        'собака'   => 'bi-house-heart',
        'грызун'   => 'bi-bug',
        'птица'    => 'bi-send',
        default    => 'bi-emoji-smile-fill',
    };
}

/** Иконка bootstrap-icons для услуги из справочника SERVICES. */
function serviceIcon(string $service): string
{
    return match ($service) {
        'первичный осмотр' => 'bi-clipboard2-pulse',
        'вакцинация'       => 'bi-shield-fill-check',
        'стрижка когтей'   => 'bi-scissors',
        'УЗИ'              => 'bi-activity',
        default            => 'bi-heart-pulse',
    };
}

/**
 * Русское склонение слова по числу.
 *
 * pluralForms(1, 'заявка', 'заявки', 'заявок')  → 'заявка'
 * pluralForms(3, 'заявка', 'заявки', 'заявок')  → 'заявки'
 * pluralForms(11, 'заявка', 'заявки', 'заявок') → 'заявок'
 */
function pluralForms(int $number, string $one, string $few, string $many): string
{
    $abs = abs($number) % 100;

    if ($abs > 10 && $abs < 20) {
        return $many;
    }

    return match ($abs % 10) {
        1       => $one,
        2, 3, 4 => $few,
        default => $many,
    };
}