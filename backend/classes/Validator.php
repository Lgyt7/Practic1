<?php
declare(strict_types=1);

/**
 * Класс валидации полей форм.
 *
 * Использование:
 *   $v = Validator::make($_POST)
 *          ->login()
 *          ->password('password');
 *   if ($v->fails()) { $errors = $v->errors(); }
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    private function __construct(private array $data)
    {
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    /** Есть ли ошибки. */
    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Значение поля или '' (с обрезкой пробелов). */
    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** Проверка на пустое значение. */
    public function required(string $field, string $label): self
    {
        if ($this->value($field) === '') {
            $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        return $this;
    }

    /** Логин: латиница и цифры, не менее 6 символов. */
    public function login(string $field = 'login', string $label = 'Логин'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!preg_match('/^[A-Za-z0-9]{6,}$/', $value)) {
            return $this->add(
                $field,
                "Логин должен состоять из латинских букв и цифр, быть не короче 6 символов."
            );
        }

        return $this;
    }

    /** Пароль: не менее 6 символов. */
    public function password(string $field = 'password', string $label = 'Пароль'): self
    {
        $value = $this->data[$field] ?? '';

        if (!is_string($value) || $value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (mb_strlen($value, 'UTF-8') < 6) {
            return $this->add($field, 'Пароль должен быть не короче 6 символов.');
        }

        return $this;
    }

    /** ФИО: кириллица и пробелы. */
    public function fullName(string $field = 'full_name', string $label = 'ФИО'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!preg_match('/^[А-Яа-яЁё]+(?:[ ]+[А-Яа-яЁё]+)+$/u', $value)) {
            return $this->add(
                $field,
                "ФИО указывается кириллицей и пробелами, например: Иванов Иван Иванович."
            );
        }

        return $this;
    }

    /** Телефон: формат +7(XXX)XXX-XX-XX. */
    public function phone(string $field = 'phone', string $label = 'Телефон'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $value)) {
            return $this->add($field, 'Телефон в формате +7(XXX)XXX-XX-XX, например +7(999)123-45-67.');
        }

        return $this;
    }

    /** Электронная почта. */
    public function email(string $field = 'email', string $label = 'Электронная почта'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->add($field, 'Электронная почта указана в неверном формате.');
        }

        return $this;
    }

    /** Дата приёма: текстовое поле в формате ДД.ММ.ГГГГ. */
    public function date(string $field = 'appt_date', string $label = 'Дата приёма'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        $date = DateTime::createFromFormat('!d.m.Y', $value);

        if (!$date || $date->format('d.m.Y') !== $value) {
            return $this->add($field, 'Дата приёма в формате ДД.ММ.ГГГГ, например 15.06.2026.');
        }

        return $this;
    }

    /** Время приёма: текстовое поле в формате ЧЧ:ММ. */
    public function time(string $field = 'appt_time', string $label = 'Время приёма'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $this->add($field, 'Время приёма в формате ЧЧ:ММ, например 10:30.');
        }

        // Приём возможен только в часы работы клиники.
        if ($value < CLINIC_OPEN || $value > CLINIC_CLOSE) {
            return $this->add(
                $field,
                sprintf('Клиника работает с %s до %s. Выберите время в этом диапазоне.', CLINIC_OPEN, CLINIC_CLOSE)
            );
        }

        return $this;
    }

    /** Значение выбирается из справочника. */
    public function inList(string $field, array $allowed, string $label): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Выберите значение поля «{$label}».");
        }

        if (!in_array($value, $allowed, true)) {
            return $this->add(
                $field,
                sprintf(
                    'Недопустимое значение поля «%s». Выберите одно из: %s.',
                    $label,
                    implode(', ', $allowed)
                )
            );
        }

        return $this;
    }

    /** Строка произвольной длины: обязательна, minLength…maxLength. */
    public function text(
        string $field,
        string $label,
        int $min = 1,
        int $max = 255,
        bool $multiline = false
    ): self {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        $length = mb_strlen($value, 'UTF-8');

        if ($length < $min || $length > $max) {
            return $this->add($field, "Поле «{$label}»: длина должна быть от {$min} до {$max} символов.");
        }

        if ($multiline && mb_strpos($value, "\n") !== false) {
            return $this->add($field, "Поле «{$label}» не должно содержать переносы строк.");
        }

        return $this;
    }

    /** Способ оплаты: наличными или картой в клинике. */
    public function paymentMethod(string $field = 'payment_method', string $label = 'Способ оплаты'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!in_array($value, [PAYMENT_CASH, PAYMENT_CARD], true)) {
            return $this->add($field, 'Способ оплаты: наличными или картой в клинике.');
        }

        return $this;
    }

    /** Совпадение пароля с подтверждением. */
    public function matches(string $field, string $other, string $message): self
    {
        if (($this->data[$field] ?? '') !== ($this->data[$other] ?? '')) {
            return $this->add($other, $message);
        }

        return $this;
    }

    /** Добавить ошибку вручную (например, при проверке уникальности в БД). */
    public function add(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;

        return $this;
    }
}