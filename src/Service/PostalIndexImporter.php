<?php

namespace App\Service;

use Exception;
use PDO;

class PostalIndexImporter
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Executes the import of postal indexes from a zip archive.
     *
     * @param string $zipPath Path to the zip file on disk
     * @param string $csvFileName Name of the CSV file inside the archive
     */
    public function import(string $zipPath, string $csvFileName): void
    {
        if (!file_exists($zipPath)) {
            throw new Exception("Архив не найден по пути: {$zipPath}");
        }

        // Unique identifier for the current import session
        $batchId = date('YmdHis') . '_' . bin2hex(random_bytes(4));

        // Open the file directly from the zip file without first unpacking it to disk.
        $streamPath = "zip://{$zipPath}#{$csvFileName}";
        $handle = fopen($streamPath, 'r');

        if (!$handle) {
            throw new Exception("Failed to open data stream: {$streamPath}");
        }

        // Skip CSV header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new Exception("File empty or damaged.");
        }

        // SQL-request for quick insert/update (Upsert)
        $sql = "INSERT INTO post_indexes (
            postal_code, region_ua, raion_old_ua, raion_new_ua, city, 
            region_en, raion_new_en, settlement, post_office_ua, 
            post_office_en, post_index, last_seen_at, is_manual
        ) VALUES (
            :postal_code, :region_ua, :raion_old_ua, :raion_new_ua, :city, 
            :region_en, :raion_new_en, :settlement, :post_office_ua, 
            :post_office_en, :post_index, :last_seen_at, 0
        ) ON DUPLICATE KEY UPDATE
            region_ua = VALUES(region_ua),
            raion_old_ua = VALUES(raion_old_ua),
            raion_new_ua = VALUES(raion_new_ua),
            city = VALUES(city),
            region_en = VALUES(region_en),
            raion_new_en = VALUES(raion_new_en),
            settlement = VALUES(settlement),
            post_office_ua = VALUES(post_office_ua),
            post_office_en = VALUES(post_office_en),
            post_index = VALUES(post_index),
            last_seen_at = VALUES(last_seen_at),
            is_manual = 0"; // If record at first was manual, but now come from archive, we reset flag to manual

        $stmt = $this->db->prepare($sql);

        $rowCount = 0;
        $batchSize = 2000; // Batch size for transaction commit

        $this->db->beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                // check string structure (wait 11 column)
                if (count($row) < 11) {
                    continue;
                }

                // check if post_index is empty (skip it)
                if ($row[10] === null || $row[10] === '') {
                    continue;
                }

                $stmt->execute([
                    ':region_ua'      => $row[0] !== '' ? $row[0] : null,
                    ':raion_old_ua'   => $row[1] !== '' ? $row[1] : null,
                    ':raion_new_ua'   => $row[2] !== '' ? $row[2] : null,
                    ':city'           => $row[3] !== '' ? $row[3] : null,
                    ':postal_code'    => $row[4] !== '' ? $row[4] : null,
                    ':region_en'      => $row[5] !== '' ? $row[5] : null,
                    ':raion_new_en'   => $row[6] !== '' ? $row[6] : null,
                    ':settlement'     => $row[7] !== '' ? $row[7] : null,
                    ':post_office_ua' => $row[8] !== '' ? $row[8] : null,
                    ':post_office_en' => $row[9] !== '' ? $row[9] : null,
                    ':post_index'     => $row[10],
                    ':last_seen_at'   => $batchId
                ]);

                $rowCount++;

                // periodically commit the transaction to free up DBMS logs.
                if ($rowCount % $batchSize === 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();

                    // Freeing up unused PHP memory
                    gc_collect_cycles();
                }
            }

            // Fixing remaining records
            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        // Deleting records that were not in the new file (except for those created manually/via API)
        $deleteSql = "DELETE FROM post_indexes WHERE (last_seen_at IS NULL OR last_seen_at != :batch_id) AND is_manual = 0";

        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->execute([':batch_id' => $batchId]);
    }
}