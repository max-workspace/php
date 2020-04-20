<?php

namespace library;

use Casbin\Enforcer;
use CasbinAdapter\Database\Adapter;
use library\FormatResult;

class CasbinService {
    public $defaultAction = 'default';
    public $enforce;
    public $config;

    public function __construct()
    {
        $params = require_once(__DIR__ . '/../conf/params.php');
        $this->config = $params['CasModel'];
        $adapter = Adapter::newAdapter([
            'type'     => $this->config['type'],
            'hostname' => $this->config['hostname'],
            'database' => $this->config['database'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'hostport' => $this->config['hostport'],
            'casbinRuleTableName'=>$this->config['casbinRuleTableName'],
        ]);

        $model_path = __DIR__ . '/../conf/rbac_model.conf';

        $this->enforce = new Enforcer($model_path, $adapter);
    }

    public function CheckUserPermission($sub, $obj, $act)
    {
        try {
            if (empty($sub) || empty($obj) || empty($act)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->enforce($sub, $obj, $act);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            echo $e;die;
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public function getPermissionList($sub)
    {
        try {
            if (empty($sub)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->getPermissionsForUser($sub);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public function addPermissionForUser($sub, $obj, $act)
    {
        try {
            if (empty($sub) || empty($obj)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->addPermissionForUser($sub, $obj, $act);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public function deletePermissionForUser($sub, $obj, $act)
    {
        try {
            if (empty($sub) || empty($obj) || empty($act)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->deletePermissionForUser($sub, $obj, $act);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public function AddRoleForUser($user, $role)
    {
        try {
            if (empty($user) || empty($role)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->addRoleForUser($user, $role);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public function DeleteRoleForUser($user, $role)
    {
        try {
            if (empty($user) || empty($role)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->deleteRoleForUser($user, $role);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public function GetRoleByUser($sub)
    {
        try {
            if (empty($sub)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->getRolesForUser($sub);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public function DeleteRole($sub)
    {
        try {
            if (empty($sub)) {
                throw new \Exception('缺少必要的参数');
            }
            $ret = $this->enforce->deleteRole($sub);
            return FormatResult::success($ret);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }
}