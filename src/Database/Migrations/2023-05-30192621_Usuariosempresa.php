<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Usuariosempresa extends Migration
{
    public function up()
    {
        // Usuariosempresa
        $this->forge->addField([
            'id'                    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idEmpresa'             => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'idUsuario'             => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'status'             => ['type' => 'varchar', 'constraint' => 8, 'null' => true],

            'created_at'       => ['type' => 'datetime', 'null' => true],
            'updated_at'       => ['type' => 'datetime', 'null' => true],
            'deleted_at'       => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('usuariosempresa', true);
    }
    public function down()
    {
        $this->forge->dropTable('usuariosempresa', true);
    }
}
