<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 2018/11/23
 * Time: 15:23
 * 目前只要收到信号就退出,简单点
 */

$son=[];
$count=2;
function start_son(){
    pcntl_signal(SIGHUP,function(){
       echo '啊,我死了啊o_O!'.PHP_EOL;
       exit(0);
    });
    $pid=posix_getpid();
    $fp=fopen(__DIR__."/{$pid}.log",'w');
    while(1){
        pcntl_signal_dispatch();
        fwrite($fp,date("Y-m-d H:i:s").PHP_EOL);
        sleep(1);
    }
    echo '未知错误!'.$pid.PHP_EOL;
    exit(250);
}

echo '主进程启动!'.PHP_EOL;
//主进程循环判断是否子进程退出,
while(1){

    while(count($son)<$count){
        $pid=pcntl_fork();
        if($pid==-1){
            echo '生成子进程失败!'.PHP_EOL;
            exit(0);
        }
        elseif ($pid==0){
            $son[]=$pid;
            pcntl_signal(SIGHUP,function ()use($son){
                //关闭子进程和自己
                foreach ($son as $pid){
                    posix_kill($pid,SIGHUP);
                }
                echo "!!主进程退出!!".PHP_EOL;
                exit(0);
            });
        }else{
            start_son();
        }
    }

    pcntl_signal_dispatch();
    $status=0;
    $child_pid=pcntl_wait($status,WUNTRACED);
    pcntl_signal_dispatch();
    if($child_pid){
        $index=array_search($child_pid,$son);
        if($index!==false){
            array_splice($son,$index,1);
        }
    }
}
