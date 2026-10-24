<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function isAjax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function flash(string $type, string $text): void
{
    $_SESSION['flash'][] = ['type' => $type, 'text' => $text];
}

function flashTake(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function setErrors(array $errors): void
{
    $_SESSION['errors'] = $errors;
}

function takeErrors(): array
{
    $items = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);
    return $items;
}

function setOld(array $data): void
{
    unset($data['password'], $data['password_confirm']);
    $_SESSION['old'] = $data;
}

function takeOld(): array
{
    $items = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return $items;
}

function oldValue(array $old, string $key, string $default = ''): string
{
    return (string) ($old[$key] ?? $default);
}

function formatDate(string $value): string
{
    if ($value === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

    return $date === false ? $value : $date->format('d.m.Y');
}

function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

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

function asset(string $path): string
{
    $relative = 'frontend/assets/' . ltrim($path, '/');
    $file     = dirname(__DIR__) . '/' . $relative;
    $version  = is_file($file) ? '?v=' . filemtime($file) : '';

    return url($relative) . $version;
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

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

function alertIcon(string $type): string
{
    return $type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
}

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
