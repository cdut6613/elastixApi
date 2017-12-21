<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/24
 * Time: 14:45
 */
namespace Home\Controller;

class SipController extends BaseController{
    protected $appendFile = false;
    /**
     * 创建单个SIP用户,默认创建后重启
     */
    public function create($exten=null,$secret=null,$reload=true)
    {
        $this->exten = $exten;
        if ($secret) $this->secret = $secret;
        //创建分机用户
        $this->sipCustom();
        //更新配置
        if ($this->appendFile==false){
            $this->appendFileToExtensionsConf();
        }
        $this->extLocalCustomConf();
        $this->fromDidDirectIvrCustomConf();
        $this->enableDatabase();
        if ($reload){
            $this->ami->Command("reload");
            $this->ajaxReturn(['code'=>0,'msg'=>'exten create successed','data'=>I()]);
        }
    }

    /**
     * 批量创建SIP用户
     */
    public function createLists($from=null,$to=null,$secret=null)
    {
        $from = intval(I("from"));
        $to = intval(I("to"));
        for ($n=$from;$n<=$to;$n++){
            $this->create($n,$secret,false);
        }
        $this->ami->Command("reload");
        $this->ajaxReturn(['code'=>0,'msg'=>'exten create lists successed','data'=>I()]);
    }


    /**
     * 写入sip_custom.conf信息
     */
    public function sipCustom()
    {
        //判断在exten是否已经存在
        $content = parse_ini_file(C("etc").C("sip"),true);
        if(!array_key_exists($this->exten,$content)){
            $temp = array(
                "deny"=>"0.0.0.0/0.0.0.0",
                "secret"=>"962540",
                "dtmfmode"=>"rfc2833",
                "canreinvite"=>"no",
                "context"=>"from-internal",
                "host"=>"dynamic",
                "trustrpid"=>"yes",
                "sendrpid"=>"no",
                "type"=>"friend",
                "nat"=>"no",
                "port"=>"5060",
                "qualify"=>"yes",
                "qualifyfreq"=>"60",
                "transport"=>"udp",
                "avpf"=>"no",
                "icesupport"=>"no",
                "dtlsenable"=>"no",
                "dtlsverify"=>"no",
                "dtlssetup"=>"actpass",
                "encryption"=>"no",
                "callgroup"=>"",
                "pickupgroup"=>"",
                "mailbox"=>"",
                "permit"=>"0.0.0.0/0.0.0.0",
                "callcounter"=>"yes",
                "faxdetect"=>"no",
            );
            $info = array(
                "secret"=>$this->secret,
                "dial"=>"SIP/".$this->exten,
                "callerid"=>$this->exten,
            );
            $extenInfo = array_merge($temp,$info);

            $file = fopen(C("etc").C("sip"),"ab");
            fwrite($file,"\n");
            fwrite($file,"[".$this->exten."]\n");
            foreach ($extenInfo as $k=>$v){
                fwrite($file,$k."=".$v."\n");
            }
            fwrite($file,"\n");
            fclose($file);
        }
    }

    /**
     * 向extensions.conf配置文件里追加信息：导入两个配置文件用于sip
     * 同时创建这两个配置文件
     */
    public function appendFileToExtensionsConf()
    {
        //文件信息
        $extensionsConf = C("etc")."extensions.conf";
        $fileArr = array('ext-local-custom','from-did-direct-ivr-custom');
        $ext = ".conf";

        //ext-local-custom.conf  from-did-direct-ivr-custom.conf 是否存在
        foreach ($fileArr as $name){
            if (!file_exists(C("etc").$name.$ext)){
                file_put_contents(C("etc").$name.$ext,"[".$name."]\n");
            }
        }

        //extensions.conf 文件里是否已经添加了导入信息,没有添加则执行添加
        $content = file_get_contents($extensionsConf);
        $file = fopen($extensionsConf,"ab");
        foreach ($fileArr as $name){
            if(strpos($content,"#include ".$name.$ext)==false){
                fwrite($file,"#include ".$name.$ext."\n");
            }
        }
        fclose($file);
        $this->appendFile = true;
    }

    /**
     * 向ext-local-custom.conf里需要写入的信息
     */
    public function extLocalCustomConf()
    {
        $file = C("etc")."ext-local-custom.conf";
        //$content = file_get_contents($file);
        //查询当前分机号是否已经写入,没有则写入
        //if (strpos($content,$this->exten)==false){
            $temp = array(
                "exten => ".$this->exten.",1,Set(__RINGTIMER=\${IF($[\${DB(AMPUSER/".$this->exten."/ringtimer)} > 0]?\${DB(AMPUSER/".$this->exten."/ringtimer)}:\${RINGTIMER_DEFAULT})})",
                "exten => ".$this->exten.",n,Macro(exten-vm,novm,".$this->exten.",0,0,0)",
                "exten => ".$this->exten.",n(dest),Set(__PICKUPMARK=)",
                "exten => ".$this->exten.",n,Goto(\${IVR_CONTEXT},return,1)",
                "exten => ".$this->exten.",hint,SIP/".$this->exten.",CustomPresence:".$this->exten,
            );
            $file = fopen($file,"ab");
            fwrite($file,"\n");
            foreach ($temp as $line){
                fwrite($file,$line."\n");
            }
            fwrite($file,"\n");
            fclose($file);
        //}
    }

    /**
     * 向from-did-direct-ivr-custom.conf里需要写入的信息
     */
    public function fromDidDirectIvrCustomConf()
    {
        $file = C("etc")."from-did-direct-ivr-custom.conf";
        //$content = file_get_contents($file);
        //查询当前分机号是否已经写入,没有则写入
        //if (strpos($content,$this->exten)==false){
            $temp = array(
                "exten => ".$this->exten.",1,Macro(blkvm-clr,)",
                "exten => ".$this->exten.",n,Set(__NODEST=)",
                "exten => ".$this->exten.",n,Goto(from-did-direct,".$this->exten.",1)",
            );
            $file = fopen($file,"ab");
            fwrite($file,"\n");
            foreach ($temp as $line){
                fwrite($file,$line."\n");
            }
            fwrite($file,"\n");
            fclose($file);
        //}
    }

    /**
     * 添加到数据库
     */
    public function enableDatabase()
    {
        $this->ami->Command('database put AMPUSER/'.$this->exten.' recording ""');
        $this->ami->Command('database put AMPUSER/'.$this->exten.'/recording/out external always');
        $this->ami->Command('database put AMPUSER/'.$this->exten.'/recording/out internal always');
        $this->ami->Command('database put AMPUSER/'.$this->exten.'/recording/in external always');
        $this->ami->Command('database put AMPUSER/'.$this->exten.'/recording/in internal always');
        $this->ami->Command('database put AMPUSER/'.$this->exten.' cidname '.$this->exten);
        $this->ami->Command('database put AMPUSER/'.$this->exten.' cidnum '.$this->exten);
        $this->ami->Command('database put AMPUSER/'.$this->exten.' device '.$this->exten);
        $this->ami->Command('database put DEVICE/'.$this->exten.' user '.$this->exten);
        $this->ami->Command('database put DEVICE/'.$this->exten.' dial SIP/'.$this->exten);
    }

    /**
     * 删除ext-local-custom.conf from-did-direct-ivr-custom.conf
     */
    public function removeCustomConf()
    {
        $fileArr = array('ext-local-custom','from-did-direct-ivr-custom');
        $ext = ".conf";
        foreach ($fileArr as $file){
            unlink(C("etc").$file.$ext);
        }
        return true;
    }

}