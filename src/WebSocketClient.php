<?php

namespace WebSocket;

define('RAWLOG_PLAYBACKMODE_NONE',   0);//<record mode
define('RAWLOG_PLAYBACKMODE_NORMAL', 1);//<playback in normal speed
define('RAWLOG_PLAYBACKMODE_QUICK',  2);//<playback with no sleep

/**
 * @desc
 * @package   WebSocket
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright MIT
 */

class WebSocketClient{
    protected $client = null;
    protected $uri = '';
    protected $handler = null;
    protected $options = array();
    protected $ackmsg = '';
    protected $ackback = '';
    protected $ackround = 0;
    protected $logfile = null;
    protected $playback = 0;
    public function __construct($uri, $handler, $options = array()) {
        $this->uri = $uri;
        $this->handler = $handler;
        $this->options = $options;
    }
    public function setupAck($ackmsg, $ackback, $round) {
        $this->ackmsg = $ackmsg;
        $this->ackback = $ackback;
        $this->ackround = $round;
    }
    public function setupRawLog($rawlogfile, $playback = RAWLOG_PLAYBACKMODE_NONE) {
        $this->logfile = $rawlogfile;
        $this->playback = $playback;
    }
    public function send($msg) {
        if ($this->playback == RAWLOG_PLAYBACKMODE_NONE) {
            try{
                $this->client->send($msg);
            }catch(Exception $e) {
                return false;
            }
        }
        return true;
    }
    public function loop() {
        if ($this->playback == RAWLOG_PLAYBACKMODE_NONE) {
            return $this->_loopWithSocket();
        }
        return $this->_loopFromRawLog();
    }
    private function _loopFromRawLog() {
        if (!$this->logfile) return false;
        do {
            $fd = fopen($this->logfile, 'r');
            if ($fd === false) return false;
            $this->handler->onConnect($this);
            $firstoff = -1;
            while(!feof($fd)) {
                $line = fgets($fd);
                $timepos = strpos($line, ':');
                if ($timepos != -1) {
                    $ts = floatval(substr($line, 0, $timepos));
                    $line = json_decode(substr($line, $timepos + 1),true);
                    if ($firstoff == -1) {
                        $firstoff = microtime(true) - $ts;
                    }
                    if ($this->playback == RAWLOG_PLAYBACKMODE_NORMAL) {
                        $ts += $firstoff;
                        @time_sleep_until($ts);
                    }
                    if (!$this->handler->onReceive($this, $line)) {
                        unset($this->client);
                        return false;
                    }
                }
            }
            fclose($fd);
        } while($this->handler->onError($this, 0, 'playback finished.'));
        return false;
    }
    private function _handleError($errcode = 0, $errmsg = '') {
        if ($errcode == 0) {
            $errcode = socket_last_error();
            $errmsg = socket_strerror($errcode);
        }
        if ($this->handler->onError($this, $errcode, $errmsg)) {
            $this->client->reset();
            return true;
        }
        unset($this->client);
        return false;
    }
    private function _loopWithSocket() {
        while(true) {
            $this->client = new ClientEx($this->uri, $this->options);
            try{
                $this->client->tryconnect();
            }catch(Exception $e) {
                if ($this->_handleError(500, $e->getMessage())) {
                    continue;
                } else {
                    return false;
                }
            }
            $laseack = time();
            $this->handler->onConnect($this);
            $sock = $this->client->getsocket();
            while(true) {
                $r=$e=array($sock);
                $w = null;
                $to = $this->ackround - (time() - $laseack);
                $ret = @stream_select($r,$w,$e,$to);
                if($ret>0) {
                    if (is_array($e) && count($e)>0) {
                        if ($this->_handleError()) {
                            break;
                        } else {
                            return false;
                        }
                    }
                    if (is_array($r) && count($r)>0) {
                        try{
                            $ret = $this->client->receive();
                        }catch(Exception $e){
                            if ($this->_handleError()) {
                                break;
                            } else {
                                return false;
                            }
                        }
                        if ($this->ackround && $ret == $this->ackback) {
                            $laseack = time();
                            continue;
                        }
                        if ($this->logfile) {
                            file_put_contents($this->logfile, microtime(true).':'.json_encode($ret)."\n", FILE_APPEND | LOCK_EX);
                        }
                        if (!$this->handler->onReceive($this, $ret)) {
                            unset($this->client);
                            return false;
                        }
                    }
                } else if ($ret === false){
                    if ($this->_handleError()) {
                        break;
                    } else {
                        return false;
                    }
                }
                $ti = time();
                if ($this->ackround) {
                    if ($ti - $laseack + 1 >= $this->ackround) {
                        $laseack = $ti;
                        $this->send($this->ackmsg);
                    }
                } else {
                    $laseack = $ti;
                }
            }
            unset($this->client);
        }
    }
}
