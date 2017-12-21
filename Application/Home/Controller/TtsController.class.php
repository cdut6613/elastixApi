<?php
namespace Home\Controller;

use Home\Controller\BaseController;

class TtsController extends BaseController {
    public $config = array(
        'id'=>'9983077',
        'key'=>'gQv1DIirH5UsCNeHCfwfAlOP',
        'secret'=>'836a766cea52a90f189564e6ebfdb6b2'
    );

    /**
     * 向一个手机号码发送语音提示消息
     * @param tel 手机号码
     * @param msg 消息文字，会自动处理成语音文件
     */
    public function voicePrompt($phone=null,$msg=null)
    {
        $this->phone = $phone;
        $this->msg = $msg;
        if (!$this->verifyPhone()){
            $this->ajaxReturn(['code'=>200,'msg'=>'phone format error','data'=>I()]);
        }
        $video = $this->create();
        if ($video!==false){
            $orignate = array(
                'Channel'       => 'sip/'.C('trunk_name').$this->phone,
                'Exten'         => '67498883',
                'Context'       => 'from-internal',
                'Priority'      => '1',
                'Application'   => 'Playback',
                'Data'          => $video,
                'CallerID'      => '67498883',
                'Async'         => true
            );
            $rs = $this->ami->send_request('Originate',$orignate);
            if ($rs['Response']=='Success'){
                $this->ajaxReturn(['code'=>0,'msg'=>'Originate successfully queued','data'=>I()]);
            }
        }
        $this->ajaxReturn(['code'=>200,'msg'=>'Originate failed','data'=>I()]);
    }

    /**
     * 根据语音生成mp3文件
     * 返回不含后辍的文件名
     */
    public function create()
    {

        vendor("BaiduTTS.AipSpeech");
        // 初始化AipSpeech对象
        $aipSpeech = new \AipSpeech($this->config['id'], $this->config['key'], $this->config['secret']);
        $fileName = "custom/".uniqid().time();
        $result = $aipSpeech->synthesis($this->msg, 'zh', 1, array(
            'vol' => 5,
        ));

        // 识别正确返回语音二进制 错误则返回json 参照下面错误码
        if(!is_array($result)){
            $num = file_put_contents(C("souds").$fileName.".mp3", $result);
            if($num>0){
                return $fileName;
            }
        }
        return false;
    }

    /**
     * 删除语音文件
     */
    public function remove($fileName)
    {
        $rs = unlink(C("souds").$fileName.".mp3");
        return $rs?true:false;
    }

}