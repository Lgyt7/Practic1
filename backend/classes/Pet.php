<?php
declare(strict_types=1);

final class Pet
{

    public const NOTE_MAX_LENGTH = 255;

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

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM pets WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM pets WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public static function count(): int
    {
        $row = Database::fetchOne('SELECT COUNT(*) AS n FROM pets');

        return (int) ($row['n'] ?? 0);
    }

    public static function nameExists(int $userId, string $name): bool
    {
        return Database::fetchOne(
            'SELECT 1 FROM pets WHERE user_id = :user_id AND name = :name',
            ['user_id' => $userId, 'name' => $name]
        ) !== null;
    }

    public static function applicationCount(int $petId): int
    {
        $row = Database::fetchOne(
            'SELECT COUNT(*) AS n FROM applications WHERE pet_id = :pet_id',
            ['pet_id' => $petId]
        );

        return (int) ($row['n'] ?? 0);
    }

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

    private static function applicationWord(int $number): string
    {
        return pluralForms($number, 'заявка', 'заявки', 'заявок');
    }

    private function __construct()
    {
    }
}
