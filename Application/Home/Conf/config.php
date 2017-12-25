<?php
return array(
    'DEFAULT_AJAX_RETURN'   => 'json',

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
        -1  => '不存在',
        0   => '空闲',
        1   => '使用中',
        2   => '忙',
        4   => '不可用',
        8   => '振铃中',
        16  => '保持',
    ),

    //百度TTS配置
    'baiduTTS'  => array(
        'id'        =>  '9983077',
        'key'       =>  'gQv1DIirH5UsCNeHCfwfAlOP',
        'secret'    =>  '836a766cea52a90f189564e6ebfdb6b2',
    ),

);