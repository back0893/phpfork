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
    protected $back=false;
    protected $pid_path;
    protected $user='root';
    protected $group='root';
    public function __construct()
    {
        $this->main_socket=stream_socket_server("tcp://0.0.0.0:5000",$errno,$errmsg);
        if(!$this->main_socket){
            exit("错误编号:{$errno},错误信息:{$errmsg}".PHP_EOL);
        }
        $this->pid_path=__DIR__.'/pid.log';
        $this->installSign();
    }

    /**
     * 生成子进程
     */
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

    /**
     * 子进程监听
     */
    public function listen(){
        $reads=[$this->main_socket];
        $writes=[];
        while(1){
            $tmp_reads=$reads;
            $tmp_write=$writes;
            $r=@stream_select($tmp_reads,$tmp_write,$except,null);
            if($r===false){
                exit('!!!读取失败!!!'.PHP_EOL);
            }
            foreach ($tmp_reads as $read){
                if($read===$this->main_socket){
                    $rev=stream_socket_accept($read,5,$remote_address);
                    $reads[]=$rev;
                    $writes[]=$rev;
                }
                else{
                    $message=trim(fread($read,65535));
                    if(strlen($message)==0){
                        $index=array_search($read,$reads);
                        if($index!==false && $index>=0){
                            unset($reads[$index]);
                        }
                        $index=array_search($read,$writes);
                        if($index!==false && $index>=0){
                            unset($writes[$index]);
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

    /**
     * 安装信号
     */
    public function installSign(){
        pcntl_async_signals(true);
        pcntl_signal(SIGINT,[$this,'installSIGINT'],false);
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    /**
     * 信号处理
     */
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

    /**
     * 守护的父进程,用来管理子进程
     *  重启或者关闭
     */
    public function domain(){
        $this->main_pid=posix_getpid();
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

    /**
     * 退出全部清理
     */
    public function stopAll(){
        if($this->main_pid==posix_getpid()){
            foreach ($this->son_pid as $pid){
                posix_kill($pid,SIGINT);
            }
            sleep(1);
            echo "主进程退出".PHP_EOL;
            exit(0);
        }else{
            echo "子进程退出".PHP_EOL;
            exit(0);
        }

    }

    /**
     * 解析shell 命令
     */
    public function parseShell(){
        global $argv;
        array_shift($argv);
        $order=array_shift($argv);
        $ext=array_shift($argv);
        if($ext==='-d'){
            $this->back=true;
        }
        $master_pid=$this->getMasterPid();
        if($this->masterIsAlive($master_pid)){
            if($order=='start'){
                exit("服务器已经启动\n");
            }elseif($order=='stop'){
                exit("服务器还未启动\n");
            }
        }
        switch ($order){
            case "start":
                break;
            case 'stop':
                posix_kill($master_pid,SIGINT);
                exit();
                break;
        }
    }
    public function getMasterPid(){
        if(file_exists($this->pid_path) && is_readable($this->pid_path)){
            $master_pid=file_get_contents($this->pid_path);
        }else{
            $master_pid=0;
        }
        return $master_pid;
    }
    public function masterIsAlive($master_pid){
        if($master_pid){
            return posix_kill($master_pid,SIG_DFL) && ($master_pid !=posix_getpid());
        }
        return false;
    }
    public function back(){
        if(!$this->back) return ;
        $this->setUserAndGroup();
        umask(0);
        $pid=pcntl_fork();
        if($pid>0){
            exit(0);
        }elseif($pid==-1){
            exit("创建守护进程失败!!--1\n");
        }
        $sid=posix_setsid();
        if($sid==-1){
            exit("创建守护进程失败!!--2\n");
        }
        $pid=pcntl_fork();
        if($pid>0){
            exit(0);
        }elseif($pid==-1){
            exit("创建守护进程失败!!--3\n");
        }
        $this->saveMasterPid();
    }
    public function saveMasterPid(){
        $this->main_pid=posix_getpid();
        if($this->back){
            file_put_contents($this->pid_path,$this->main_pid);
        }
    }
    public function run(){
        $this->parseShell();
        $this->back();
        $this->forkSon();
        $this->domain();
    }
    public function setUserAndGroup(){
        $user_info = posix_getpwnam($this->user);
        if(!$user_info){
            exit('选择的用户不存在!!'.PHP_EOL);
        }
        $uid=$user_info['uid'];
        $gid=$user_info['gid'];
        if($this->group){
            $group_info=posix_getgrnam($this->group);
            if(!$group_info){
                exit('选择的用户组不存在!!'.PHP_EOL);
            }
            $gid=$group_info['gid'];
        }
        if($uid!=posix_getuid() || $gid!=posix_getgid()){
            if(!posix_setgid($gid) || !posix_initgroups($user_info['name'],$gid) || !posix_setuid($uid)){
                exit("改变用户,用户组失败!".PHP_EOL);
            }
        }
    }
}

$server=new Server();
$server->run();
