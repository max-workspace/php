<?php

namespace app\library\ldap;

use Yii;
use Adldap\Adldap;
use Adldap\Connections\Provider;
use doushen\frame\ResponseBath;

class LdapBaseService extends Adldap
{
    protected $ldapService = [];
    protected $defaultSuccessCode = 200;
    protected $defaultSuccessDesc = 'success';
    protected $defaultErrorCode = 500;
    protected $defaultErrorDesc = 'error';

    public function __construct(array $providers = [])
    {
        // 如果没有传参就使用配置文件中的参数构建服务
        if (empty($providers) && isset(Yii::$app->params['ldap'])) {
            $providers = [
                'default' => Yii::$app->params['ldap'],
            ];
        }
        parent::__construct($providers);
    }

    /**
     * 获取指定的ldap服务提供者
     * @param null $name
     * @param null $username
     * @param null $password
     * @return \Adldap\Connections\ProviderInterface|mixed
     */
    public function getLdapService($name = null, $username = null, $password = null)
    {
        if (is_null($name)) {
            $name = $this->default;
        }

        if (isset($this->ldapService[$name])) {
            return $this->ldapService[$name];
        } else {
            $ldapService = $this->connect($name, $username, $password);
            $this->ldapService[$name] = $ldapService;
            return $ldapService;
        }
    }

    /**
     * 获取所有的ou信息
     * @param Provider $ldapService
     * @return array
     */
    public function getDepartmentByDepartmentIdList(Provider $ldapService, $departmentIdList = [], $resultFormat = 1)
    {
        try {
            $query = $ldapService->search()->ous();
            if (!empty($departmentIdList)) {
                $query = $query->whereIn(
                    'departmentId', $departmentIdList
                );
            }
            $result = $query->get();
            if (!empty($result) && $resultFormat === 1) {
                $result = json_decode($result, true);
            }
            return ResponseBath::nrdata($result, $this->defaultSuccessCode, $this->defaultSuccessDesc);
        } catch (\Exception $e) {
            return ResponseBath::nrdata([], $this->defaultErrorCode, $e->getMessage());
        }
    }

    /**
     * 根据uid获取用户信息
     * @param Provider $ldapService
     * @param $uid
     * @param array $select
     * @param int $type 0：全等，1：包含
     * @param int $resultFormat 0：不格式化，1格式化
     * @return array
     */
    public function getUserByUid(Provider $ldapService, $uid, $select = [], $type = 0, $resultFormat = 1)
    {
        try {
            if (empty($uid)) {
                throw new \Exception('缺少必要的参数');
            }
            if ($type === 1) {
                $conditions = [
                    ['uid', 'contains', $uid],
                ];
            } else {
                $conditions = [
                    ['uid', '=', $uid],
                ];
            }
            $query = $ldapService->search();
            if (!empty($fields)) {
                $query->select($fields);
            }
            $result = $query->where($conditions)->get();
            if (!empty($result) && $resultFormat === 1) {
                $result = json_decode($result, true);
            }
            return ResponseBath::nrdata($result, $this->defaultSuccessCode, $this->defaultSuccessDesc);
        } catch (\Exception $e) {
            return ResponseBath::nrdata([], $this->defaultErrorCode, $e->getMessage());
        }
    }

    /**
     * 修改用户信息
     * @param Provider $ldapService
     * @param $uid
     * @param $param
     * @return array
     */
    public function updateUserByUid(Provider $ldapService, $uid, $param)
    {
        try {
            if (empty($uid) || empty($param)) {
                throw new \Exception('缺少必要的参数');
            }
            // 获取用户信息
            $user = $ldapService->search()->whereEquals('uid', $uid)->firstOrFail();

            // 修改用户信息
            if (isset($param['mail'])) {
                $user->mail = trim($param['mail']);
            }
            if (isset($param['mobile'])) {
                $user->mobile = trim($param['mobile']);
            }
            if (isset($param['userPassword'])) {
                $user->userPassword = trim($param['userPassword']);
            }
            if (isset($param['cn'])) {
                $user->cn = trim($param['cn']);
            }
            if (isset($param['sn'])) {
                $user->sn = trim($param['sn']);
            }
            if (isset($param['givenName'])) {
                $user->givenName = trim($param['givenName']);
            }
            if (isset($param['idCard'])) {
                $user->idCard = trim($param['idCard']);
            }
            if (isset($param['userStatus'])) {
                $user->userStatus = trim($param['userStatus']);
            }
            if (isset($param['departmentNumber'])) {
                $user->departmentNumber = trim($param['departmentNumber']);
            }
            if (isset($param['telephoneNumber'])) {
                $user->telephoneNumber = trim($param['telephoneNumber']);
            }
            if (isset($param['nickName'])) {
                $user->nickName = trim($param['nickName']);
            }
            if (isset($param['title'])) {
                $user->title = trim($param['title']);
            }
            if (isset($param['station'])) {
                $user->station = trim($param['station']);
            }
            if (isset($param['avatar'])) {
                $user->avatar = trim($param['avatar']);
            }
            if (isset($param['birthday'])) {
                $user->birthday = trim($param['birthday']);
            }
            if (isset($param['gender'])) {
                $user->gender = trim($param['gender']);
            }
            if (isset($param['band'])) {
                $user->band = trim($param['band']);
            }

            $resultChangeLdap = $user->update();
            if (!$resultChangeLdap) {
                throw new \Exception('修改ldap用户信息失败');
            }
            return ResponseBath::nrdata([], $this->defaultSuccessCode, $this->defaultSuccessDesc);
        } catch (\Exception $e) {
            return ResponseBath::nrdata([], $this->defaultErrorCode, $e->getMessage());
        }
    }

    public function addUser(Provider $ldapService, $param)
    {
        try {
            if (empty($param) || empty($param['uid']) || empty($param['cn']) || empty($param['sn']) || empty($param['dn'])) {
                throw new \Exception('缺少必要的参数');
            }
            // 检测对应uid用户是否已经存在
            $result = $this->getUserByUid($ldapService, $param['uid']);
            if (empty($result['code']) || $result['code'] != $this->defaultSuccessCode) {
                throw new \Exception('查询用户信息失败');
            }
            if (is_array($result['data'])) {
                throw new \Exception('当前uid已经存在对应用户');
            }

            // 必要参数补全
            if (empty($param['objectclass'])) {
                $param['objectclass'][0] = 'top';
                $param['objectclass'][1] = 'inetOrgPerson';
            }

            // 创建用户
            $user = $ldapService->make()->user(['dn' => $param['dn']]);
            if (!$user->create($param)) {
                throw new \Exception('创建用户信息失败');
            }
            return ResponseBath::nrdata([], $this->defaultSuccessCode, $this->defaultSuccessDesc);
        } catch (\Exception $e) {
            return ResponseBath::nrdata([], $this->defaultErrorCode, $e->getMessage());
        }
    }

    public function deleteUserByUid(Provider $ldapService, $uid)
    {
        try {
            $user = $ldapService->search()->whereEquals('uid', $uid)->firstOrFail();

            if (!$user->delete()) {
                throw new \Exception('删除用户信息失败');
            }
            return ResponseBath::nrdata([], $this->defaultSuccessCode, $this->defaultSuccessDesc);
        } catch (\Exception $e) {
            return ResponseBath::nrdata([], $this->defaultErrorCode, $e->getMessage());
        }
    }

    /**
     * 移动用户所在部门
     * @param Provider $ldapService
     * @param $uid
     * @param $departmentId
     * @return array
     */
    public function moveUser(Provider $ldapService, $uid, $departmentId)
    {
        try {
            if (empty($uid) || empty($departmentId)) {
                throw new \Exception('缺少必要的参数');
            }

            // 获取部门信息
            $result = $this->getDepartmentByDepartmentIdList($ldapService, [$departmentId]);
            if (empty($result['code']) || $result['code'] != $this->defaultSuccessCode) {
                throw new \Exception('获取部门信息失败');
            }
            $distinguishedname = $result['data'][0]['distinguishedname'][0];

            // 获取用户信息
            $user = $ldapService->search()->whereEquals('uid', $uid)->firstOrFail();

            // 移动用户所在部门
            $resultMoveLdap = $user->move($distinguishedname);
            if (!$resultMoveLdap) {
                throw new \Exception('move ldap用户信息失败');
            }
            return ResponseBath::nrdata([], $this->defaultSuccessCode, $this->defaultSuccessDesc);
        } catch (\Exception $e) {
            return ResponseBath::nrdata([], $this->defaultErrorCode, $e->getMessage());
        }
    }

}