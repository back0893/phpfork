<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 2018/11/20
 * Time: 15:43
 * 测试后台守护进程
 */



umask(0);
//fork 第一次
$pid=pcntl_fork();

if($pid==-1){
    exit('创建进程失败');
}
elseif($pid>0){
    exit(0);
}elseif($pid==0){
    //设置一个session组,并作为leader,脱离之前的会话和进程组
    if(posix_setsid()===-1){
        exit('启动session失败');
}
    //再是fork 防止启动到终端的控制
    $pid=pcntl_fork();
    if($pid==-1){
        exit('创建进程失败'.PHP_EOL);
    }elseif ($pid>0){
        exit(0);
    }else{
        //实现一个控制,来在其他地方停止这个守护进程
        pcntl_async_signals(true);
        pcntl_signal(SIGINT,function($signo){
            echo '我死了o(ﾟДﾟ)っ啥!'.PHP_EOL;
            exit(0);
        });

        cli_set_process_title('!test_domain!');
        echo __DIR__.'/domain.log'.PHP_EOL;
        $fp=fopen(__DIR__.'/domain.log','w');
        for($i=0;;$i++){
            $l=fwrite($fp,"====================>{$i}\n");
            sleep(1);
        }
        fclose($fp);
    }
}
