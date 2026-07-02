<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePostIndexesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // 1. Turn off default id and set a custom primary key
        $table = $this->table('post_indexes', [
            'id' => false,
            'primary_key' => 'post_index'
        ]);

        $table
            ->addColumn('post_index', 'string', ['limit' => 10, 'null' => false])
            ->addColumn('region_ua', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('raion_old_ua', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('raion_new_ua', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('city', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('postal_code', 'string', ['limit' => 20, 'null' => false])

            ->addColumn('region_en', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('raion_new_en', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('settlement', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('post_office_ua', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('post_office_en', 'string', ['limit' => 150, 'null' => true])

            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->create();
    }
}
