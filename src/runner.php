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

/**
 * runner格式
 * Class runner
 * @package cmd
 */
class runner
{
    public $cmd;

    public $des;

    public $pipe;

    public $cwd;

    public $env;

    public $other_option;

    /**
     *
     * runner constructor.
     * @param $cmd
     * @param array $des
     * @param null|string $cwd
     * @param array|null $env
     * @param array|null $other_option
     */
    public function __construct ($cmd, array $des, ?string $cwd = null, ?array $env = null, ?array $other_option= null)
    {
        $this->cmd = join(' ', (array) $cmd);
        $this->des = $des;
        $this->cwd = $cwd;
        $this->env = $env;
        $this->other_option = $other_option;
    }

    /**
     * 转换成索引数组
     * @return array
     */
    public function toArray():array
    {
        return [
            $this->cmd,
            $this->des,
            $this->pipe,
            $this->cwd,
            $this->env,
            $this->other_option,
        ];
    }

    /**
     *
     * @return string
     */
    public function __toString ()
    {
        return "cmd: $this->cmd, des: " .
            json_encode ($this -> des, JSON_UNESCAPED_UNICODE) . ", pipe: " .
            json_encode ($this -> pipe, JSON_UNESCAPED_UNICODE) . ", cwd: $this->cwd, env: " .
            json_encode ($this -> env, JSON_UNESCAPED_UNICODE) . ", other_option: " .
            json_encode ($this -> other_option, JSON_UNESCAPED_UNICODE);
    }
}
