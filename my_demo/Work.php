<?php
/**
 * Created by IntelliJ IDEA.
 * User: liu
 * Date: 2018/12/18
 * Time: 1:16
 */

//1一个主进程
//2个子进程
class Work
{
    protected $count=2;
    protected static $is_stop=false;
    protected static $son_child_pid=[];
    protected static $master_pid;
    protected $socket;

    public function __construct()
    {
        $this->socket=stream_socket_server("tcp://0.0.0.0:10086",$errno,$errmsg);
        if(!$this->socket){
            echo "err no=>{$errno},message=>{$errmsg}".PHP_EOL;
            exit(250);
        }
    }

    /**
     * 主进程的实现守护进程
     */
    public function domain(){
        $pid=pcntl_fork();
        if($pid<0){
            echo 'fork 主进程 失败!!'.PHP_EOL;
            exit(250);
        }
        if($pid>0){
            exit(0);
        }
        //第一次fork,需要将本进程提升成leader
        //为了拍拖之前的会话和进程
        if(posix_setsid()===-1){
            echo '启动session失败!'.PHP_EOL;
            exit(250);
        }
        //这里再次fork为了防止被终端控制
        $pid=pcntl_fork();
        if($pid<0){
            echo 'fork 主进程 失败!!'.PHP_EOL;
            exit(250);
        }
        if($pid>0){
            exit(0);
        }
        $this->masterHandler();
    }

    public function masterHandler(){
        pcntl_signal(SIGINT,function($sign){
            echo '!主进程退出!'.PHP_EOL;
            $this->closeSon();
            exit(0);
        });
    }
    public function closeSon($pid=null){
        if(is_null($pid)){
            foreach (self::$son_child_pid as $pid){
                posix_kill($pid,SIGINT);
            }
        }
        else{
            posix_kill($pid,SIGINT);
        }
    }

    public function forkSon(){
        while(count(self::$son_child_pid)<2){
            $pid=pcntl_fork();
            if($pid<0){
                echo 'fork 子进程 失败!!'.PHP_EOL;
                $this->closeSon();
                exit(250);
            }
            if($pid>0){
                self::$son_child_pid[]=$pid;
                continue;
            }
            break;
        }
        $this->sonHandler();
        $this->listen();
        echo "子进程不期望的执行".PHP_EOL;
        exit(250);
    }
    public function sonHandler(){
        pcntl_signal(SIGINT,function($sign){
            $pid=posix_getpid();
            echo "子进程{$pid}退出!!".PHP_EOL;
            exit(0);
        });
    }
    public function listen(){
        $read=[$this->socket];
        $write=[];
        while(true){
            $tmp_read=$read;
            $tmp_write=$write;
            $r=@stream_select($tmp_read,$tmp_write,$except,null);
            if($r===false){
                continue;
            }
            foreach ($tmp_read as $t){
                if($t===$this->socket){
                    $rev=stream_socket_accept($this->socket,5,$remote_address);
                    if($rev===false) continue;
                    $read[]=$rev;
                    $write[]=$rev;
                }
                else{
                    $message=trim(fread($t,65535));
                    if(strlen($message)==0){
                        $index=array_search($t,$read);
                        if($index!==false){
                            unset($tmp_read[$index]);
                        }
                        $index=array_search($t,$write);
                        if($index!==false){
                            unset($tmp_read[$index]);
                        }
                    }
                    else{
                        echo "客户端发送来的数据是:{$message}";
                        if(in_array($t,$tmp_write)){
                            fwrite($t,$message);
                        }
                    }
                }
            }
        }
    }
    public function run(){
        $this->domain();
        $this->listen();
        $this->master();
    }
}