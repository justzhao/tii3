Tii PHP Framework
=======

* [Reference](docs/en/)
* [中文手册](docs/zh-CN/)


Error
====

Disable Function Check
----

禁用函数检查

Check if the function is disabled

使用这个脚本检查是否有禁用函数。命令行运行：

Use this script to check whether there is a disable function. The command line to run:

```curl -Ss http://www.tiiframework.com/check | php```

如果有提示Function stream\_socket\_server may be disabled. Please check disable\_functions in php.ini说明有依赖的函数被禁用，需要在php.ini中解除禁用才能正常使用。

Solution for the "stream\_socket\_server disabled" issue

步骤如下：

Steps are as follows:

1、运行`php --ini` 找到php cli所使用的php.ini文件位置

Run `php --ini` find php.ini under cli mode

2、打开php.ini，找到disable_functions一项解除stream\_socket\_server的禁用

Remove the stream\_socket\_server string from the disable\_functions at php.ini file


WebSocket Bad Request
----

400 Bad Request

Sec-WebSocket-Key not found.

This is a WebSocket service and can not be accessed via HTTP.

错误原因

Wrong

出现这个错误说明你用http协议去访问了websocket协议的服务。
开发者要注意，客户端使用的应用层协议要与服务端的应用层协议相同，也就是服务端是什么协议，客户端就使用什么协议。
如果协议不对应就会出现类似这种拒绝通讯甚至出错的情况。
这个道理就像在浏览器地址栏里访问数据库的ip:3306端口一样，你不会指望数据库真的会给你返回什么有用的信息吧？

This error indicates that you used the HTTP protocol to access the services of the websocket protocol.
Developers should pay attention to the use of the client application layer protocol and server application layer protocol, which is what the server is the agreement, the client will use what protocol.

正确做法

Right

正确的做法应该是建立一个websocket协议的链接，利用websocket协议的客户端与websocket协议的服务进行通讯。 如果客户端是浏览器，可以利用js建立websocket链接，代码类似这样：

The correct approach should be to establish a link to the websocket protocol, using the websocket protocol client and websocket protocol services for communication. If the client is a browser, you can use the JS to create a websocket link, code similar to this:

```
// 假设服务端ip为127.0.0.1，端口为1234
// Assuming the server 127.0.0.1 is IP, the port is 1234
ws = new WebSocket("ws://127.0.0.1:1234");
ws.onopen = function() {
    alert("连接成功");//Connect successfully
    ws.send('hello');
    alert("给服务端发送一个字符串：hello");//Send a string to the server
};
ws.onmessage = function(e) {
    alert("收到服务端的消息：" + e.data);//Receive a message from the server
};
```


