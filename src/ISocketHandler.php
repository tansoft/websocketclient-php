<?php

namespace WebSocket;

/**
 * @desc
 * @package   WebSocket
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright MIT
 */

interface ISocketHandler{
    public function onConnect(&$client);
    //return false to close the connection
    public function onReceive(&$client, $msg);
    //return true for reconnect
    public function onError(&$client, $errno, $errmsg);
}
