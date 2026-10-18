<?php
declare(strict_types=1);

/**
 * Модель питомца владельца.
 *
 * Питомец добавляется в профиль один раз, а при записи на приём
 * выбирается из списка. В заявке хранится ссылка на питомца,
 * поэтому переименование питомца не требует правки старых заявок.
 */
final class Pet
{
    /** Максимальная длина заметки владельца о питомце. */
    public const NOTE_MAX_LENGTH = 255;

    /** Добавить питомца в профиль. Возвращает id. */
    public static function create(
        int $userId,
        string $name,
        string $animalType,
        string $breed = '',
        ?string $birthDate = null,
        string $note = ''
    ): int {
        return Database::insert(
            'INSERT INTO pets (user_id, name, animal_type, breed, birth_date, note)
             VALUES (:user_id, :name, :animal_type, :breed, :birth_date, :note)',
            [
                'user_id'     => $userId,
                'name'        => $name,
                'animal_type' => $animalType,
                'breed'       => $breed === '' ? null : $breed,
                'birth_date'  => $birthDate,
                'note'        => $note === '' ? null : $note,
            ]
        );
    }

    /**
     * Питомцы владельца.
     * Сортировка — по дате рождения (самые молодые сверху),
     * при её отсутствии — по кличке.
     */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM applications a WHERE a.pet_id = p.id) AS apps_count
               FROM pets p
              WHERE p.user_id = :user_id
              ORDER BY p.birth_date DESC NULLS LAST, p.name',
            ['user_id' => $userId]
        );
    }

    /** Питомец по идентификатору — без проверки владельца. */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM pets WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Питомец, принадлежащий указанному владельцу.
     * Нужен там, где важно не дать пользователю подсунуть чужой id:
     * возвращает null, если питомец чужой или не существует.
     */
    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM pets WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /** Количество питомцев в системе. */
    public static function count(): int
    {
        $row = Database::fetchOne('SELECT COUNT(*) AS n FROM pets');

        return (int) ($row['n'] ?? 0);
    }

    /** Занята ли кличка у этого владельца. */
    public static function nameExists(int $userId, string $name): bool
    {
        return Database::fetchOne(
            'SELECT 1 FROM pets WHERE user_id = :user_id AND name = :name',
            ['user_id' => $userId, 'name' => $name]
        ) !== null;
    }

    /** Сколько заявок оформлено на питомца. */
    public static function applicationCount(int $petId): int
    {
        $row = Database::fetchOne(
            'SELECT COUNT(*) AS n FROM applications WHERE pet_id = :pet_id',
            ['pet_id' => $petId]
        );

        return (int) ($row['n'] ?? 0);
    }

    /**
     * Удалить питомца из профиля.
     * Возвращает текст ошибки либо null при успехе.
     *
     * Питомца с заявками удалить нельзя: в заявке осталась бы ссылка
     * на несуществующую запись, а администратор увидел бы пустого питомца.
     */
    public static function delete(int $petId, int $userId): ?string
    {
        if (self::findForUser($petId, $userId) === null) {
            return 'Питомец не найден в вашем профиле.';
        }

        $applications = self::applicationCount($petId);

        if ($applications > 0) {
            return sprintf(
                'Нельзя удалить питомца: на него оформлено %d %s. Удаление заявок не предусмотрено.',
                $applications,
                self::applicationWord($applications)
            );
        }

        Database::execute('DELETE FROM pets WHERE id = :id', ['id' => $petId]);

        return null;
    }

    /** Русское склонение слова «заявка». */
    private static function applicationWord(int $number): string
    {
        return pluralForms($number, 'заявка', 'заявки', 'заявок');
    }

    private function __construct()
    {
    }
}