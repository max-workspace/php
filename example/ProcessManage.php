<?php

use \stdClass;
use \Exception;
/*
 * Created by NetBeans.
 * User: max
 * Date: 2018-8-31
 * Time: 15:54:22
 */

/**
 * Description of Deamon
 * PHP多进程基类，该基类依赖PHP的pcntl扩展，使用前先检测是否安装对应扩展
 * 父进程作为多个子进程的管理器，监控子进程状态
 * 构造函数中设置要生成的子进程个数，workDispatch负责处理具体的逻辑
 * @author max
 */
class ProcessManage {
    
    /**
     * 父进程的pid信息
     * @var type 
     */
    protected $mainPid;
    
    /**
     * 子进程的个数
     * @var type 
     */
    protected $processNum;
    
    public function __construct(int $processNum) {
        try {
            $this->mainPid = getmypid();
            $this->processNum = $processNum;
            if(!$this->mainPid) {
                throw new Exception('could not get main mainPid');
            }
            $this->createProcess($this->processNum);
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }
    
    protected function createProcess(int $processNum) {
        echo $this->mainPid."\n";
        for($i=1; $i<=$processNum; $i++){
            $ppid = getmypid();
            // 保证只有主进程可以创建子进程
            if($ppid!=$this->mainPid){
                return;
            }
            // 创建进程
            $pid = pcntl_fork();
            if($pid == -1){
                throw new Exception('could not fork process');
            }else if($pid){
                // 父进程的操作
            }else{
                // 子进程的操作
                $this->workDispatch($ppid,$i);
            }
        }
    }

    public function workDispatch($ppid,$i){
        echo " ppid => {$ppid} pid => ".getmypid()." i => {$i}  \n";
    }
}

$test = new ProcessManage(10);
