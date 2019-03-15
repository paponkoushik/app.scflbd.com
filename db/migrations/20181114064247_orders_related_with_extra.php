<?php


use Phinx\Migration\AbstractMigration;

class OrdersRelatedWithExtra extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        if ( ! $this->table("scfl_orders")->hasColumn("status")) {
            $this->table("scfl_orders")
                ->addColumn("status", "integer", ['default' => 0, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY])
                ->update();
        }

        if ($this->table("scfl_orders")->hasForeignKey("company_id")) {
            $this->table("scfl_orders")
                ->dropForeignKey("company_id")
                ->update();
        }
    }

    public function down()
    {

    }
}
