<?php
declare(strict_types=1);

final class Validator
{

    private array $errors = [];

    private function __construct(private array $data)
    {
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

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

    public static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    public function date(string $field = 'appt_date', string $label = 'Дата приёма'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!self::isDate($value)) {
            return $this->add($field, "Поле «{$label}»: ожидается дата в формате ГГГГ-ММ-ДД.");
        }

        return $this;
    }

    public function optionalDate(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this;
        }

        if (!self::isDate($value)) {
            return $this->add($field, "Поле «{$label}»: ожидается дата в формате ГГГГ-ММ-ДД.");
        }

        return $this;
    }

    public function notInPast(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value === '' || !self::isDate($value)) {
            return $this;
        }

        if ($value < date('Y-m-d')) {
            return $this->add($field, "Поле «{$label}»: дата не может быть в прошлом.");
        }

        return $this;
    }

    public function time(string $field = 'appt_time', string $label = 'Время приёма'): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Поле «{$label}» обязательно для заполнения.");
        }

        if (!Schedule::isValidTime($value)) {
            return $this->add($field, "Поле «{$label}»: ожидается время в формате ЧЧ:ММ, например 10:30.");
        }

        if (!Schedule::isOpenTime($value)) {
            return $this->add($field, Schedule::outsideReason());
        }

        return $this;
    }

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

    public function id(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this->add($field, "Выберите значение поля «{$label}».");
        }

        if (!ctype_digit($value) || (int) $value < 1) {
            return $this->add($field, "Поле «{$label}»: ожидается положительное число.");
        }

        return $this;
    }

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

    public function optionalText(string $field, string $label, int $max = 255): self
    {
        $value = $this->value($field);

        if ($value === '') {
            return $this;
        }

        if (mb_strlen($value, 'UTF-8') > $max) {
            return $this->add($field, "Поле «{$label}»: длина не должна превышать {$max} символов.");
        }

        return $this;
    }

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

    public function add(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;

        return $this;
    }
}
