<?php
/**
 * 一个简单的demo,包含1个主进程,2个子进程
 * 子进程负责写入文件
 */


class my_worker{
    protected $son_pid=[];
    protected $master_pid;
    protected $count=2;


    public function __construct()
    {
        $this->master_pid=posix_getpid();
    }
    public function run(){
        echo 'master is run then fork 2 son worker';
        while(count($this->son_pid)<$this->count){
            $pid=pcntl_fork();
            if($pid==-1){
                exit('fork 失败!');
            }
            elseif($pid==0){
                //子进程
                $this->sonWork($pid);
                $this->installSign();
                echo '子进程 ====>'.$pid.'意外退出!';
                exit(250);
            }else{
                $this->son_pid[]=$pid;
            }
        }
    }
    public function sonWork($pid){
        $fp=fopen(__DIR__."/{$pid}.log",'w');
        while(1){
            fwrite($fp,date("Y-m-d H:i:s"));
            sleep(1);
        }
    }
    public function installSign(){
        //安装信号量
    }
}