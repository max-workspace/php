<?php
/**
 * Author: wuhongfei
 * Date: 2019/09/05
 * Time: 15:04:23
 * desc: 用户积分控制器
 */
require_once __DIR__ . '/../base/ControlBase.class.php';
require_once __DIR__ . '/../models/ScoreRegular.class.php';
require_once __DIR__ . '/../models/UserScoreLog.class.php';

class UserScoreControl extends ControlBase
{

    private $userModel;

    private $scoreLogModel;

    private $scoreRegularModel;

    private $repeatTypeOnce = 0;

    private $repeatTypeDaily = 1;

    private $statusTypeEnable = 0;

    public function __construct($context)
    {
        parent:: __construct($context);
        $this->scoreLogModel = new UserScoreLog($context);
        $this->scoreRegularModel = new ScoreRegular($context);
        // todo 初始化数据库操作对象 用户（用户的积分操作不仅涉及用户表上的积分字段，还涉及到了积分记录表）
    }

    /**
     * 获取指定用户、指定积分id下在给定时间范围内的积分记录
     * @param array $userIdList
     * @param array $scoreRegularIdList
     * @param $dateStart
     * @param $dateEnd
     * @return array
     */
    private function getUserScoreLog(array $userIdList, array $scoreRegularIdList, $dateStart, $dateEnd) {
        try {
            $userScoreLogArray = [];
            $rawUserScoreLogArray = $this->scoreLogModel->getUserScoreLog($userIdList, $scoreRegularIdList, $dateStart, $dateEnd);
            foreach ($rawUserScoreLogArray as $rawUserScoreLogItem) {
                $userId = $rawUserScoreLogItem["user_id"];
                $userScoreLogArray[$userId][] = $rawUserScoreLogItem;
            }
            return array('status_code' => Retcode::RET_OK_CONTROL, 'data' => $userScoreLogArray);
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    /**
     * 获取指定用户、指定积分id下在给定时间范围内的积分记录数量
     * @param array $userIdList
     * @param $scoreRegularIdList
     * @param $dateStart
     * @param $dateEnd
     * @return array
     */
    private function getUserScoreLogStat(array $userIdList, $scoreRegularIdList, $dateStart, $dateEnd) {
        try {
            $userScoreLogStatArray = [];
            $rawUserScoreLogStatArray = $this->scoreLogModel->countUserScoreLog($userIdList, $scoreRegularIdList, $dateStart, $dateEnd);
            foreach ($rawUserScoreLogStatArray as $rawUserScoreLogStatItem) {
                $userId = $rawUserScoreLogStatItem["user_id"];
                $userScoreLogStatArray[$userId][] = $rawUserScoreLogStatItem;
            }
            return array('status_code' => Retcode::RET_OK_CONTROL, 'data' => $userScoreLogStatArray);
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    private function filterEnableScoreRegular($userScoreRegularItem) {
        if ($userScoreRegularItem['status'] == $this->statusTypeEnable) {
            return true;
        }
        return false;
    }

    private function filterOnceScoreRegular($userScoreRegularArrayEnableItem) {
        if ($userScoreRegularArrayEnableItem['repeat_type'] == $this->repeatTypeOnce) {
            return true;
        }
        return false;
    }

    private function filterDailyScoreRegular($userScoreRegularArrayEnableItem) {
        if ($userScoreRegularArrayEnableItem['repeat_type'] == $this->repeatTypeDaily) {
            return true;
        }
        return false;
    }

    /**
     * 检测对应的积分规则是否可以使用（规则不存在、规则不可用、以达到对应规则的上限）
     * 注意在调用此方法时记录调用者的名称，以及此方法的返回值
     * @param $scoreRegularIdList
     * @param $userId
     * @return array
     */
    private function validateUserScoreRegular($scoreRegularIdList, $userId){
        try {
            // 获取对应的详细积分规则
            $userScoreRegularArray = $this->scoreRegularModel->getScoreRegularList($scoreRegularIdList);
            $userScoreRegularArray = array_column($userScoreRegularArray, '', 'id');

            // 区分出可用的规则
            $userScoreRegularArrayEnable = array_filter($userScoreRegularArray, [$this, 'filterEnableScoreRegular']);
            $userScoreRegularArrayEnable = array_column($userScoreRegularArrayEnable, '', 'id');

            // 从可用积分规则中获取单次的规则
            $onceScoreRegularArray = array_filter($userScoreRegularArrayEnable, [$this, 'filterOnceScoreRegular']);
            $onceScoreRegularArray = array_column($onceScoreRegularArray, '', 'id');

            // 从可用积分规则中获取每日的规则
            $dailyScoreRegularArray = array_filter($userScoreRegularArrayEnable, [$this, 'filterDailyScoreRegular']);
            $dailyScoreRegularArray = array_column($dailyScoreRegularArray, '', 'id');

            // 按照重复类型判断是否达到其重复类型上限
            if (!empty($onceScoreRegularArray)) {
                $onceScoreRegularIdArray = array_column($onceScoreRegularArray, 'id');
                $result = $this->getUserScoreLogStat([$userId], $onceScoreRegularIdArray, null, null);
                if ($result['status_code'] != Retcode::RET_OK_CONTROL || empty($result['data'][$userId])) {
                    throw new Exception($result['status_txt']);
                }
                $onceScoreRegularRealArray = $result['data'][$userId];
                $onceScoreRegularRealArray = array_column($onceScoreRegularRealArray, 'num', 'score_regular_id');
            }
            if (!empty($dailyScoreRegularArray)) {
                $onceScoreRegularIdArray = array_column($onceScoreRegularArray, 'id');
                $dateStart = date("Y-m-d 00:00:00");
                $dateEnd = date('Y-m-d 00:00:00', strtotime('+1 day'));
                $result = $this->getUserScoreLogStat([$userId], $onceScoreRegularIdArray, null, null, $dateStart, $dateEnd);
                if ($result['status_code'] != Retcode::RET_OK_CONTROL || empty($result['data'][$userId]) ) {
                    throw new Exception($result['status_txt']);
                }
                $dailyScoreRegularRealArray = $result['data'][$userId];
                $dailyScoreRegularRealArray = array_column($dailyScoreRegularRealArray, 'num', 'score_regular_id');
            }

            // 生成完整的积分规则状态数组
            $checkScoreRegularIdArray = [];
            foreach ($scoreRegularIdList as $scoreRegularId) {
                $tempArray = [];
                $tempArray['id'] = $scoreRegularId;
                $tempArray['status'] = true;
                $tempArray['description'] = '规则可用';
                if (!array_key_exists($scoreRegularId, $userScoreRegularArray)) {
                    $tempArray['status'] = false;
                    $tempArray['description'] = '规则id不存在';
                    continue;
                }
                if (!array_key_exists($scoreRegularId, $userScoreRegularArrayEnable)) {
                    $tempArray['status'] = false;
                    $tempArray['description'] = '规则id不可用';
                    continue;
                }
                if (array_key_exists($scoreRegularId, $onceScoreRegularArray)) {
                    if ($onceScoreRegularRealArray[$scoreRegularId] >= $onceScoreRegularArray[$scoreRegularId]['repeat_num']) {
                        $tempArray['status'] = false;
                        $tempArray['description'] = '超过最大的可用次数 - once';
                        continue;
                    }
                }
                if (array_key_exists($scoreRegularId, $dailyScoreRegularArray)) {
                    if ($dailyScoreRegularRealArray[$scoreRegularId] >= $dailyScoreRegularArray[$scoreRegularId]['repeat_num']) {
                        $tempArray['status'] = false;
                        $tempArray['description'] = '超过最大的可用次数 - daily';
                        continue;
                    }
                }
            }
            return array('status_code' => Retcode::RET_OK_CONTROL, 'data' => $checkScoreRegularIdArray);
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    // todo 根据传入的积分规则id，执行对应的增/减积分操作
    private function doScoreActionByScoreRegularId($scoreRegularId){
        // 检测对应的积分规则在当前是否可用

    }

    // todo 修改用户表的总积分
    private function editUserScore(){
        // 修改用户的积分

        // 存储用户积分操作记录

    }

}
