<?php
namespace Home\Controller;
use Think\Controller;
vendor('guzzle53.vendor.autoload');
vendor('phpagi.phpagi-asmanager');
use \GuzzleHttp\Client;

class BaseController extends Controller {

    protected $exten = null;
    protected $phone = null;
    protected $secret = '962540';
    protected $msg = null;
    public function __construct()
    {
        parent::__construct();
        $this->client = new \GuzzleHttp\Client();
        $this->ami = new \AGI_AsteriskManager();
        if ($this->ami->connect()==false){
            $this->ajaxReturn(['code'=>200,'msg'=>'ami未连接','data'=>'']);
        }
    }

    /**]
     *
     * 验证手机号码格式
     */
    public function verifyPhone($phone=null)
    {
        if ($phone){
            if ( !preg_match('/1[34578][0-9]{9}/',$phone) ){
                return false;
            }else{
                return true;
            }
        }else{
            if ( !preg_match('/1[34578][0-9]{9}/',$this->phone) ){
                return false;
            }else{
                return true;
            }
        }
    }

    /**
     * 获取通道变量
     * @param string $exten 分机号码
     * @return boolean or channel
     */
    public function getChannel()
    {
        $rs = $this->ami->Command("core show channels");
        $reg = '/SIP\/'.$this->exten.'-\S+\s?/';
        preg_match($reg,$rs['data'],$channel);
        if (empty($channel)){
            return false;
        }else{
            return $channel[0];
        }
    }

    /**
     * 判断分机状态
     */
    public function status($exten=null)
    {
        $rs = $this->ami->ExtensionState((int) $exten);
        if ($rs['Status']==4 || $rs['Status']==-1){
            $this->ajaxReturn(['code'=>0,'msg'=>'分机状态：'.C('exten_status')[$rs['Status']],'data'=>$rs,'status'=>false]);
        }else{
            $this->ajaxReturn(['code'=>0,'msg'=>'分机状态：'.C('exten_status')[$rs['Status']],'data'=>$rs,'status'=>true]);
        }
    }


}