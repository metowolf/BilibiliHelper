<?php
/**
 *  Author： METO
 *  Version: 0.4.3
 */

Class Bilibili{

    public $debug=false;
    public $color=true;
    public $break=true;
    public $roomid='3746256'; // 主播房间号
    public $roomuid='14739884'; // 主播 UID
    public $useragent='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36';

    public function __construct($cookie){
        date_default_timezone_set('Asia/Shanghai');
        $this->cookie=$cookie;
        $this->start=time();
        $this->lock['task']['flag']=false;
        $this->lock['sign']=$this->start;
        $this->lock['silver']=$this->start;
        $this->lock['sendgift']=strtotime(date('Y-m-d 23:55:00'));
        $this->lock['expheart']=$this->start;
        $this->lock['expheart']+=(300-$this->lock['expheart']%300);
        $this->lock['giftheart']=$this->start;
        $this->lock['giftheart']+=(300-$this->lock['giftheart']%300)+60;
        preg_match('/LIVE_LOGIN_DATA=(.{40})/',$cookie,$token);
        $this->token=isset($token[1])?$token[1]:'';
        if(empty($this->token)){
            $this->log("cookie 不完整，部分功能已经禁用",'red','警告');
        }
    }

    public function run(){
        while(true){
            if(!$this->sign())return;
            if(!$this->silver())return;
            if(!$this->sendgift())return;
            if(!$this->expheart())return;
            if(!$this->giftheart())return;
            sleep(1);
            if($this->break&&date('H:i')=='23:59')break;
        }
    }

    private function sign(){
        if(time()<$this->lock['sign'])return true;

        $raw=$this->curl('http://api.live.bilibili.com/sign/doSign');
        $data=json_decode($raw,true);

        if($data['code']==-101){
            $this->log($data['msg'],'bg_red','签到');
            return false;
        }
        if($data['code']==-500){
            $this->log($data['msg'],'green','签到');
            $this->lock['sign']+=24*60*60;
            return true;
        }
        $this->log("尝试签到",'blue','签到');

        $this->curl('https://api.live.bilibili.com/giftBag/sendDaily?_='.round(microtime(true)*1000));
        $raw=$this->curl('http://live.bilibili.com/sign/GetSignInfo');
        $data=json_decode($raw,true);
        $this->log("获得 ".$data['data']['text'].$data['data']['specialText'],'green','签到');
        return true;
    }

    private function expheart(){
        if(time()<$this->lock['expheart'])return true;
        $this->lock['expheart']+=5*60;

        $raw=$this->curl('http://api.live.bilibili.com/User/userOnlineHeart');
        $data=json_decode($raw,true);

        if($data['code']==-101){
            $this->log($data['msg'],'bg_red','心跳');
            return false;
        }

        $raw=$this->curl('http://api.live.bilibili.com/User/getUserInfo?ts='.round(microtime(true)*1000));
        $data=json_decode($raw,true);
        $level=$data['data']['user_level'];
        $a=$data['data']['user_intimacy'];
        $b=$data['data']['user_next_intimacy'];
        $per=round($a/$b*100,3);
        $this->log("level:$level exp:$a/$b ($per%)",'magenta','心跳');
        return true;
    }

    private function sendgift(){
        if(time()<$this->lock['sendgift'])return true;
        $this->lock['sendgift']+=24*60*60;
        if(empty($this->token))return true;

        $this->log("开始翻动礼物",'green','投喂');
        $raw=$this->curl('http://api.live.bilibili.com/gift/playerBag?_='.round(microtime(true)*1000));
        $data=json_decode($raw,true);
        foreach($data['data'] as $vo){
            if($vo['expireat']!='今日')continue;
            $payload=array(
                'giftId'=>$vo['gift_id'],
                'roomid'=>$this->roomid,
                'ruid'=>$this->roomuid,
                'num'=>$vo['gift_num'],
                'coinType'=>'silver',
                'Bag_id'=>$vo['id'],
                'timestamp'=>time(),
                'rnd'=>mt_rand()%10000000000,
                'token'=>$this->token,
            );
            $res=$this->curl('http://api.live.bilibili.com/giftBag/send',$payload);
            $res=json_decode($res,true);

            if($res['code'])$this->log("{$data['msg']}",'red','投喂');
            else $this->log("成功向 http://live.bilibili.com/{$this->roomid} 投喂了 {$vo['gift_num']} 个 {$vo['gift_name']}",'green','投喂');
        }
        return true;
    }

    private function giftheart(){
        if(time()<$this->lock['giftheart'])return true;
        $this->lock['giftheart']+=5*60;

        $raw=$this->curl('http://api.live.bilibili.com/eventRoom/heart?roomid='.$this->roomid);
        $data=json_decode($raw,true);

        if($data['code']==-403){
            $this->log($data['msg'],'magenta','收礼');
            if($data['data']['heart']==false)$this->lock['giftheart']+=24*60*60;
            elseif($data['msg']=='非法心跳')$this->curl("http://api.live.bilibili.com/eventRoom/index?ruid=17561885");
            return true;
        }
        $gift=end($data['data']['gift']);
        $this->log("{$data['msg']}，礼物 {$gift['bagId']} 喜加一（{$gift['num']}）",'magenta','收礼');
        return true;
    }

    private function silver(){
        if(time()<$this->lock['silver'])return true;

        if($this->lock['task']['flag']==false){
            $raw=$this->curl("http://live.bilibili.com/FreeSilver/getCurrentTask");
            $data=json_decode($raw,true);

            if($data['code']==-101){
                $this->log($data['msg'],'bg_red','宝箱');
                return false;
            }
            if($data['code']==-10017){
                $this->lock['silver']+=24*60*60;
                $this->log($data['msg'],'blue','宝箱');
                return true;
            }

            $this->log("领取宝箱，内含 {$data['data']['silver']} 瓜子，需要 {$data['data']['minute']} 分钟开启",'cyan','宝箱');
            $this->lock['task']['flag']=true;
            $this->lock['task']['start']=$data['data']['time_start'];
            $this->lock['task']['end']=$data['data']['time_end'];
            $this->lock['silver']=$data['data']['time_end']+5;
            $t=date('H:i:s',$this->lock['silver']);
            $this->log("等待 $t 领取",'blue','宝箱');
            return true;
        }

        $this->log("开始做小学生算术","blue",'宝箱');
        $captcha=$this->captcha();
        $start=$this->lock['task']['start'];
        $end=$this->lock['task']['end'];
        $raw=$this->curl("http://live.bilibili.com/freeSilver/getAward?time_start={$start}&time_end={$end}&captcha=$captcha");
        $data=json_decode($raw,true);

        $this->lock['task']['flag']=false;
        if($data['code']==0)$this->log("领取成功，silver: {$data['data']['silver']}(+{$data['data']['awardSilver']})",'cyan','宝箱');
        else $this->log("领取失败，{$data['msg']}",'red','宝箱');
        $this->lock['silver']=time()+5;
        return true;
    }

    private function captcha(){
        $raw=$this->curl('http://live.bilibili.com/freeSilver/getCaptcha?ts='.time(),null,false);
        $image=imagecreatefromstring($raw);
        $width=imagesx($image);
        $height=imagesy($image);

        for($i=0;$i<$height;$i++)
            for($j=0;$j<$width;$j++)
                $grey[$i][$j]=(imagecolorat($image,$j,$i)>>16)&0xFF;
        for($i=0;$i<$width;$i++)$vis[$i]=0;
        for($i=0;$i<$height;$i++)
            for($j=0;$j<$width;$j++)
                $vis[$j]|=$grey[$i][$j]<220;
        for($i=0;$i<$height;$i+=2){
            for($j=0;$j<$width;$j+=2)
                echo $grey[$i][$j]<220?'■':'□';
            echo "\n";
        }

        $OCR=array(
            '0'=>'0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111111111111111111111',
            '1'=>'0011111001111101111110111111011111101111111111111111111111111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111',
            '2'=>'0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111000000000011111100000000001111110000000001111110000000001111111000000000111111000000000111111000000000011111100000000011111100000000011111100000000001111110000000001111110000000000111110000000000111111000000000111111000000000011111000000000011111100000000001111100000000001111111111111111111111111111111111111111111111111111111111111111',
            '3'=>'0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111000000000011111100000000011111110000000111111110000000111111110000000011111100000000001111111100000000011111111000000000011111110000000000111111000000000001111100000000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
            '4'=>'00000001111110000000000011111100000000001111110000000000011111100000000000111110000000000011111100000000000111111000000000001111100000000000111111000000000001111110000000000011111000000000001111110000000000011111101111100000111110011111000011111100111110000111111001111100001111100011111000111111000111110001111110001111100011111000011111001111110000111110011111111111111111111111111111111111111111111111111111111111111111111000000000011111000000000000111110000000000001111100000000000011111000000000000111110000000000001111100',
            '5'=>'1111111111111111111111111111111111111111111111111111111111111111111110000000000011111000000000001111100000000000111110000000000011111000000000001111100000000000111110000000000011111111111111101111111111111111111111111111111111111111111111110000000000011111000000000001111100000000000111110000000000011111000000000001111100000000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
            '6'=>'0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000000000111110000000000011111000000000001111100000000000111111111111111011111111111111111111111111111111111111111111111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
            '7'=>'1111111111111111111111111111111111111111111111111111111111111111111110000011111111111000001111111111100000111110111110000011111000000000011111100000000001111110000000000111110000000000011111000000000011111100000000001111110000000000111110000000000011111000000000011111100000000001111110000000000111111000000000011111000000000011111100000000001111110000000000111111000000000011111000000000011111100000000001111110000000000111111000000000011111000000000011111100000000001111110000000000111110000000',
            '8'=>'0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111100000111111111100000011111111110000001111111111100001111110111111111111110001111111111110000011111111110000011111111111100011111111111111011111110011111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111111',
            '9'=>'1111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111111111111111111111111111111111111111111111101111111111111110000000000011111000000000001111100000000000111110000000000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
            '+'=>'00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000111110000000000001111100000000000011111000000000000111110000000000001111100000000000011111000000111111111111111111111111111111111111111111111111111111111111111111110000001111100000000000011111000000000000111110000000000001111100000000000011111000000000000111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
            '-'=>'000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000111111111111111111111111111111111111000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        );

        $result='';
        for($k=0;$k<$width;$k++)if($vis[$k]){
            $L=$R=$k;
            while($vis[$R]==1)$R++;
            $str='';
            for($i=4;$i<=34;$i++)
                for($j=$L;$j<$R;$j++)
                    $str.=$grey[$i][$j]<220?'1':'0';
            $max=0;
            foreach($OCR as $key=>$vo){
                similar_text($str,$vo,$per);
                if($per>$max){
                    $max=$per;
                    $ch=$key;
                }
            }
            $result.=$ch;
            $k=$R;
        }

        $ans=eval("return $result;");
        $this->log("(๑•̀ㅂ•́)و✧ $result = $ans","blue",'宝箱');
        return $ans;
    }

    private function log($message,$color='default',$type=''){
        $colors = array(
            'none' => "",
            'black' => "\033[30m%s\033[0m",
            'red' => "\033[31m%s\033[0m",
            'green' => "\033[32m%s\033[0m",
            'yellow' => "\033[33m%s\033[0m",
            'blue' => "\033[34m%s\033[0m",
            'magenta' => "\033[35m%s\033[0m",
            'cyan' => "\033[36m%s\033[0m",
            'lightgray' => "\033[37m%s\033[0m",
            'darkgray' => "\033[38m%s\033[0m",
            'default' => "\033[39m%s\033[0m",
            'bg_red' => "\033[41m%s\033[0m",
        );
        $date=date('[Y-m-d H:i:s] ');
        if(!empty($type))$type="[$type] ";
        if(!$this->color)$color='none';
        echo sprintf($colors[$color],$date.$type.$message).PHP_EOL;
    }

    private function curl($url,$data=null,$logout=true){
        if($this->debug)$this->log('>>> '.$url,'lightgray');
        $curl=curl_init();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_IPRESOLVE, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_REFERER, 'http://live.bilibili.com/'.$this->roomid);
        curl_setopt($curl, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->useragent);
        if(!empty($data)){
            if(is_array($data))$data=http_build_query($data);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $result=curl_exec($curl);
        curl_close($curl);
        if($this->debug&&$logout)$this->log('<<< '.$result,'yellow');
        return $result;
	}
}
