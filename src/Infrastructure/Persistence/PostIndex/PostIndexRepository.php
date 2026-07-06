<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PostIndex;

use PDO;

final class PostIndexRepository
{
    private const DEFAULT_LIMIT = 50;

    private PDO $pdo;

    /**
     * PostIndexRepository constructor.
     *
     * @param PDO $pdo PDO-подключение к базе данных.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns a list of postal codes with paginated navigation.
     *
     * If no search parameters are provided, return 50 records,
     * sorted by postal code in ascending order.
     *
     * @param int $page Page number, starting from 1.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPaginated(int $page = 1): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::DEFAULT_LIMIT;

        $sql = '
            SELECT
                post_index,
                region_ua,
                raion_old_ua,
                raion_new_ua,
                city,
                postal_code,
                region_en,
                raion_new_en,
                settlement,
                post_office_ua,
                post_office_en,
                is_manual,
                last_seen_at,
                created_at
            FROM post_indexes
            ORDER BY post_index ASC
            LIMIT :limit OFFSET :offset
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', self::DEFAULT_LIMIT, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Returns a single record by postal code.
     *
     * @param string $postIndex Postal code of the communication office.
     *
     * @return array<string, mixed>|null
     */
    public function findByPostIndex(string $postIndex): ?array
    {
        $sql = '
            SELECT
                post_index,
                region_ua,
                raion_old_ua,
                raion_new_ua,
                city,
                postal_code,
                region_en,
                raion_new_en,
                settlement,
                post_office_ua,
                post_office_en,
                is_manual,
                last_seen_at,
                created_at
            FROM post_indexes
            WHERE post_index = :post_index
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':post_index', $postIndex);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Finds postal codes by address or part of the address.
     *
     * Search is performed on Ukrainian and English address fields.
     * Returns a maximum of 50 records.
     *
     * @param string $address Address or part of the address.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByAddress(string $address): array
    {
        $sql = '
            SELECT
                post_index,
                region_ua,
                raion_old_ua,
                raion_new_ua,
                city,
                postal_code,
                region_en,
                raion_new_en,
                settlement,
                post_office_ua,
                post_office_en,
                is_manual,
                last_seen_at,
                created_at
            FROM post_indexes
            WHERE
                region_ua LIKE :address
                OR raion_old_ua LIKE :address
                OR raion_new_ua LIKE :address
                OR city LIKE :address
                OR postal_code LIKE :address
                OR region_en LIKE :address
                OR raion_new_en LIKE :address
                OR settlement LIKE :address
                OR post_office_ua LIKE :address
                OR post_office_en LIKE :address
            ORDER BY
                city ASC,
                post_office_ua ASC,
                post_index ASC
            LIMIT :limit
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':address', '%' . $address . '%');
        $stmt->bindValue(':limit', self::DEFAULT_LIMIT, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Adds or updates one or more postal codes.
     *
     * Records added via API are marked with is_manual = 1,
     * so daily import does not delete them as missing in the archive.
     *
     * @param array<int, array<string, mixed>> $items List of postal codes.
     *
     * @return int Number of processed records.
     */
    public function upsertMany(array $items): int
    {
        $sql = '
            INSERT INTO post_indexes (
                post_index,
                region_ua,
                raion_old_ua,
                raion_new_ua,
                city,
                postal_code,
                region_en,
                raion_new_en,
                settlement,
                post_office_ua,
                post_office_en,
                is_manual,
                last_seen_at
            ) VALUES (
                :post_index,
                :region_ua,
                :raion_old_ua,
                :raion_new_ua,
                :city,
                :postal_code,
                :region_en,
                :raion_new_en,
                :settlement,
                :post_office_ua,
                :post_office_en,
                1,
                :last_seen_at
            )
            ON DUPLICATE KEY UPDATE
                region_ua = VALUES(region_ua),
                raion_old_ua = VALUES(raion_old_ua),
                raion_new_ua = VALUES(raion_new_ua),
                city = VALUES(city),
                postal_code = VALUES(postal_code),
                region_en = VALUES(region_en),
                raion_new_en = VALUES(raion_new_en),
                settlement = VALUES(settlement),
                post_office_ua = VALUES(post_office_ua),
                post_office_en = VALUES(post_office_en),
                is_manual = 1,
                last_seen_at = VALUES(last_seen_at)
        ';

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare($sql);
            $count = 0;

            foreach ($items as $item) {
                $stmt->execute([
                    ':post_index'     => $item['post_index'],
                    ':region_ua'      => $item['region_ua'] ?? null,
                    ':raion_old_ua'   => $item['raion_old_ua'] ?? null,
                    ':raion_new_ua'   => $item['raion_new_ua'] ?? null,
                    ':city'           => $item['city'] ?? null,
                    ':postal_code'    => $item['postal_code'] ?? null,
                    ':region_en'      => $item['region_en'] ?? null,
                    ':raion_new_en'   => $item['raion_new_en'] ?? null,
                    ':settlement'     => $item['settlement'] ?? null,
                    ':post_office_ua' => $item['post_office_ua'] ?? null,
                    ':post_office_en' => $item['post_office_en'] ?? null,
                    ':last_seen_at'   => $item['last_seen_at'] ?? date('Y-m-d H:i:s'),
                ]);

                $count++;
            }

            $this->pdo->commit();

            return $count;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    /**
     * Deletes a postal code from the database.
     *
     * @param string $postIndex Postal code of the communication office.
     *
     * @return bool true, if the record was deleted.
     */
    public function deleteByPostIndex(string $postIndex): bool
    {
        $sql = 'DELETE FROM post_indexes WHERE post_index = :post_index';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':post_index', $postIndex);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
