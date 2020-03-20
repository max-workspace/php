<?php

namespace app\library;

use phpCAS;

class PhpCasService {
    // cas service 地址
    protected $passportUrl = '';
    // cas service 端口
    protected $passportPort = '';
    // cas context
    protected $casContext = '';
    // cas log
    protected $casLog = '/home/work/dsp/log/ldap-cas.log';
    // 退出登陆后跳转地址
    protected $defaultLogoutAddress = '';
    // 是否开启同步登出功能
    protected $handleLogoutRequests = true;

//    /**
//     * BaseController constructor.
//     * 直接在构造中使用系统配置加载配置的方式，需要在命名空间 use Yii
//     * @param $passportUrl
//     * @param $passportPort
//     * @param $casContext
//     * @param $defaultLogoutAddress
//     */
//    public function __construct()
//    {
//        $this->passportUrl = Yii::$app->params['passportUrl'];
//        $this->passportPort = Yii::$app->params['passportPort'];
//        $this->casContext = Yii::$app->params['casContext'];
//        $this->casLog = Yii::$app->params['casLog'];
//        $this->defaultLogoutAddress = Yii::$app->params['defaultLogoutAddress'];
//        $this->handleLogoutRequests = Yii::$app->params['handleLogoutRequests'];
//        $this->initPhpCas();
//    }

    /**
     * BaseController constructor.
     * 读取系统配置在构造传参的方式加载配置
     * @param $passportUrl
     * @param $passportPort
     * @param $casContext
     * @param $defaultLogoutAddress
     */
    public function __construct($passportUrl, $passportPort, $casContext, $casLog, $defaultLogoutAddress, $handleLogoutRequests)
    {
        $this->passportUrl = empty($passportUrl) ? $this->passportUrl : $passportUrl;
        $this->passportPort = empty($passportPort) ? $this->passportPort : $passportPort;
        $this->casContext = empty($casContext) ? $this->casContext : $casContext;
        $this->casLog = empty($casLog) ? $this->casLog : $casLog;
        $this->defaultLogoutAddress = empty($defaultLogoutAddress) ? $this->defaultLogoutAddress : $defaultLogoutAddress;
        $this->handleLogoutRequests = empty($handleLogoutRequests) ? $this->handleLogoutRequests : $handleLogoutRequests;
        $this->initPhpCas();
    }

    protected function initPhpCas()
    {
        phpCAS::setDebug($this->casLog);
        phpCAS::client(
            CAS_VERSION_3_0,
            $this->passportUrl,
            $this->passportPort,
            $this->casContext
        );
        // 用http协议连接
        phpCAS::setNoCasServerValidation();
        // 是否开启其他语言平台间同步登出
        if ($this->handleLogoutRequests) {
            phpCAS::handleLogoutRequests();

        }
    }

    /**
     * logout
     * 登出操作
     */
    public function logout()
    {
        phpCAS::logout();
    }

    /**
     * logoutWithRedirect
     * 登出并且进行重定向操作
     * @param null $service
     */
    public function logoutWithRedirect($service = null)
    {
        $service = empty($service) ? $this->defaultLogoutAddress : $service;
        phpCAS::logoutWithRedirectService($service);
    }

    /**
     * getLoginStatus
     * 返回当前用户的登陆状态
     * @return bool
     */
    public function getLoginStatus()
    {
        return phpCAS::checkAuthentication();
    }

    /**
     * login
     * 强制当前用户登陆，并重定向到登陆页面
     */
    public function login()
    {
        phpCAS::forceAuthentication();
    }

    public function getUser()
    {
        return phpCAS::getUser();
    }

    public function getUserInfo()
    {
        return phpCAS::getAttributes();
    }
}
