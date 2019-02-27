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
use Generator;

class collection
{
    public $runner = [];

    /**
     * 添加执行命令
     * @param $cmd
     * @param string $log_file
     * @return $this
     * @throws Exception
     */
    public function add($cmd, $log_file = 'php://stdout')
    {
        $cmd = $this->verifyCmd ($cmd);
        $log_file = $this->logFile ($log_file, count($this->runner));
        $this->runner[] = [
            'cmd'=>$cmd,
            'log_file'=>$log_file
        ];
        return $this;
    }

    /**
     * 批量添加
     * @param array $cmd_info
     * @return $this
     * @throws \Exception
     */
    public function multiAdd(array $cmd_info)
    {
        foreach($cmd_info as $k=>$v)
        {
            $cmd_info[$k]['cmd'] = $this->verifyCmd ($v['cmd']);
            $cmd_info[$k]['log_file'] = $this->logFile ($v['log_file'], $k);
        }
        $this->runner = array_merge ($this->runner, $cmd_info);
        return $this;
    }

    /**
     *
     * @param $cmd
     * @return string
     * @throws \Exception
     */
    public function verifyCmd($cmd)
    {
        if(empty($cmd))
        {
            throw new Exception("执行命令不能为空！");
        }
        if(is_array ($cmd))
        {
            $cmd = join(' ', $cmd);
        }
        return trim($cmd);
    }

    /**
     * 构建runner对象
     * @param array $cmd_info
     * @return runner
     */
    public function buildRunner(array $cmd_info)
    {
        $out = [ 'file' , $cmd_info['log_file'], 'a' ];
        $des = [ 0 => [ 'pipe' , 'r' ] , 1 => $out , 2 => $out ];
        return new runner($cmd_info['cmd'], $des);
    }

    /**
     * 获取runner
     * @return Generator
     */
    public function getRunner():Generator
    {
        foreach ($this->runner as $cmd)
        {
            yield $this->buildRunner ($cmd);
        }
    }

    /**
     * 处理log文件
     * @param string $log_file
     * @param int $index
     * @return null|string
     * @throws Exception
     */
    public function logFile(string $log_file, $index=false):?string
    {
        if($log_file)
        {
            if(strcasecmp ($log_file, 'php://stdout') != 0)
            {
                $name = basename ($log_file);
                $name = explode ('.', $name);
                $extend = array_pop ($name);
                $name = join('.', $name).".".($index===false?null :"{$index}.").$extend;
                $dir_name = dirname ($log_file);
                if(!is_dir($dir_name))
                {
                    if(!mkdir($dir_name, 0777, true))
                    {
                        throw new Exception("创建{$dir_name}的文件夹失败！");
                    }
                }
                $log_file = "$dir_name/$name";
            }
        }
        else
        {
            $log_file = 'php://stdout';
        }
        return $log_file;
    }
}
