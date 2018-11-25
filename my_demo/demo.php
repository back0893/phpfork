<?php
/**
 * 一个简单的demo,包含1个主进程,2个子进程
 * 子进程负责写入文件
 */


class my_worker{
    protected $son_pid=[];
    protected $master_pid;
    protected $count=2;
    public static $IS_STOP=false;

    public function __construct()
    {
        $this->master_pid=posix_getpid();
        cli_set_process_title('test_demo');
    }
    public function run(){
        echo 'master is run then fork 2 son worker'.PHP_EOL;
        $this->forkSon();
        if($this->master_pid==posix_getpid()){
            $this->domain();
        }
    }
    public function domain(){
        while(true){
            $status=0;
            //等待,这里会亮信号一起阻塞!
            echo '阻塞,等待子进程退出或者受到一个信号中断!'.PHP_EOL;
            pcntl_signal_dispatch();
            $son_pid=pcntl_wait($status,WUNTRACED);
            pcntl_signal_dispatch();
            if($son_pid>0){
                if(($index=array_search($son_pid,$this->son_pid))!==false){
                    array_splice($this->son_pid,$index,1);
                    echo '!!!新启动子进程!!!';
                    $this->forkSon();
                }
            }
            if($this::$IS_STOP){
                $this->stopAll();
            }
        }
    }
    public function forkSon(){
        if($this::$IS_STOP){
            return '';
        }
        $this->installSign();
        while(count($this->son_pid)<$this->count){
            $pid=pcntl_fork();
            if($pid==-1){
                exit('fork 失败!');
            }
            elseif($pid>0){
                $this->son_pid[]=$pid;
                var_dump($this->son_pid);
            }else{
                //子进程
                $pid=posix_getpid();
                echo "!!子进程{$pid}启动!!".PHP_EOL;
                $this->sonWork($pid);
                echo '子进程 ====>'.$pid.'意外退出!';
                exit(250);
            }
        }
    }
    public function sonWork($pid){
        $fp=fopen(__DIR__."/{$pid}.log",'w');
        while(1){
            fwrite($fp,date("Y-m-d H:i:s\n"));
            sleep(1);
            pcntl_signal_dispatch();
        }
    }
    public function installSign(){
        //安装信号量
        //!这是触发安装信号,异步触发!
//        pcntl_async_signals(true);
        echo '注册异步信号!'.PHP_EOL;
        $obj=$this;
        pcntl_signal(SIGTERM,function($sign)use($obj){
            echo "!!主进程退出!!";
            my_worker::$IS_STOP=true;
            $obj->stopAll();
        },false);
        pcntl_signal(SIGINT,function($sign){
            echo "!!!!我死了!!!!".PHP_EOL;
            exit(0);
        });
    }
    public function stopAll(){
        $sons=$this->son_pid;
        foreach ($sons as $son){
            posix_kill($son,SIGINT);
        }
        echo "!!全部停止!!";
        exit(0);
    }
    public function __destruct()
    {
        if($this->master_pid==posix_getpid()){
            $this::$IS_STOP=true;
            $this->stopAll();
        }
    }
}

$do=new my_worker();
$do->run();