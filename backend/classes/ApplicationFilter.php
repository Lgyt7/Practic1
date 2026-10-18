<?php
declare(strict_types=1);

/**
 * Разбор фильтров панели администратора.
 *
 * Фильтры живут в адресной строке, поэтому приходят из GET откуда угодно
 * и обязаны проверяться по справочникам до попадания в SQL.
 *
 * Разбор вынесен в класс, потому что параметры нужны в двух местах:
 * сама страница панели и обработчик смены статуса, которому они нужны,
 * чтобы пересчитать «показано N–M из K» без перезагрузки страницы.
 */
final class ApplicationFilter
{
    /** Максимальная длина поисковой строки. */
    public const QUERY_MAX_LENGTH = 60;

    private function __construct(
        public readonly string $status,
        public readonly string $service,
        public readonly string $query,
        public readonly int $page
    ) {
    }

    /**
     * Фильтр из массива параметров запроса.
     * Нераспознанное значение отбрасывается — фильтр просто не применяется.
     */
    public static function fromArray(array $source): self
    {
        $status = (string) ($source['status'] ?? '');

        if (!in_array($status, STATUSES, true)) {
            $status = '';
        }

        $service = (string) ($source['service'] ?? '');

        if (!in_array($service, SERVICES, true)) {
            $service = '';
        }

        return new self(
            $status,
            $service,
            mb_substr(trim((string) ($source['q'] ?? '')), 0, self::QUERY_MAX_LENGTH),
            max(1, (int) ($source['page'] ?? 1))
        );
    }

    /** Применён ли хоть один фильтр. */
    public function isActive(): bool
    {
        return $this->status !== '' || $this->service !== '' || $this->query !== '';
    }

    /**
     * Фильтр из поля «return» обработчика смены статуса.
     * Значение там ограничено ссылкой на панель, поэтому достаточно
     * вытащить строку запроса и разобрать её тем же способом.
     */
    public static function fromReturn(string $return): self
    {
        $query = parse_url($return, PHP_URL_QUERY);
        $params = [];

        if (is_string($query)) {
            parse_str($query, $params);
        }

        return self::fromArray($params);
    }

    /** Параметры для ссылки: фильтры без номера страницы. */
    public function params(): array
    {
        return ['status' => $this->status, 'service' => $this->service, 'q' => $this->query];
    }

    /** Параметры для ссылки с номером страницы. */
    public function paramsWithPage(int $page): array
    {
        return $this->params() + ['page' => $page];
    }
}