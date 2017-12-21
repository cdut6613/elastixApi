<?php
return array(
    //asterisk配置目录
    'etc'           => '/etc/asterisk/',
    //自动拨号目录
    'outgoing'      => '/var/spool/asterisk/outgoing/',
    //录音文件目录
    'monitor'       => '/var/spool/asterisk/monitor/',
    //agi脚本目录
    'agi'           => '/var/lib/asterisk/agi-bin/',
    //声音文件目录
    'souds'         => '/var/lib/asterisk/sounds/',
    //写入分机信息的文件
    'trunk'         => 'sip_additional.conf',
    'sip'           => 'sip_custom.conf',
    //写入拨号计划的文件
    'extensions'    => 'extensions_custom.conf',
    //中继名称
    'trunk_name'    => '110/',
    //分机状态
    'exten_status'  => array(
        -1  => 'Extension not found',
        0   => 'Idle',
        1   => 'In Use',
        2   => 'Busy',
        4   => 'Unavailable',
        8   => 'Ringing',
        16  => 'On Hold',
    ),
);