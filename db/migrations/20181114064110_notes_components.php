<?php


use Phinx\Migration\AbstractMigration;

class NotesComponents extends AbstractMigration
{
    public function change()
    {
        $this->table("notes")
            ->addColumn("uuid", "uuid")
            ->addColumn("related_with", "string", ['limit' => 20])
            ->addColumn("related_with_id", "integer")
            ->addColumn("comments", "text", ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM])
            ->addColumn("posted_by", "integer")
            ->addColumn("created", "datetime", ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn("is_private", "integer", ['default' => 0, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY])
            ->addForeignKey("posted_by", "scfl_users", "id", ['delete' => 'CASCADE', 'update' => "NO_ACTION"])
            ->create();

        $this->table("attachments")
            ->addColumn("uuid", "uuid")
            ->addColumn("related_with", "string", ['limit' => 20])
            ->addColumn("related_with_id", "integer")
            ->addColumn("added_by", "integer")
            ->addColumn("file_location_path", "string", ['limit' => 120])
            ->addColumn("doc_type", "string", ['default' => null, 'null' => true, 'limit' => 30])
            ->addColumn("file_type", "string", ['default' => null, 'null' => true, 'limit' => 50])
            ->addColumn("is_private", "integer", ['default' => 0, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY])
            ->addForeignKey("added_by", "scfl_users", "id", ['delete' => 'CASCADE', 'update' => "NO_ACTION"])
            ->create();
    }
}
