<?php
// +----------------------------------------------------------------------
// | cmdProcess
// +----------------------------------------------------------------------
// | Copyright (c) 2019
// +----------------------------------------------------------------------
// | Licensed GPL-3.0
// +----------------------------------------------------------------------
// | Author: zhangjs
// +----------------------------------------------------------------------
// | Date: 2019-2-27
// +----------------------------------------------------------------------
// | Time: 上午 10:23
// +----------------------------------------------------------------------
namespace phpth\process;

use Exception;
use Throwable;

class executor
{
    /**
     * 正在执行列表
     * @var array
     */
    protected $run_list = [];

    /**
     * 停止列表
     * @var array
     */
    protected $stop_list = [];

    /**
     *
     * @var resource
     */
    protected $log_handle ;

    /**
     *
     * @var int
     */
    public $log_write_flag = FILE_APPEND;

    /**
     *
     * @var array
     */
    protected $runner;

    /**
     * 重试次数
     */
    public const OPEN_FAIL_RETRY = 3;

    /**
     * 重试间隔
     */
    public const OPEN_FAIL_RETRY_AFTER = 0.01;

    /**
     *
     * executor constructor.
     * @param collection $collection
     * @param string $log_file
     * @throws \Exception
     */
    public function __construct (collection $collection, string $log_file = 'php://stdout')
    {
        if(empty($log_file))
        {
            $log_file = 'php://stdout';
        }
        $this->log_handle = $log_file;
        foreach($collection->getRunner () as $no=> $runner)
        {
            $this->runner[] = $runner;
        }
        $this->init ();
    }

    /**
     *
     */
    protected function init():void
    {
        // 初始化
    }

    /**
     * 执行命令进程
     * @param int $run_cmd_times 0: 代表守护执行的进程，如果进程停止则从新开启进程。 >0 : 代表守护次数
     * @return $this
     * @throws Throwable
     */
    public function run(int $run_cmd_times = 1):executor
    {
        $run_cmd_times = (int) abs ( $run_cmd_times);
        if(!$run_cmd_times)
        {
            $this->stop_list = array_combine ( array_keys ( $this->runner) , array_fill ( 0 , count($this->runner) , false));
        }
        else
        {
            $this->stop_list = array_combine ( array_keys ( $this->runner) , array_fill ( 0 , count($this->runner) , $run_cmd_times-1));
        }
        try{
            foreach($this->runner as $k=>$runner)
            {
                $this->run_list[$k] = $this->open ($runner);
            }
        }catch (Throwable $e)
        {
            unset($this->run_list[$k]);
            $this->stop ();
            throw $e;
        }
        return $this;
    }

    /**
     * 等待进程执行结束
     * @param bool $block 是否阻塞等待
     * @param float $interval 等待间隔
     * @return $this
     */
    public function wait(bool $block = true,  float $interval = 0.5):executor
    {
        do{
            foreach ( $this -> runner as $runner_no => $runner )
            {
                if(!empty($this->run_list[$runner_no]))
                {
                    $this->checkRun ($runner_no, $this->run_list[$runner_no]);
                }
            }
            $this->sleep ($interval);
        }while($block && count ($this->run_list)>0);
        $block ?$this->log('命令全部执行结束！', 'info'):null;
        return $this;
    }

    /**
     *
     * @return int
     */
    public function isRun()
    {
        return count($this->run_list);
    }

    /**
     *
     * @param $time
     * @return void|null
     */
    public function sleep(float $time):void
    {
        usleep((int)$time*1000000);
    }

    /**
     * 检查运行状态并运行已经停止且还需要运行的runner
     * @param int $runner_no
     * @param $runner_resource
     * @return array|bool
     */
    protected function checkRun(int $runner_no, $runner_resource):bool
    {
        $status = $this -> processStatus ($runner_resource);
        if($status && $status['running'])
        {
            $run = true ;
        }
        else if(!$status || !$status[ 'running'] )
        {
            if($this -> stop_list[ $runner_no ] === false || $this -> stop_list[ $runner_no ] > 0)
            {
                $this -> reOpen ($runner_no);
                $run = true;
            }
            else
            {
                $run = false;
            }
        }
        else
        {
            $run = false ;
        }
        if(!$run && $this -> stop_list[ $runner_no ] !== false && $this -> stop_list[ $runner_no ] <= 0 ){
            unset($this->run_list[$runner_no]);
        }
        return $run;
    }

    /**
     * 重建runner
     * @param int $runner_no
     * @return bool
     */
    protected function reOpen(int $runner_no):bool
    {
        try{
            $retry = 0 ;
            retry:
            $this->run_list[$runner_no] = $this->open ($this->runner[$runner_no], true);
            $this -> stop_list[ $runner_no ] === false?:$this->stop_list[$runner_no] --;
            $res = true ;
            $this->log ("重建runner成功！已经重试{$retry}次，runner：{$this->runner[$runner_no]}", 'info');
        }catch (Exception $e)
        {
            if($retry<= executor::OPEN_FAIL_RETRY)
            {
                $retry++;
                $this->sleep (executor::OPEN_FAIL_RETRY_AFTER);
                goto retry;
            }
            $this->log ("重建runner失败！已经重试".executor::OPEN_FAIL_RETRY."次", 'error');
            $res = false ;
        }
        return $res;
    }

    /**
     * 执行runner
     * @param Runner $runner
     * @param bool $re_open
     * @return bool|resource
     * @throws \Exception
     */
    protected function open(Runner $runner, $re_open=false)
    {
        $res = proc_open ($runner->cmd, $runner->des, $runner->pipe, $runner->cwd, $runner->env, $runner->other_option);
        if(!is_resource ($res))
        {
            $this->openFailException ($runner, $re_open);
        }
        return $res;
    }

    /**
     *
     * @param \phpth\cmdExec\Runner $runner
     * @param bool $re_open
     * @throws \Exception
     */
    protected function openFailException(Runner $runner, bool $re_open):void
    {
        $err = ($re_open?'重建':'创建')."命令进程失败！[runner] $runner";
        $this->log ($err,'error');
        throw new Exception($err);
    }

    /**
     * 进程状态
     * @param $resource resource
     * @return array |bool
     */
    protected function processStatus($resource)
    {
        if(!is_resource ($resource))
        {
            goto end ;
        }
        $info = proc_get_status ( $resource) ;
        if(!$info)
        {
            goto end ;
        }
        $res['pid'] = $info['pid'];
        $res['cmd'] = $info['command'];
        if($info['running'])
        {
            $res['running'] = true;
            $res['msg'] = '';
        }
        else
        {
            $res['running'] = false;
            $res['msg'] = "pid: {$info['pid']}, 退出码: {$info['exitcode']}".
                ($info['signaled']?", 因信号 {$info['termsig']} 退出":null).
                ($info['stopped']?", 因信号 {$info['stopsig']} 停止!":null);
        }
        end:
        return $res??false;
    }

    /**
     * 停止进程
     * @param bool $process_no 进程在进程对象中的编号
     * @param int $signal default=SIGKILL
     * @return bool|null
     * @throws Exception
     */
    protected function stop ( $process_no = true , $signal = 9 ):?bool
    {
        if ( $process_no === true ) {
            $run_list = $this -> run_list;
        }
        else if ( isset( $this -> run_list[ $process_no ] ) ) {
            $run_list[ $process_no ] = $this -> run_list[ $process_no ];
        }
        else {
            throw new Exception( "进程编号：{$process_no} 不存在！" );
        }
        foreach ( $run_list as $k => $v ) {
            $this -> stop_list[$k] = 0;
            proc_terminate ( $v, $signal );
        }
        return true;
    }

    /**
     * 日志记录
     * @param $msg
     * @param string $lev ：info,error,debug,notice
     */
    public function log(string $msg, string $lev='info'):void
    {
        $msg = date('Y-m-d H:i:s')." [{$lev}] $msg ".PHP_EOL;
        file_put_contents ($this->log_handle, $msg, $this->log_write_flag);
    }
}
