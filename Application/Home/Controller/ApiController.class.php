<?php
namespace Home\Controller;
use Think\Controller;
vendor('guzzle53.vendor.autoload');
vendor('phpagi.phpagi-asmanager');
use \GuzzleHttp\Client;

class ApiController extends Controller {

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
     * 批量查询分机状态
     */
    public function status($exten='')
    {
        // 指定允许其他域名访问
        header('Access-Control-Allow-Origin:*');
        // 响应类型
        header('Access-Control-Allow-Methods:POST');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        if (empty($exten)){
            $this->ajaxReturn(['code'=>200,'msg'=>'请传入正确的分机号,多个分机用,格开','data'=>'']);
        }
        $statusData = [];
        foreach (explode(',',$exten) as $e){
            $rs = $this->ami->ExtensionState((int) $e);
            if ($rs['Status']==4 || $rs['Status']==-1){
                $statusData[$e]=0;
            }else{
                $statusData[$e]=1;
            }
        }
        if (!empty($statusData)){
            $this->ajaxReturn(['code'=>0,'msg'=>'查询分机状态信息成功','data'=>$statusData]);
        }
        $this->ajaxReturn(['code'=>200,'msg'=>'请传入正确的分机号','data'=>'']);
    }


}