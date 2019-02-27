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

class factory
{
    /**
     *
     * @var collection
     */
    public $collection;

    /**
     *
     * @var executor
     */
    public $executor;

    /**
     * 主进程日志目录 默认为标准输出
     * @var string
     */
    public $main_log = '';//'log/out.log';

    /**
     * @param string $log_file
     * @return factory
     */
    public static function getInstance($log_file = '')
    {
        $instance = new static;
        $instance->main_log = $log_file;
        $instance->collection = new collection();
        return $instance;
    }

    /**
     * 添加命令行数据
     * @param string $cmd
     * @param string $cmd_log_file
     * @param string $cmd_flag
     * @return $this
     * @throws Exception
     */
    public function addCmd(string $cmd, string $cmd_log_file = '')
    {
        $this->collection->add ($cmd, $cmd_log_file?:$this->main_log);
        return $this;
    }

    /**
     * 重置
     * @return $this
     */
    public function resetCmd()
    {
        $this->collection->runner = [];
        return $this;
    }

    /**
     * 获取执行实例
     * @return executor
     * @throws \Exception
     */
    public function getExecutor()
    {
        if(!$this->executor)
        {
            if(empty($this->collection->runner))
            {
                throw new Exception("没有设置要运行的命令！");
            }
            $this->executor = new executor($this->collection, $this->collection->logFile ($this->main_log));
        }
        return $this->executor;
    }

    /**
     * 停止任务
     * @param string $title
     * @param int $signal
     * @return $this`
     */
    public function stopByTitle(string $title,int $signal=9)
    {
        $title = str_replace ('\'', '', $title);
        $title = str_replace ('"', '', $title);
        if(stripos(PHP_OS, 'win')===false)
        {
            $cmd_tpl = "ps aux | grep '%s' | grep -v grep | awk '{print \"kill -s %s \"$2 }' | sh" ;
        }
        else
        {
            $cmd_tpl = "taskkill /F /IM php.exe";
        }
        $cmd = sprintf ( $cmd_tpl, $title, $signal) ;
        exec ($cmd, $out, $res );
        if($out)
        {
            print_r($out);
        }
        return $res;
    }

    /**
     * 停止所有的命令行
     * @param int $signal
     */
    public function stopAll(int $signal= 9)
    {
        foreach($this->collection->getRunner () as $runner)
        {
            $this->stopByTitle ($runner->cmd, $signal);
        }
    }
}
