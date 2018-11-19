# PHPForker

## What is it
A simple Multi-Process programming skeleton written in PHP and learned much from [Workerman](https://www.workerman.net), which remove the part of Network Event Library, it aims at two aspects by programming personally：
* `Help us study PHP Multi-Process programming` 
* `Help us find out how Workerman core works` 

## PHPForker是什么
PHPForker是一个PHP多进程编程骨架，借鉴了Workerman诸多优良编程思想，剥离了其中的网络事件库抽象部分，集中围绕多进程编程，为了便于直观的调试以及保持最轻的多进程骨架，所以简单的内嵌了一个基于select多路复用技术的 TCP & UDP Server。为了学习如此优秀的Workerman框架，金牛座亲自撸了一遍，本项目旨在深入学习和分享:
* `学习PHP多进程编程思想` 
* `学习Workerman内核工作原理`

## Prerequisites
* \>= PHP 5.3
* A POSIX compatible operating system (Linux, OSX, BSD)  
* POSIX extensions for PHP  
* PCNTL extensions for PHP  

##fork 自 https://github.com/blogdaren

