<?php
     function stopAll($sig){
    echo "master has a sig $sig\n" ;
}

$master_id = getmypid();

$pid = pcntl_fork();
if($pid > 0)
{
    pcntl_signal(SIGINT,'stopAll') ;
   pcntl_signal_dispatch();
    $epid = pcntl_wait($status,WUNTRACED);
    pcntl_signal_dispatch();
    echo "parent process {$master_id}, child process {$pid}\n";
    if($epid){
        echo "child $epid exit \n" ;
    }
}
else
{
    $id = getmypid();
    echo "child process,pid {$id}\n";
    echo "send signal to master\n";
    posix_kill($master_id, SIGINT);
    sleep(60);
}
