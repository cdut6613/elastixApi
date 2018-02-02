<?php
namespace Home\Controller;

use Home\Controller\BaseController;
use Think\Log;

class DialController extends BaseController {

    /**
     * 网页回拨
     * @param string exten 分机号码
     * @param string tel 手机号码
     */
    public function one($exten=null,$phone=null)
    {
        $this->exten = $exten;
        $this->phone = $phone;
        //验证手机号和分机
        if(!$this->verifyPhone()){
            $this->ajaxReturn(array('code'=>200,'msg'=>'手机号格式不正确!','data'=>I()));
        }
        //验证分机状态
        $extenStatus = $this->ami->ExtensionState($exten);
        if ($extenStatus['Status']!=0){
            $this->ajaxReturn(array('code'=>200,'msg'=>C('exten_status')[$extenStatus['Status']],'data'=>I()));
        }
        //执行拨号并返回
        $data = $this->ami->Originate("SIP/".$this->exten,$this->phone,'from-internal',1,null,null,null,$this->exten,null,null,true);
        $this->ajaxReturn(['code'=>0,'msg'=>'请求成功','data'=>['request'=>I(),'response'=>$data]]);
    }



    /**
     * 双向回拨
     * @param string from 手机号码1
     * @param string to 手机号码2
     */
    public function two($from=null,$to=null){
        //验证手机号码
        if (!$this->verifyPhone(trim($from)) || !$this->verifyPhone(trim($from)) ){
            $this->ajaxReturn(['code'=>'200','msg'=>'from/to电话格式不正确','data'=>'']);
        }
        $data = array(
            'file' => $from."-".$to."-".time().uniqid().mt_rand(1000,9999),
            'from' => $from,
            'to'   => $to
        );
        if ($this->createOutGoingFile($data)){
            $this->ajaxReturn(['code'=>0,'msg'=>'请求成功','data'=>$data]);
        }
        $this->ajaxReturn(['code'=>200,'msg'=>'系统错误','data'=>$data]);
    }

    /**
     * 创建自动拨号文件
     */
    public function createOutGoingFile($data)
    {
        $name = $this->createExtensions("two-dial");
        $tempArr = array(
            "Channel:SIP/".C("trunk_name")."{$data['from']}",
            "CallerID:{$data['from']}",
            "MaxRetries:0",
            "RetryTime:0",
            "Context:{$name}",
            "Extension:{$data['to']}",
            "Archive:Yes",
        );

        $fileName = C('outgoing').$data['file'].".call";
        $file = fopen($fileName,"w");
        if ($file){
            foreach ($tempArr as $line){
                fwrite($file,$line."\n");
            }
            fclose($file);
            return true;
        }
        return false;
    }

    /**
     * 创建拨号计划
     */
    public function createExtensions($name=null)
    {
        if ($name==null) return false;
        $fileName = C('etc').C('extensions');
        $fileContent = file_get_contents($fileName);
        if ($fileContent && strpos($fileContent,"[".$name."]")){
            return $name;
        }
        $extStr = array(
            '['.$name.']',
            'exten => _x.,1,Answer',
            'exten => _x.,n,Set(CDR(calldate)=${STRFTIME(${EPOCH},,%Y-%m-%d %H:%M:%S)})',
            ';exten => _x.,n,Background(custom/calling)',
            'exten => _x.,n,Set(NOW=${EPOCH})',
            'exten => _x.,n,Set(__DAY=${STRFTIME(${NOW},,%d)})',
            'exten => _x.,n,Set(__MONTH=${STRFTIME(${NOW},,%m)})',
            'exten => _x.,n,Set(__YEAR=${STRFTIME(${NOW},,%Y)})',
            'exten => _x.,n,Set(__TIMESTR=${YEAR}${MONTH}${DAY}-${STRFTIME(${NOW},,%H%M%S)})',
            'exten => _x.,n,Set(__CALLFILENAME=${CALLERID}-${EXTEN}-${TIMESTR}-${UNIQUEID})',
            'exten => _x.,n,Set(MIXMONFILE='.C('monitor').'${YEAR}/${MONTH}/${DAY}/${CALLFILENAME}.${MIXMON_FORMAT})',
            'exten => _x.,n,MixMonitor(${MIXMONFILE},,b)',
            'exten => _x.,n,Set(CDR(recordingfile)=${MIXMONFILE})',
            'exten => _x.,n,Dial(SIP/'.C('trunk_name').'${EXTEN})',
            ';exten => h,1,AGI(custom/cdr.php)',
            'exten => _x.,n,Hangup',
        );

        $file = fopen($fileName,"a");
        fwrite("\n");
        foreach ($extStr as $line){
            fwrite($file,$line."\n");
        }
        fwrite("\n");
        fclose($file);
        $this->ami->Command("reload");
        return $name;
    }

    /**
     * 根据分机号挂断当前通道
     */
    public function hangup($exten=null)
    {
        // 指定允许其他域名访问
        header('Access-Control-Allow-Origin:*');
        // 响应类型
        header('Access-Control-Allow-Methods:POST');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        $this->exten = $exten;
        if ($data=$this->getChannel()){
            $rs = $this->ami->Command("hangup request ".$data);
            $this->ajaxReturn(['code'=>0,'msg'=>'已挂断','data'=>I()]);
        }
        $this->ajaxReturn(['code'=>200,'msg'=>'未挂断','data'=>I()]);
    }

    /**
     * 接收通话记录信息
     */
    public function getCdr()
    {
        $data = $_POST;
        Log::write(json_encode($data));
    }

    /**
     * @param null $exten 监听分机
     * @param null $spyExten 被监听分机
     * @param null $type 监听类型 default=1
     * 1-密语，客户听不到监听者说话 （常用）
     * 2-强插，三方正常通话
     * 3-监听，只能听
     */
    public function chanSpy($exten=null,$spyExten=null,$type=1)
    {

        // 指定允许其他域名访问
        header('Access-Control-Allow-Origin:*');
        // 响应类型
        header('Access-Control-Allow-Methods:POST');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        $this->exten = $exten;

        //验证监听分机状态,监听分机必需空闲
        $extenStatus = $this->ami->ExtensionState($this->exten);
        if ($extenStatus['Status']!=0){
            $this->ajaxReturn(array('code'=>200,'msg'=>'分机：'.$exten.C('exten_status')[$extenStatus['Status']],'data'=>I()));
        }
        //验证被监听分机状态,被监听分机必需使用中
        $spyExtenStatus = $this->ami->ExtensionState($spyExten);
        if ($spyExtenStatus['Status']!=1){
            $this->ajaxReturn(array('code'=>200,'msg'=>'分机：'.$spyExten.C('exten_status')[$spyExtenStatus['Status']],'data'=>I()));
        }

        switch ($type){
            case 1:
                $dataExt="Ew";break;
            case 2:
                $dataExt="EB";break;
            case 3:
                $dataExt="E";break;
            default:
                $dataExt="Ew";break;
        }
        $data = "SIP/".$spyExten.",".$dataExt;
        $rs = $this->ami->Originate("SIP/".$this->exten,$spyExten,'from-internal',1,"ChanSpy",$data,null,$this->exten,null,null,true);
        if ($rs['Response']!='Error'){
            $this->ajaxReturn(array('code'=>0,'msg'=>'监听连接成功','data'=>$rs));
        }
        $this->ajaxReturn(array('code'=>200,'msg'=>'系统错误','data'=>$rs));

    }



}