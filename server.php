<?php
/**
 * Created by PhpStorm.
 * User: liu
 * Date: 2018/11/22
 * Time: 1:27
 * 一个简单的demo tcp服务器 php实现
 */

$socket=stream_socket_server("tcp://0.0.0.0:8000",$errno,$errstr);
if(!$socket){
    exit('server up fail!');
}
$reads=[$socket];
$writes=[];
while(1){
    $tmp_read=$reads;
    $tmp_write=$writes;
    //如果没有就阻塞
    $r=stream_select($tmp_read,$tmp_write,$tmp_error,5);
    if($r===false){
       exit('失败!');
    }
    echo '===>read';
    foreach ($tmp_read as $read){
        if($read===$socket){
            $sock=stream_socket_accept($socket,5,$remote_address);
            echo '有新的链接上来了'.$remote_address;
            $reads[]=$sock;
            $writes[]=$sock;
        }
        else{
            $string=trim(fread($read,65535));
            echo "发送的数据是:{$string}";
            if(in_array($read,$tmp_write)){
                //可写
                fwrite($read,'我受到了');
            }
            if(strlen($string)){
                fwrite($read,'关闭!');
                fclose($read);
                $index=array_search($read,$reads,true);
                unset($reads[$index]);
                $index=array_search($read,$writes,true);
                unset($writes[$index]);
            }

        }
    }
}

