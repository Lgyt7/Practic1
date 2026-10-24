<?php
declare(strict_types=1);

final class ApplicationFilter
{

    public const QUERY_MAX_LENGTH = 60;

    private function __construct(
        public readonly string $status,
        public readonly string $service,
        public readonly string $query,
        public readonly int $page
    ) {
    }

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

    public function isActive(): bool
    {
        return $this->status !== '' || $this->service !== '' || $this->query !== '';
    }

    public static function fromReturn(string $return): self
    {
        $query = parse_url($return, PHP_URL_QUERY);
        $params = [];

        if (is_string($query)) {
            parse_str($query, $params);
        }

        return self::fromArray($params);
    }

    public function params(): array
    {
        return ['status' => $this->status, 'service' => $this->service, 'q' => $this->query];
    }

    public function paramsWithPage(int $page): array
    {
        return $this->params() + ['page' => $page];
    }
}
