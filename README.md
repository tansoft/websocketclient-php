Websocket Client for PHP
========================

Simple websocket client and support data record and playback.

Depend on project [Textalk/websocket](https://github.com/Textalk/websocket-php/).

Installing
----------

Preferred way to install is with [Composer](https://getcomposer.org/).

Just add

    "require": {
      "tansoft/websocket": "1.0.*"
    }

in your projects composer.json.

Client usage:
-------------
```php
require('vendor/autoload.php');

class MyHandler implements WebSocket\ISocketHandler{
    public function onConnect(&$client){
        //init send something
        $client->send($reg);
    }
    public function onReceive(&$client, $msg){
        //receive something with $msg
        //return false for exit
        return true;
    }
    public function onError(&$client, $errno, $errmsg){
        echo('socket error '.$errno.':'.$errmsg);
        //return true for auto reconnect
        return true;
    }
}

$client = new WebSocket\WebSocketClient('ws://echo.websocket.org/', new MyHandler);
//setup auto ack settings
$client->setupAck('{"event":"ping"}', '{"event":"pong"}', KEEPALIVE_TIMEOUT_SECOND);

//setup rawdata record
$client->setupRawLog('rawdata.log');
$client->loop();

//or

//setup rawdata playback
//use flag WebSocket\WebSocketClient::RAWLOG_PLAYBACKMODE_QUICK for playback with no sleep
$client->setupRawLog('rawdata.log', WebSocket\WebSocketClient::RAWLOG_PLAYBACKMODE_NORMAL);
$client->loop();

```

Changelog
---------

1.0.0

 * Support auto ack.
 * Support event base callback.
 * Support rawdata record and playback.
