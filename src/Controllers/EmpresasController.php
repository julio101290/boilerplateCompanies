<?php

namespace julio101290\boilerplatecompanies\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplatelog\Models\LogModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use julio101290\boilerplatecompanies\Models\UsuariosempresaModel;

class EmpresasController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $empresas;
    protected $usuarios;
    protected $usuariosEmpresa;

    public function __construct() {
        $this->empresas = new EmpresasModel();
        $this->log = new LogModel();
        $this->usuarios = new UserModel();
        $this->usuariosEmpresa = new UsuariosempresaModel();
        helper('menu', 'filesystem');
    }

    public function index() {


        helper('auth');
        $userName = user()->username;
        $idUser = user()->id;
        $titulos["empresas"] = $this->empresas->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == "0") {

            $empresasID[0] = "0";
        } else {

            $empresasID = array_column($titulos["empresas"], "id");
        }



        if ($this->request->isAJAX()) {




            $request = $this->request->getVar();

            $draw = isset($request['draw']) ? (int) $request['draw'] : 0;
            $start = isset($request['start']) ? (int) $request['start'] : 0;
            $length = isset($request['length']) ? (int) $request['length'] : 10;
            $searchValue = $request['search']['value'] ?? null;

            // Columnas EXACTAS como las usas
            $columns = [
                'id',
                'nombre',
                'direccion',
                'rfc',
                'telefono',
                'correoElectronico',
                'diasEntrega',
                'caja',
                'logo',
                'certificado',
                'archivoKey',
                'contraCertificado',
                'regimenFiscal',
                'razonSocial',
                'codigoPostal',
                'CURP',
                'created_at',
                'updated_at',
            ];

            // BASE QUERY (tu mismo select)
            $datos = $this->empresas->select('id as id'
                            . ',nombre as nombre'
                            . ',direccion as direccion'
                            . ',rfc as rfc'
                            . ',telefono as telefono'
                            . ',correoElectronico as correoElectronico'
                            . ',diasEntrega as diasEntrega'
                            . ',caja as caja'
                            . ',logo as logo'
                            . ',certificado as certificado'
                            . ',archivoKey as archivoKey'
                            . ',contraCertificado as contraCertificado'
                            . ',regimenFiscal as regimenFiscal'
                            . ',razonSocial as razonSocial'
                            . ',codigoPostal as codigoPostal'
                            . ',CURP as CURP'
                            . ',created_at as created_at'
                            . ',updated_at as updated_at')
                    ->where('deleted_at', null)
                    ->whereIn("id", $empresasID);

            // TOTAL SIN FILTROS
            $recordsTotal = $datos->countAllResults(false);

            // SEARCH GLOBAL
            if (!empty($searchValue)) {
                $datos->groupStart()
                        ->like('nombre', $searchValue)
                        ->orLike('direccion', $searchValue)
                        ->orLike('rfc', $searchValue)
                        ->orLike('telefono', $searchValue)
                        ->orLike('correoElectronico', $searchValue)
                        ->orLike('razonSocial', $searchValue)
                        ->orLike('codigoPostal', $searchValue)
                        ->orLike('CURP', $searchValue)
                        ->groupEnd();
            }

            // TOTAL FILTRADO
            $recordsFiltered = $datos->countAllResults(false);

            // ORDER
            if (isset($request['order'][0]['column'])) {

                $orderColIndex = (int) $request['order'][0]['column'];
                $orderDir = strtolower($request['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

                if (isset($columns[$orderColIndex])) {
                    $datos->orderBy($columns[$orderColIndex], $orderDir);
                }
            } else {
                $datos->orderBy('id', 'DESC');
            }

            // LIMIT
            if ($length != -1) {
                $datos->limit($length, $start);
            }

            $data = $datos->findAll();

            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => $recordsTotal,
                        'recordsFiltered' => $recordsFiltered,
                        'data' => $data
            ]);
        }



        $regimenesFiscales = $this->catalogosSAT->regimenesFiscales40()->searchByField("texto", "%%", 1000);

        $titulos["title"] = lang('empresas.title');
        $titulos["subtitle"] = lang('empresas.subtitle');
        $titulos["regimenesFiscales"] = $regimenesFiscales;

        //$data["data"] = $datos;
        return view('julio101290\boilerplatecompanies\Views\empresas', $titulos);
    }

    /**
     * Read Vehicle
     */
    public function obtenerEmpresa() {


        $idEmpresa = $this->request->getPost("idEmpresa");

        $datosEmpresa = $this->empresas->find($idEmpresa);

        echo json_encode($datosEmpresa);
    }

    /**
     * Save or update Vehicle
     */
    public function save() {


        helper('auth');
        $userName = user()->username;
        $idUser = user()->id;

        $datos = $this->request->getPost();

        unset($datos["certificado"]);
        unset($datos["archivoKey"]);
        unset($datos["certificadoCSD"]);
        unset($datos["archivoKeyCSD"]);
        unset($datos["logo"]);

        $certificado = $this->request->getFile('certificado');
        $archivoKey = $this->request->getFile('archivoKey');

        $certificadoCSD = $this->request->getFile('certificadoCSD');
        $archivoKeyCSD = $this->request->getFile('archivoKeyCSD');

        $logo = $this->request->getFile('logo');

        if ($certificado <> null) {
            if ($certificado->getClientExtension() <> "cer") {

                return lang("empresas.certExtensionIncorrect");
            }

            $datos["certificado"] = $datos["rfc"] . "_certificado.cer";
        }

        if ($archivoKey <> null) {
            if ($archivoKey->getClientExtension() <> "key") {

                return lang("empresas.keyFileExtensionIncorrect");
            }

            $datos["archivoKey"] = $datos["rfc"] . "_certificado.key";
        }




        if ($certificadoCSD <> null) {
            if ($certificadoCSD->getClientExtension() <> "cer") {

                return lang("empresas.certExtensionIncorrect");
            }

            $datos["certificadoCSD"] = $datos["rfc"] . "_certificadoCSD.cer";
        }

        if ($archivoKeyCSD <> null) {
            if ($archivoKeyCSD->getClientExtension() <> "key") {

                return lang("empresas.keyFileExtensionIncorrect");
            }

            $datos["archivoKeyCSD"] = $datos["rfc"] . "_certificadoCSD.key";
        }

        if ($logo) {

            if ($logo->getClientExtension() <> "png") {

                return lang("empresas.pngFileExtensionIncorrect");
            }
            $datos["logo"] = $datos["rfc"] . "_logo.png";
        }

        if ($datos["idEmpresa"] == 0) {


            try {

                if ($this->empresas->save($datos) === false) {

                    $errores = $this->empresas->errors();

                    foreach ($errores as $field => $error) {

                        echo $error . " ";
                    }

                    return;
                }

                $dateLog["description"] = lang("empresas.logDescription") . json_encode($datos);
                $dateLog["user"] = $userName;

                $empresaUsuariosdatos["idEmpresa"] = $this->empresas->getInsertID();
                $empresaUsuariosdatos["idUsuario"] = $idUser;
                $empresaUsuariosdatos["status"] = "on";

                $this->usuariosEmpresa->insert($empresaUsuariosdatos);

                $this->log->save($dateLog);

                if ($certificado <> null) {
                    $certificado->move(WRITEPATH . "uploads/certificates", $datos["rfc"] . "_certificado.cer");
                }

                if ($archivoKey <> null) {
                    $archivoKey->move(WRITEPATH . "uploads/certificates", $datos["rfc"] . "_certificado.key");
                }



                if ($certificadoCSD <> null) {
                    $certificado->move(WRITEPATH . "uploads/certificates", $datos["rfc"] . "_certificadoCSD.cer");
                }

                if ($archivoKeyCSD <> null) {
                    $archivoKey->move(WRITEPATH . "uploads/certificates", $datos["rfc"] . "_certificadoCSD.key");
                }

                if ($logo <> null) {
                    $logo->move("images/logo", $datos["rfc"] . "_logo.png");
                }

                echo "Guardado Correctamente";
            } catch (\PHPUnit\Framework\Exception $ex) {


                echo "Error al guardar " . $ex->getMessage();
            }
        } else {


            $datosAnteriores = $this->empresas->find($datos["idEmpresa"]);

            if ($this->empresas->update($datos["idEmpresa"], $datos) == false) {

                $errores = $this->empresas->errors();
                foreach ($errores as $field => $error) {

                    echo $error . " ";
                }

                return;
            } else {


                if ($certificado <> null) {

                    if (file_Exists(WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificado.cer")) {

                        unlink(WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificado.cer");
                    }

                    $certificado->move(WRITEPATH . "uploads/certificates", $datos["rfc"] . "_certificado.cer");
                }

                if ($archivoKey <> null) {

                    if (file_Exists((WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificado.key"))) {
                        unlink(WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificado.key");
                    }
                }

                if ($certificadoCSD <> null) {

                    if (file_Exists(WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificadoCSD.cer")) {

                        unlink(WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificadoCSD.cer");
                    }

                    $certificadoCSD->move(WRITEPATH . "uploads/certificates", $datos["rfc"] . "_certificadoCSD.cer");
                }

                if ($archivoKeyCSD <> null) {

                    if (file_Exists((WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificadoCSD.key"))) {
                        unlink(WRITEPATH . "uploads/certificates/" . $datosAnteriores["rfc"] . "_certificadoCSD.key");
                    }

                    $archivoKeyCSD->move(WRITEPATH . "uploads/certificates", $datos["rfc"] . "_certificadoCSD.key");
                }

                if ($logo <> null) {

                    if (file_Exists("images/logo/" . $datosAnteriores["rfc"] . "_logo.png")) {

                        unlink("images/logo/" . $datosAnteriores["rfc"] . "_logo.png");
                    }

                    $logo->move("images/logo", $datos["rfc"] . "_logo.png");
                }

                $dateLog["description"] = lang("empresas.logUpdated") . json_encode($datosAnteriores);
                $dateLog["user"] = $userName;

                $this->log->save($dateLog);
                echo "Actualizado Correctamente";

                return;
            }
        }

        return;
    }

    /**
     * Delete Empresas
     * @param type $id
     * @return type
     */
    public function delete($id) {

        $infoEmpresa = $this->empresas->find($id);
        helper('auth');
        $userName = user()->username;

        if (!$found = $this->empresas->delete($id)) {
            return $this->failNotFound(lang('empresas.msg.msg_get_fail'));
        }

        $logData["description"] = lang("empresas.logDeleted") . json_encode($infoEmpresa);
        $logData["user"] = $userName;

        if (file_exists(WRITEPATH . "uploads/certificates/" . $infoEmpresa["rfc"] . "_certificado.cer")) {

            unlink(WRITEPATH . "uploads/certificates/" . $infoEmpresa["rfc"] . "_certificado.cer");
        }

        if (file_exists(WRITEPATH . "uploads/certificates/" . $infoEmpresa["rfc"] . "_certificado.key")) {

            unlink(WRITEPATH . "uploads/certificates/" . $infoEmpresa["rfc"] . "_certificado.key");
        }

        if (file_exists("images/logo/" . $infoEmpresa["rfc"] . "_logo.png")) {

            unlink("images/logo/" . $infoEmpresa["rfc"] . "_logo.png");
        }

        $this->empresas->purgeDeleted();

        $this->log->save($logData);
        return $this->respondDeleted($found, lang('empresas.msg_delete'));
    }

    public function usuariosPorEmpresa($empresa) {



        $empresa = $this->usuarios->db->escapeString($empresa);

        $nombreUsuariosEmpresa = $this->usuariosEmpresa->db->prefixTable("usuariosempresa");

        $tablaUsuariosNombre = $this->usuarios->db->prefixTable("users");

        $db = \Config\Database::connect();

        if ($db->DBDriver === 'pgsql' || $db->DBDriver === 'PDO\Postgres' || $db->DBDriver === 'Postgre') {


            $usuarios = $this->usuarios->select("u.id as id, u.username as username, {$empresa} as idEmpresa")
                    ->select("COALESCE((SELECT ue.status
                       FROM {$nombreUsuariosEmpresa} ue
                       WHERE \"ue\".\"idUsuario\" = u.id
                         AND \"ue\".\"idEmpresa\" = {$empresa}), 'off') AS status")
                    ->select("COALESCE((SELECT ue.id
                       FROM {$nombreUsuariosEmpresa} ue
                       WHERE \"ue\".\"idUsuario\" = u.id
                         AND \"ue\".\"idEmpresa\" = {$empresa}), 0) AS idnombreempresa")
                    ->from("{$tablaUsuariosNombre} u")
                    ->where("u.deleted_at", null)
                    ->groupBy("u.id"); // Agrega esta línea para evitar la repetición de usuarios
        } else {

            $usuarios = $this->usuarios->select("u.id as id, u.username as username, {$empresa} as idEmpresa")
                    ->select("COALESCE((SELECT ue.status
                       FROM {$nombreUsuariosEmpresa} ue
                       WHERE ue.idUsuario = u.id
                         AND ue.idEmpresa = {$empresa}), 'off') AS status")
                    ->select("COALESCE((SELECT ue.id
                       FROM {$nombreUsuariosEmpresa} ue
                       WHERE ue.idUsuario = u.id
                         AND ue.idEmpresa = {$empresa}), 0) AS idnombreempresa")
                    ->from("{$tablaUsuariosNombre} u")
                    ->where("u.deleted_at", null)
                    ->groupBy("u.id"); // Agrega esta línea para evitar la repetición de usuarios
        }

        $db->close();

        return \Hermawan\DataTables\DataTable::of($usuarios)->toJson(true);
    }

    /**
     * Activar Desactivar Usuario Por Empresa
     */
    public function ActivarDesactivar() {

        $datos = $this->request->getPost();

        if ($datos["id"] > 0) {

            //ACTUALIZA SI  EXISTE

            if ($this->usuariosEmpresa->update($datos["id"], $datos) === false) {
                $errores = $this->usuariosEmpresa->errors();
                foreach ($errores as $field => $error) {
                    echo $error . " ";
                }
                return;
            }

            echo "ok";
        } else {

            //INSERTA SI  NO EXISTE
            if ($this->usuariosEmpresa->insert($datos) === false) {

                $errores = $this->usuariosEmpresa->errors();

                foreach ($errores as $key => $error) {

                    echo $error . " ";
                }

                return;
            }



            echo "ok";
        }
    }
}
