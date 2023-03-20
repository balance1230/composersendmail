<?php

namespace Balance1230\Dxsendmail;

use GuzzleHttp\Client;

class SendMail
{

    const SEND_URI = '/mail/insideSend';

    /**
     * mail-clent url
     * @var string
     */
    public $baseUrl = '';

    /**
     * 标题
     * @var string
     */
    public $subject = '';
    /**
     * 发送人昵称
     * @var string
     */
    public $sendNickname = '';
    /**
     * 发送人邮件
     * @var string
     */
    public $sendMail = '';
    /**
     * 邮件内容html|text
     * @var string
     */
    public $body = '';
    /**
     * 是否是重要
     * @var string
     */
    public $urgent = false;
    /**
     * 收件人[[username=>1@qq.com,nickname=>name]]
     * @var array
     */
    public $rec = [];
    /**
     * 抄送人[[username=>1@qq.com,nickname=>name]]
     * @var array
     */
    public $ccs = [];
    /**
     * 密送人[[username=>1@qq.com,nickname=>name]]
     * @var array
     */
    public $bccs = [];

    /**
     * 已读回执
     * @var bool
     */
    public $confirmReadingTo = false;

    /**
     * 附件[[ name=>文件名称,formattedSize    =>大小如：1KB,1M,1G ,url=>cos完整路径可访问url]]
     * @var array
     */
    public $attach = [];
    /**
     * 超大附件 [[ name=>文件名称,formattedSize    =>大小如：1KB,1M,1G ,url=>cos完整路径可访问url]]
     * @var array
     */
    public $largeFile = [];

    public $error = '';

    public function __construct(string $url)
    {
        $this->baseUrl = $url;
    }

    public function send(): bool
    {
        $sendParams=[];
        try {
            $sendParams=$this->validateSendParams();
        }catch (\Exception $e)
        {
            $this->error=$e->getMessage();
        }
        if($this->error||empty($sendParams))
        {
            return false;
        }

        $re=$this->http('post',$this->baseUrl.self::SEND_URI,$sendParams);

        return $this->isSuccess($re);
    }

    /**
     * 验证发送邮件入参
     * @return array 发件入参
     * @throws \Exception
     */
    private function validateSendParams(): array
    {
        if(!$this->sendMail)
        {
            throw new \Exception('发件人邮箱地址必须');
        }
        if (!$this->subject) {
            throw new \Exception('标题不能为空');
        }
        if (!$this->body) {
            throw new \Exception('内容不能为空');
        }
        if (empty($this->rec)) {
            throw new \Exception('收件人不能为空');
        } elseif (!$this->validateRes($this->rec)) {
            throw new \Exception('收件人数据格式错误');
        }
        if(!empty($this->ccs)&&!$this->validateRes($this->ccs))
        {
            throw new \Exception('抄送人人数据格式错误');
        }
        if(!empty($this->bccs)&&!$this->validateRes($this->bccs))
        {
            throw new \Exception('密送人人数据格式错误');
        }
        if(!empty($this->attach)&&!$this->validateAttach($this->attach))
        {
            throw new \Exception('附件数据格式错误');
        }
        if(!empty($this->largeFile)&&!$this->validateAttach($this->largeFile))
        {
            throw new \Exception('大附件数据格式错误');
        }

        $arr= [
            'subject' => $this->subject,
            'sendNickname' => $this->sendNickname,
            'sendMail' => $this->sendMail,
            'body' => $this->body,
            'urgent' => $this->urgent,
            'recs' => $this->rec,
            'ccs' => $this->ccs,
            'bccs' => $this->bccs,
            'confirmReadingTo' => $this->confirmReadingTo,
            'mailType' => 1,
        ];
        if($this->attach)
        {
            $arr['attach']= array_map(function ($item){
                $item['size']=!isset($item['size'])||!is_int($item['size'])?$this->getSizeAes($item['formattedSize']):$item['size'];
                return $item;
            },$this->attach);
        }

        if($this->largeFile)
        {
            $arr['largeFile']= array_map(function ($item){
                $item['size']=!isset($item['size'])||!is_int($item['size'])?$this->getSizeAes($item['formattedSize']):$item['size'];
                return $item;
            },$this->largeFile);
        }
        return $arr;
    }

    /**
     * 验证to/cc/bcc
     * @param array $res
     * @return bool
     */
    private function validateRes(array $res): bool
    {
        try {
            array_column($res, 'username');
            array_column($res, 'nickname');
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * 验证 attach/largeFile
     * @param array $attach
     * @return bool
     */
    private function validateAttach(array $attach):bool
    {
        try {
            array_column($attach, 'name');
            array_column($attach, 'formattedSize');
            array_column($attach, 'url');
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }


    /**
     * 发送http
     * @param string $method
     * @param string $uriPatch
     * @param array $params
     * @return array
     */
    private function http(string $method,string $uriPatch,array $params=[]):array
    {

        $httpParams=$method=='post'?['form_params'=>$params]:['query'=>$params];

        $response=(new Client())->{$method}($uriPatch,$httpParams);

        $res=$response->getBody()->getContents();

        if(!is_string($res))
        {
            $this->error='邮件client api 调用失败 res:'.(string)$res;
            return [];
        }
        $res=json_decode($res,true);
        if(!is_array($res))
        {
            $this->error='邮件client api 返回值不正确 res:'.(string)$res;
            return [];
        }

        if(!isset($res['code'])||!isset($res['data'])||!isset($res['msg']))
        {
            $this->error='邮件client api 返回值不正确 res:'.(string)$res;
            return [];
        }
        return $res;
    }

    private function isSuccess(array $res):bool
    {
        return isset($res['code'])&&$res['code']=='00000';
    }

   private function is_num($i)
    {
        return is_numeric($i) ? $i : 0;
    }


    /**
     * KB，MB，GB 转成字节
     * @param string $size
     * @return int
     */
    private function getSizeAes(string $size)
    {
        $size = strtolower($size);
        $Unit = preg_replace('/[^a-z]/', '', $size);
        if ($Unit == 'bytes' || $Unit == 'byte' || $Unit == 'b') {
            return str_replace(['Byte', 'byte', 'Bytes', 'bytes', 'b'], '', $size);
        } elseif ($Unit == 'kb') {
            $i = $this->is_num(str_replace(['KB', 'kb'], '', $size));
            return $i * 1024;
        } elseif ($Unit == 'mb') {
            $i = $this->is_num(str_replace(['MB', 'mb'], '', $size));
            return $i * 1024 * 1024;
        } elseif ($Unit == 'gb') {
            $i = $this->is_num(str_replace(['GB', 'gb'], '', $size));
            return $i * 1024 * 1024 * 1024;
        }
        return $size;
    }
}