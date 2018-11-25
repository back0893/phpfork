<?php
/**
 * Created by PhpStorm.
 * User: liu
 * Date: 2018/11/26
 * Time: 0:04
 */

class Server
{
    protected $son_pid=[];
    protected $main_pid;
    protected $is_stop=false;
    protected $count=2;
    protected $main_socket;

    public function __construct()
    {
        $this->main_pid=posix_getpid();
        $this->main_socket=stream_socket_server("tcp://0.0.0.1:5000",$errno,$errmsg);
        if(!$this->main_socket){
            exit("错误编号:{$errno},错误信息:{$errmsg}".PHP_EOL);
        }
        $this->installSign();
    }

    public function forkSon(){
        while(count($this->son_pid)<$this->count){
            $pid=pcntl_fork();
            if($pid==-1){
                exit('创建子进程失败'.PHP_EOL);
            }
            elseif($pid==0){
                $this->listen();
                echo "子进程不期望的执行".PHP_EOL;
                exit(250);
            }
            else{
                $this->son_pid[]=$pid;
            }
        }
    }
    public function listen(){
        $reads=[$this->main_socket];
        $write=[];
        while(1){
            $tmp_reads=$reads;
            $tmp_write=$write;
            $r=@stream_select($tmp_reads,$tmp_write,$except,null);
            if($r===false){
                exit('!!!读取失败!!!'.PHP_EOL);
            }
            foreach ($tmp_reads as $read){
                if($read===$this->main_socket){
                    $rev=stream_socket_accept($read,5,$remote_address);
                    $tmp_reads[]=$rev;
                    $tmp_write=$rev;
                }
                else{
                    $message=trim(fread($read,65535));
                    if(strlen($message)==0){
                        $index=array_search($read,$tmp_reads);
                        if($index!==false && $index>=0){
                            unset($tmp_reads[$index]);
                        }
                        $index=array_search($read,$tmp_write);
                        if($index!==false && $index>=0){
                            unset($tmp_write[$index]);
                        }
                        fclose($read);
                    }
                    else{
                        echo "客户端发送来的数据是:{$message}";
                        if(in_array($read,$tmp_write)){
                            fwrite($read,$message);
                        }
                    }
                }
            }
        }
    }
    public function installSign(){
        pcntl_async_signals(true);
        pcntl_signal(SIGINT,[$this,'installSIGINT'],false);
    }
    public function installSIGINT(){
        $pid=posix_getpid();
        if($pid!=$this->main_pid){
            echo "子进程{$pid}退出了".PHP_EOL;
            exit(0);
        }else{
            echo "父进程退出,清理所有的子进程!".PHP_EOL;
            $this->is_stop=true;
        }
    }
    public function domain(){
        while(1){
            $status=0;
            $son_pid=pcntl_wait($status,WUNTRACED);
            if($son_pid>0){
                if(($index=array_search($son_pid,$this->son_pid))!==false){
                    array_splice($this->son_pid,$index,1);
                    echo "启动新的子进程!!".PHP_EOL;
                    $this->forkSon();
                }
            }
            if($this->is_stop){
                $this->stopAll();
            }
        }
    }
    public function stopAll(){
        foreach ($this->son_pid as $pid){
            posix_kill($pid,SIGINT);
        }
        sleep(1);
        echo "主进程退出,子进程退出".PHP_EOL;
        exit(0);
    }
    public function run(){
        $this->forkSon();
        $this->domain();
    }
}