<?php
/**
 * Created by PhpStorm.
 * User: liu
 * Date: 2018/11/21
 * Time: 0:01
 */

pcntl_async_signals(true);

echo '注册信号!'.PHP_EOL;
pcntl_signal(SIGINT,function($signo){
    echo '=========='.'我受到了信号'.'=============='.PHP_EOL;
});

echo '发送一个,自动执行!'.PHP_EOL;
posix_kill(posix_getpid(),SIGINT);


