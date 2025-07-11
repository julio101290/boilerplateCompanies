<?php

namespace julio101290\boilerplatecompanies\Models;

use CodeIgniter\Model;

class UsuariosempresaModel extends Model {

    protected $table = 'usuariosempresa';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['id', 'idEmpresa', 'idUsuario', 'status', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;

    public function mdlUsuariosPorEmpresa($idEmpresa, $busqueda) {
        $builder = $this->db->table('usuariosempresa a');
        $builder->select('b.id, b.firstname, b.lastname, b.username');
        $builder->join('users b', 'a.idUsuario = b.id');
        $builder->where('a.status', 'on');
        $builder->where('a.idEmpresa', $idEmpresa);
        $builder->groupStart()
                ->like('b.username', $busqueda)
                ->orLike('b.firstname', $busqueda)
                ->orLike('b.lastname', $busqueda)
                ->groupEnd();

        $resultado = $builder->get()->getResultArray();
        return $resultado;
    }
}
