<?php
/**
 * Created by PhpStorm.
 * User: liu
 * Date: 2018/11/21
 * Time: 0:12
 */

echo 'kill domain';
posix_kill(1885,SIGINT);
