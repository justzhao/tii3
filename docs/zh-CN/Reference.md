Tii PHP框架
====

### 序言


Tii 是一套帮助 PHP 开发人员建立复杂应用程序的开发框架和工具包，一直秉承简洁实用的设计原则，在保持出色的性能和至简的代码的同时，也注重易用性，拥有众多的功能和特性。 她是一个小巧但功能强大的单文件 PHP 框架。他总大小不过200k，功能却极其强大，可同时支持编写网页、服务器脚本、高性能socket服务器等。

Tii 发音为 [Ti:]，它是“This Is It!”的首字母缩写，就是这样！


| 版本 | 说明 | 贡献者 |
| ------|------|----- |
| [Tii-2.16.0223.1627](https://raw.githubusercontent.com/ttchina/tii3/master/build/Tii-3.16.php) | 稳定版本 | @alacner |

地址 [http://www.tiichina.com]() 欢迎关注

技术交流群: 31072415

[![](images/add_to_qq_group.png)](http://shang.qq.com/wpa/qunwpa?idkey=0314ef7f84babba92b24659b76bf7db2f1adef674a885220122b0ad2880a97d5)


### 经典案例
[万铺云](http://www.wanpucloud.com)
[我要烧菜](http://www.51shaocai.com)
[短网址](http://ozt.so)
[水质指数](http://www.shuizhi.info)
[PECAN在线发布系统](http://pecan.triones.org)


### 版权申明

发布本资料须遵守开放出版许可协议 1.0 或者更新版本。

未经版权所有者明确授权，禁止发行本文档及其被实质上修改的版本。 未经版权所有者事先授权，禁止将此作品及其衍生作品以标准（纸质）书籍形式发行。如果有兴趣再发行或再版本手册的全部或部分内容，不论修改过与否，或者有任何问题，请联系版权所有者 <alacner@gmail.com>。

本文档的版权归作者所有，本文档及其描述的内容受有关法律的版权保护，对本文档内容的任何形式的非法复制，泄露或散布，将导致相应的法律责任。


文档手册
====


入门指引
----

### 简介

PHP（“PHP: Hypertext Preprocessor”，超文本预处理器的字母缩写）是一种被广泛应用的开放源代码的多用途脚本语言，它可嵌入到 HTML中，尤其适合 web 开发。

更多PHP相关请移步 [php官方网站](http://php.net/manual/zh/intro-whatis.php)

### 特性
* 加载简单，引入只需一个文件
* 日志机制
* 支持HTTP（网页模式）和cli（脚本模式）两种模式
* 事件驱动
* 数据库操作简便
* 助手类引入便捷
* 文件处理封装
* 时间处理封装
* Socket请求封装，支持代理模式
* Session支持cookie、file、db多种方式
* 支持委托代理机制
* 安全过滤器机制
* 其他


### 运行环境
需要PHP的版本大于等于5.4.0
 
### 总体结构和模块接口设计

![结构图](images/archi.png)

### 简明教程
只需要包含一个加载启动引导文件即可

``` php
<?php
require_once __DIR__ . '/../library/Tii-2.16.0223.1627.php';//加载 tii 打包库
Tii_Config::setDir(__DIR__);// 修改配置文件的路径
```

加载器
----

主要提供了一个重要的类：Tii
可以利用 Tii::object方法来创建单例，call的参数可以参考 call_user_func 参数，第一个是class，其他为传入__construct的参数；
为了便于简化调用单例模式的对象
同时支持多语言报错，也可以自定义报错。
``` php
		Tii::validator($params, array(
			'account_id' => array('is_numeric'),
		));
```
所示，创建了一个验证器，判断`$params`中的account_id是数字。

应用程序
----

### 控制器
### 助手类
### 逻辑处理器
根据环境变量，获取执行的模式，然后根据不同的模式解析出参数，通过抽象类统一方法名，这样一套控制器程序，可以同时在不同模式下执行。
![处理器](images/process.png)
### 路由
### 会话

### 举一反三

#### Hello World

##### 建立项目
##### 创建入口文件
##### 配置文件
##### 创建控制器文件

缓存
----

配置
----

全局可以使用
```
Tii::lang(‘hello word’);
```
controller中可以使用
```
$this->lang(‘hello word’);
$this->i18n(‘hello word’);
```
只需要在配置目录下创建lang-(语言).config.php ，然后对应的可以将“hello word”替换成”你好，世界”就实现了显示语言的转换。

变量包装器
----

### 简介

利用`array_merge函数`将多变量合并后转换为变量包装器中的属性值。
	
### 类摘要

```php
Tii_ConfigWrapper
{
	public Tii_ConfigWrapper function __construct([mixed $args [, mixed $... ]])
}
```
### Tii_ConfigWrapper 类

#### ->__construct()

构造函数

##### 说明

`Tii_ConfigWrapper __construct([mixed $args [, mixed $... ]])`

构造一个新的 Tii_ConfigWrapper 对象。

##### 参数

args

> 数组，其他对象请自行转换为数组。

##### 返回值

没有返回值。

#### ->__get(）

返回变量转换后的属性值

##### 说明

`mixed __get($name)`

##### 返回值

属性值

### 范例

``` php
<?php
class ClassName {
	public $foo = ["bar","xyz"];
	protected $id = 1;

	public function getId()
	{
		return $this->id;
	}
}

$obj = new stdClass();
$obj->author = 'alacner@gmail.com';

$a = new Tii_ConfigWrapper(['key' => 'value', 'arr' => ['abc' => 'xyz', 'x' => 1]], $obj, new ClassName);

var_dump($a->key);//string(5) "value"
var_dump($a->foo);
/**
array(2) {
  [0]=>
  string(3) "bar"
  [1]=>
  string(3) "xyz"
}
*/

var_dump($a->arr);
/**
array(2) {
  ["abc"]=>
  string(3) "xyz"
  ["x"]=>
  int(1)
}
*/

var_dump($a->id);//NULL
var_dump($a->author);//string(17) "alacner@gmail.com"
```

数据库
----
(PECL pdo >= 0.1.0)

委托
----

### 简介

用于通过注释的方式进行配置缓存、监控等行为，而不需要改变执行代码业务逻辑。

注释字符串，格式请移步 `Tii_DocCommentParser 类介绍`：

关键词列表：

| 键值      | 示例    | 描述 |
| ------------- |:-------------:| -----:|
| enabled      |  true | 是否启用|
| cacheName	 | method.{0}.{1.userid} | 默认值为入参的哈希值，支持通过`Tii::render`的方式自定义唯一值 |
| cacheMode      | buffer | 缓存模式，有：buffer,cache,file。默认为file模式 | 
| useExpired      | true| 是否使用过期数据，只对file模式有效 | 
	
### 类摘要

```php
Tii_Delegate
{
	public Tii_Delegate function __construct(mixed $that)
	public mixed function __get(string $name)
	public mixed function __call(string $name, array $args)
}
```
### Tii_Delegate 类

#### ->__construct()

构造函数

##### 说明

`Tii_Delegate __construct(mixed $that)`

构造一个新的 Tii_Delegate 对象。

##### 参数

that

> 既可以是一个包含类名的字符串（string）也可以是一个对象（object）

##### 返回值

没有返回值。

#### ->__get()

返回被代理类的属性值

##### 说明

`mixed __get($name)`

透过代理类返回被代理类的属性值。

##### 返回值

如果属性值


#### ->__call()

透过代理类调用被代理类的方法并返回值

##### 说明

`mixed __call(string $name, array $args)`

##### 返回值

返回方法返回值

### 范例

``` php
<?php
class ClassName {
	public $foo = "bar";
	protected $id = 1;

	public function getId()
	{
		return $this->id;
	}
}

$a = new Tii_Delegate(new ClassName);

var_dump($a->foo);
/**
object(ReflectionProperty)#14 (2) {
  ["name"]=>
  string(3) "foo"
  ["class"]=>
  string(9) "ClassName"
}
*/

var_dump($a->id);
/**
object(ReflectionProperty)#14 (2) {
  ["name"]=>
  string(2) "id"
  ["class"]=>
  string(9) "ClassName"
}
*/

var_dump($a->getId());//int(1)
```

注释解析器
----

### 简介

PHPDoc解析器，主要用于代理类根据注释配置参数。

注释字符串，格式是：

```
/**
 * 短描述（单行）
 *
 * 长描述（可以多行）
 * @key value
 * @key value
 * @... ...
 * 
 */	
```

关键词，通常有以下这些：

* access
* author
* copyright
* deprecated
* example
* ignore
* internal
* link
* param
* return
* see
* since
* tutorial
* version
* 其他自定义关键词，如代理类中的：
	 + enabled
	 + cacheName
	 + cacheMode
	 + useExpired

	
### 类摘要

```php
Tii_DocCommentParser
{
	public Tii_DocCommentParser function __construct(string $commentString)
	public string function shortDesc(void)
	public static string function desc(void)
	public static array function get(void)
	public static mixed function __get(string $name)
	public static bool function is*(void)
	public static int function int*(void)
	public static float function float*(void)
	public static string function get*(void)
```
### Tii\_DocCommentParser 类

#### ->__construct()

构造函数

##### 说明

`Tii_DocCommentParser __construct(string $commentString)`

传入注释字符串，返回注释实例

##### 参数

commentString

> 注释字符串

##### 返回值

返回Tii_DocCommentParser实例


#### ->shortDesc()

短描述

##### 说明

`string shortDesc(void)`

##### 返回值

返回短描述


#### ->desc()

描述

##### 说明

`string desc(void)`

##### 返回值

返回描述


#### ->get()

获取注释全部内容

##### 说明

`string get(void)`

##### 返回值

返回数组：

* shortDesc
* desc
* parameters


#### ->__get()

获取参数的数组

##### 说明

`string __get(string $name)`

##### 返回值

如果存在返回参数值的数组，否则返回NULL

#### ->is*()

获取参数并转换为布尔值

##### 说明

`bool is*(void)`

##### 返回值

如果存在返回布尔值，否则返回false


#### ->int*()

获取参数并转换为整数

##### 说明

`int int*(void)`

##### 返回值

如果存在返回整数，否则返回0


#### ->float*()

获取参数并转换为浮点数

##### 说明

`float float*(void)`

##### 返回值

如果存在返回浮点数，否则返回0


#### ->get()

获取参数值

##### 说明

`string get*(void)`

##### 返回值

如果存在返回参数值，否则返回NULL

### 范例

```
$doc = <<<eot
/**
 * 短描述（单行）
 *
 * 长描述（可以多行）
 * @author alacner@gmail.com
 * @enabled true
 * @expired 300
 * @value 123.456
 *
 */
eot;

$a = new Tii_DocCommentParser($doc);

echo $a->desc();//长描述（可以多行
echo $a->shortDesc();//短描述（单行
print_r($a->author);
/**
Array
(
    [0] => alacner@gmail.com
)
*/
var_dump($a->isEnabled()); //bool(true)
var_dump($a->intExpired()); //int(300)
var_dump($a->floatValue()); //float(123.456)
echo $a->getAuthor(); //alacner@gmail.com
```

事件
----
通过调用注册事件或者指定某个目录作为事件的加载位置自动加载注册到事件池，在需要的地方选择执行器还是过滤器模式来进行触发事件。
![事件](images/event.jpeg)


异常处理
----

### 简介
是框架中所有异常的基类。

### 类摘要
```php
Tii_Exception
{
	public void function __construct([mixed $args [, mixed $... ]])
	public static bool function set_error_handler(int $errno , string $errstr [, string $errfile [, int $errline [, array $errcontext ]]])
	public static void function set_error_exception_handler($errno, $errstr, $errfile, $errline, array $errcontext = [])
	public static void function set_exception_handler(Exception $exception)
}
```
### Tii_Exception 类

#### ->__construct()

构造函数

##### 说明

`void __construct([mixed $args [, mixed $... ]])`

构造函数

##### 参数

args
> 参考 `Tii::lang`

##### 返回值

返回Tii_Exception实例。

##### 范例

Example #1 示例
``` php
<?php
throw new Tii_Exception("hello %s", 'world');
```


#### ::set\_error\_handler()

用户定义的错误处理函数。

##### 说明

`bool Tii_Exception::set_error_handler(int $errno , string $errstr [, string $errfile [, int $errline [, array $errcontext ]]])`

用户的函数需要接受两个参数：错误码和描述错误的 string。 另外有可能提供三个可选参数：发生错误的文件名、发生错误的行号 以及发生错误的上下文(一个指向错误发生时活动符号表的 array)。

参考：[set-error-handler](http://php.net/manual/zh/function.set-error-handler.php)

##### 参数

errno
> 错误的级别

errstr
>错误的信息

errfile
>发生错误的文件名

errline
>错误发生的行号

errcontext
>指向错误发生时活动符号表的 array。 也就是说，errcontext 会包含错误触发处作用域内所有变量的数组。 用户的错误处理程序不应该修改错误上下文（context）。

##### 返回值

如果函数返回 FALSE，标准错误处理处理程序将会继续调用。



#### ::set\_error\_exception\_handler()

用户定义的错误处理函数。

##### 说明

`bool Tii_Exception::set_error_exception_handler(int $errno , string $errstr [, string $errfile [, int $errline [, array $errcontext ]]])`

用户的函数需要接受两个参数：错误码和描述错误的 string。 另外有可能提供三个可选参数：发生错误的文件名、发生错误的行号 以及发生错误的上下文(一个指向错误发生时活动符号表的 array)。

##### 参数

errno
> 错误的级别

errstr
>错误的信息

errfile
>发生错误的文件名

errline
>错误发生的行号

errcontext
>指向错误发生时活动符号表的 array。 也就是说，errcontext 会包含错误触发处作用域内所有变量的数组。 用户的错误处理程序不应该修改错误上下文（context）。

##### 返回值

如果函数返回 FALSE，标准错误处理处理程序将会继续调用。


#### ::set\_exception\_handler()

用户定义的异常处理函数。

##### 说明

`bool Tii_Exception::set_exception_handler(Exception $exception)`

当一个未捕获的异常发生时所调用函数。

参考：[set-exception-handler](http://php.net/manual/zh/function.set-exception-handler.php)

##### 参数

exception
> 抛出的异常对象

##### 返回值

无返回值

##### 范例

Example #1 set\_exception\_handler() 范例

```php
<?php
set_exception_handler('Tii_Exception::set_exception_handler');

throw new Exception('Uncaught Exception');
echo "Not Executed\n";
?>
```


文件系统
----

### 简介
经常用到的一些数学相关的计算方法

### 预定义常量

下列常量由此类定义，且仅在此类载入时可用。

```
	const FOLDER = 1;
	const FILE = 2;
	const BOTH = 3;
	public static $units = ['B', 'Kb', 'Mb', 'Gb', 'Tb', 'PB', 'EB', 'ZB', 'YB'];//First letter must be capitalized
```
	
### 类摘要
```php
Tii_Filesystem
{
	public static mixed function getTempDir(void)
	public static mixed function getDataDir(void)
	public static string function concat([mixed $args [, mixed $... ]])
	public static array function explode(string $path)
	public static string function getFilenameExt(string $file)
	public static string function getBasenameWithoutExt(string $file)
	public static array function getRelativePathFiles(string $path[, array $exts = [][, bool $recursive = true[, int $filter = self::FILE]]])
	public static array function getRelativePathFolders(string $path[, bool $recursive = true])
	public static array function getFolders(string $path[, bool $recursive = true])
	public static array function getFiles(string $path[, array $exts = [][, bool $recursive = true[, int $filter = self::FILE]]])
	public static bool function isWritable(string $file)
	public static bool function mkdir(string $pathname[, int $mode = 0777])
	public static bool function rmdir(string $pathname[, bool $recursive = false])
	public static bool function unlink(string $filename)
	public static bool function touch(string $filename[, int $time = null[, int $atime = null]])
	public static bool function copy(string $source, string $dest)
	public static string function tempnam([string $prefix = 'tii.'[, string $suffix = '']])
	public static string function tempfile([mixed $args [, mixed $... ]])
	public static string function hashfile(string $key[, string $namespace = 'tii'[, string $suffix = ''[, string $path = NULL]]])
	public static void function locker(string $key[, callable $locked = NULL[, callable $unlocked = NULL]])
	public mixed string function cached(string $cacheName[, int $expired = 0[, callable $function = NULL[, array $param_arr = [][, bool $isUseExpired = true]]]])
	public static string function format(int $bytes[, string $format = '%.2f%s'[, int $round = 3[, int $mod = 1024]]])
	public static int function bytes(string $val[, int $mod = 1024])
```
### Tii_Filesystem 类

#### ::getTempDir()

返回用于临时文件的目录

##### 说明

`mixed Tii_Filesystem::getTempDir( void )`

返回框架储存临时文件的默认目录的路径。

##### 返回值

返回临时目录的路径。

##### 范例
Example #1 示例
``` php
<?php
echo Tii_Filesystem::getTempDir();///var/folders/y_/spjx8212m00n/T/cli
```

#### ::getDataDir()

返回用于存储数据的目录

##### 说明

`mixed Tii_Filesystem::getDataDir( void )`

返回框架储存数据的目录的路径。

##### 返回值

返回数据目录的路径。

##### 范例
Example #1 示例
``` php
<?php
echo Tii_Filesystem::getDataDir();///tii/data/cli
```


HTTP请求
----


### 简介
利用stream\_socket\_client,fsockopen,pfsockopen函数封装访问HTTP请求的接口，类似curl模块。同时支持 HTTPS 证书、HTTP POST、HTTP PUT、 FTP 上传、HTTP 基于表单的上传、代理、cookies、用户名+密码的认证。
	
### 类摘要
```php
Tii_Http
{
	public static object function post(string $url[, mixed $data = ''[, array $headers = [][, array $options = [][, string $method = 'POST']]]])
	public static object function get(string $url[, array $headers = [][, array $options = [][, string $method = 'GET']]])
	public static string function streamBuilder(string $protocol[, array $headers = [][, string $data = '']])
	public static array function parseUrl(string $url)
	public static array function preparePostBody(array $formvars[, array $formfiles = []])
	public static array function parser(string $raw[, string $remoteAddr = '127.0.0.1'[, int $remotePort = 0]])
	public static array function parseCookie(string $cookie)
	public static string function tidyCookie([mixed $args [, mixed $... ]])
	public static string function urlAppend(string $url[, mixed $append = NULL])
	public static string function concat([mixed $args [, mixed $... ]])
	public static string function getMimeType(string $ext)
	public static string function getHttpStatus([int $code = 200])
```
### Tii_Http 类

#### ::post()

基于POST的请求方式

##### 说明

`object Tii_Http::post(string $url[, mixed $data = ''[, array $headers = [][, array $options = [][, string $method = 'POST']]]])`

##### 参数

url

> 请求的URL

data

> 字符串数据或者`Tii_Http::preparePostBody()`的参数

headers

> 头数据

options

> 选项数据
> 
> | 键值      | 示例    | 描述 |
> | ------------- |:-------------:| -----:|
> | protocol      |  HTTP/1.1 | HTTP请求协议|
> | is_proxy_mode	 | false | 是否使用代理模式 |
> | timeout      | ini_get("default\_socket\_timeout") | 超时时间 | 
> | remote_socket      | HTTP/1.1 | 如果安装了OpenSSL，那么你也许应该在你的主机名地址前面添加访问协议ssl://或者是tls://，从而可以使用基于TCP/IP协议的SSL或者TLS的客户端连接到远程主机。 |
> | flags      | STREAM\_CLIENT\_CONNECT \| STREAM\_CLIENT\_PERSISTENT | 位掩码字段可以设置为任意组合连接标志。在stream\_socket\_client的时候有效。 | 
> | contexts      | 参考context | 根据host来定义多个context  |
> | context      | 参考stream\_context\_create 函数 | [创建资源流上下文](http://php.net/manual/zh/function.stream-context-create.php) |
>

method

> 请求的方法：POST,PUT,DELETE

##### 返回值

返回object，属性有：

state

> 状态码，如：200，301，302，403等

data

> 数据，即服务器response的数据，如html数据等

headers

> 返回的HTTP协议头数据

runtime

>运行时数据，如执行的起止时间和运行时间等

>timestamp => [start => 开始时间, connection => 连接开始时间, request => 请求发起时间, response => 请求返回时间, finish => 请求结束时间]

>time_consuming => [trace => [connection => 连接消耗时间, request => 请求发起消耗时间, response => 请求返回消耗时间, finish => 结束消耗时间], total => 总消耗时间]

handler

> 用哪个句柄发起的连接

##### 范例

test.php

``` php
<?php

print_r($_SERVER);
```
Example #1 示例

``` php
<?php
print_r(Tii_Http::post('http://localhost/test.php'));
/**
stdClass Object
(
    [state] => 200
    [data] => Array
(
    [USER] => tii.framework
    [HOME] => /Users/tii.framework
    [FCGI_ROLE] => RESPONDER
    [SCRIPT_FILENAME] => /usr/local/nginx/html/test.php
    [QUERY_STRING] => 
    [REQUEST_METHOD] => POST
    [CONTENT_TYPE] => 
    [CONTENT_LENGTH] => 0
    [SCRIPT_NAME] => /test.php
    [REQUEST_URI] => /test.php
    [DOCUMENT_URI] => /test.php
    [DOCUMENT_ROOT] => /usr/local/nginx/html
    [SERVER_PROTOCOL] => HTTP/1.1
    [GATEWAY_INTERFACE] => CGI/1.1
    [SERVER_SOFTWARE] => nginx/1.2.9
    [REMOTE_ADDR] => 127.0.0.1
    [REMOTE_PORT] => 64171
    [SERVER_ADDR] => 127.0.0.1
    [SERVER_PORT] => 80
    [SERVER_NAME] => localhost
    [REDIRECT_STATUS] => 200
    [PATH_INFO] => 
    [PATH_TRANSLATED] => /usr/local/nginx/html
    [HTTP_USER_AGENT] => Tii/2.16.0223.1627
    [HTTP_CONTENT_LENGTH] => 0
    [HTTP_CONNECTION] => close
    [HTTP_HOST] => localhost
    [PHP_SELF] => /test.php
    [REQUEST_TIME_FLOAT] => 1476451280.7433
    [REQUEST_TIME] => 1476451280
)

    [headers] => Array
        (
            [Server] => Tengine/1.5.1
            [Date] => Fri, 14 Oct 2016 13:21:20 GMT
            [Content-Type] => text/html
            [Transfer-Encoding] => chunked
            [Connection] => close
            [X-Powered-By] => PHP/5.5.25
        )

    [runtime] => Array
        (
            [timestamp] => Array
                (
                    [start] => 1476451280.709
                    [connection] => 1476451280.7124
                    [request] => 1476451280.7125
                    [response] => 1476451280.7783
                    [finish] => 1476451280.7783
                )

            [time_consuming] => Array
                (
                    [trace] => Array
                        (
                            [connection] => 0.0033
                            [request] => 0.0001
                            [response] => 0.0658
                            [finish] => 0.0001
                        )

                    [total] => 0.0693
                )

        )

    [handler] => @stream_socket_client
    [options] => Array
        (
            [url] => http://localhost/test.php
            [method] => POST
            [protocol] => HTTP/1.1
            [headers] => Array
                (
                    [Host] => localhost
                )

            [parseUrl] => Array
                (
                    [scheme] => http
                    [host] => localhost
                    [path] => /test.php
                    [port] => 80
                    [query_string] => /test.php
                )

            [is_proxy_mode] => 
            [timeout] => 60
            [remote_socket] => tcp://localhost:80
            [flags] => 5
            [context] => Resource id #7
        )

    [proto] => HTTP/1.1
    [message] => OK
)
*/
```

#### ::get()

基于GET的请求方式

##### 说明

`object Tii_Http::get(string $url[, array $headers = [][, array $options = [][, string $method = 'GET']]])`

##### 参数

url

> 请求的URL

headers

> 头数据

options

> 选项数据，参考 `Tii_Http::post()`

method

> 请求的方法：GET,HEAD

##### 返回值

返回object，选项数据，参考 `Tii_Http::post()`

##### 范例

test.php

``` php
<?php

print_r($_SERVER);
```
Example #1 示例

``` php
<?php
print_r(Tii_Http::get('http://localhost/test.php'));
/**
stdClass Object
(
    [state] => 200
    [data] => Array
(
    [USER] => tii.framework
    [HOME] => /Users/tii.framework
    [FCGI_ROLE] => RESPONDER
    [SCRIPT_FILENAME] => /usr/local/nginx/html/test.php
    [QUERY_STRING] => 
    [REQUEST_METHOD] => GET
    [CONTENT_TYPE] => 
    [CONTENT_LENGTH] => 0
    [SCRIPT_NAME] => /test.php
    [REQUEST_URI] => /test.php
    [DOCUMENT_URI] => /test.php
    [DOCUMENT_ROOT] => /usr/local/nginx/html
    [SERVER_PROTOCOL] => HTTP/1.1
    [GATEWAY_INTERFACE] => CGI/1.1
    [SERVER_SOFTWARE] => nginx/1.2.9
    [REMOTE_ADDR] => 127.0.0.1
    [REMOTE_PORT] => 50481
    [SERVER_ADDR] => 127.0.0.1
    [SERVER_PORT] => 80
    [SERVER_NAME] => localhost
    [REDIRECT_STATUS] => 200
    [PATH_INFO] => 
    [PATH_TRANSLATED] => /usr/local/nginx/html
    [HTTP_USER_AGENT] => Tii/2.16.0223.1627
    [HTTP_CONTENT_LENGTH] => 0
    [HTTP_CONNECTION] => close
    [HTTP_HOST] => localhost
    [PHP_SELF] => /test.php
    [REQUEST_TIME_FLOAT] => 1476451770.8427
    [REQUEST_TIME] => 1476451770
)

    [headers] => Array
        (
            [Server] => Tengine/1.5.1
            [Date] => Fri, 14 Oct 2016 13:29:30 GMT
            [Content-Type] => text/html
            [Transfer-Encoding] => chunked
            [Connection] => close
            [X-Powered-By] => PHP/5.5.25
        )

    [runtime] => Array
        (
            [timestamp] => Array
                (
                    [start] => 1476451770.8262
                    [connection] => 1476451770.83
                    [request] => 1476451770.8302
                    [response] => 1476451770.8523
                    [finish] => 1476451770.8523
                )

            [time_consuming] => Array
                (
                    [trace] => Array
                        (
                            [connection] => 0.0038
                            [request] => 0.0001
                            [response] => 0.0221
                            [finish] => 0.0000
                        )

                    [total] => 0.0261
                )

        )

    [handler] => @stream_socket_client
    [options] => Array
        (
            [url] => http://localhost/test.php
            [method] => GET
            [protocol] => HTTP/1.1
            [headers] => Array
                (
                    [Host] => localhost
                )

            [parseUrl] => Array
                (
                    [scheme] => http
                    [host] => localhost
                    [path] => /test.php
                    [port] => 80
                    [query_string] => /test.php
                )

            [is_proxy_mode] => 
            [timeout] => 60
            [remote_socket] => tcp://localhost:80
            [flags] => 5
            [context] => Resource id #7
        )

    [proto] => HTTP/1.1
    [message] => OK
)
*/
```

#### ::streamBuilder()

基于GET的请求方式

##### 说明

`string Tii_Http::streamBuilder(string $protocol[, array $headers = [][, string $data = '']])`

##### 参数

protocol

> 协议字，如： `GET /test.php HTTP/1.1`

headers

> 头数据

data

> body数据

##### 返回值

HTTP的协议数据结构体。

##### 范例

Example #1 示例

``` php
<?php

print_r(Tii_Http::streamBuilder('GET /test.php HTTP/1.1', ['foo' => 'bar'], 'body'));
/**
GET /test.php HTTP/1.1
User-Agent: Tii/2.16.0223.1627
Content-Length: 4
Connection: close
foo: bar

body
*/
```

#### ::parseUrl()

解析 URL，返回其组成部分

##### 说明

`array Tii_Http::parseUrl(string $url)`

函数parse\_url的封装，并增加query\_string字段。

##### 参数

url

> 待解析的URL

##### 返回值

将返回一个关联数组 array，在目前至少会有一个元素在该数组中。数组中可能的键有以下几种：

* scheme - 如 http
* host
* port
* user
* pass
* path
* query - 在问号 ? 之后
* fragment - 在散列符号 # 之后
* query_string

对严重不合格的 URL，可能会抛异常。参考 [parse_url](http://php.net/manual/zh/function.parse-url.php)

##### 范例

Example #1 示例

``` php
<?php
$url = 'http://username:password@hostname/path?arg=value#anchor';
print_r(Tii_Http::parseUrl($url));
/**
Array
(
    [scheme] => http
    [host] => hostname
    [user] => username
    [pass] => password
    [path] => /path
    [query] => arg=value
    [fragment] => anchor
    [port] => 80
    [query_string] => /path?arg=value#anchor
)
*/
```


#### ::preparePostBody()

组装POST的body数据

##### 说明

`array Tii_Http::preparePostBody(array $formvars[, array $formfiles = []])`

##### 参数

formvars

> 请求的表单数据

formfiles

> POST的文件数据

##### 返回值

返回['data' => BODY数据, 'headers' => 头数据]

##### 范例

test.txt

```
hello world
```
Example #1 示例

``` php
<?php
print_r(Tii_Http::preparePostBody(['foo' => 'bar'], ['test' => 'test.txt']));
/**
Array
(
    [data] => --Boundaryeef3561d800af3205da56ea15e654dd4
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

bar
--Boundaryeef3561d800af3205da56ea15e654dd4
Content-Disposition: form-data; name="test"; filename="test.txt"
Content-Type: text/plain; Content-Transfer-Encoding: binary

hello world

--Boundaryeef3561d800af3205da56ea15e654dd4--

    [headers] => Array
        (
            [Content-Type] => multipart/form-data; boundary=Boundaryeef3561d800af3205da56ea15e654dd4
        )

)
*/
```

#### ::parser()

解析HTTP协议体

##### 说明

`array Tii_Http::parser(string $raw[, string $remoteAddr = '127.0.0.1'[, int $remotePort = 0]])`

##### 参数

raw

> HTTP协议数据

remoteAddr

> 客户端IP地址

remotePort

> 客户端端口

##### 返回值

返回`[$SERVER, $GET, $POST, $COOKIE, $REQUEST, $FILES, $http_raw_post_data]`

还原 `Tii_Http::streamBuilder`组装的HTTP协议数据。
有时候需要自行注入全局，如：`list($_SERVER,...) = 返回值数组`

##### 范例

Example #1 示例

``` php
<?php
$body = Tii_Http::streamBuilder('GET /test.php HTTP/1.1', ['foo' => 'bar'], 'body');
print_r(Tii_Http::parser($body, '127.0.0.1', 1234));
/**
Array
(
    [0] => Array
        (
            [DOCUMENT_ROOT] => 
            [REQUEST_TIME_FLOAT] => 1476454015.5118
            [REQUEST_TIME] => 1476454015
            [QUERY_STRING] => 
            [REQUEST_METHOD] => GET
            [REQUEST_URI] => /test.php
            [SERVER_PROTOCOL] => HTTP/1.1
            [SERVER_SOFTWARE] => Tii/2.16.0223.1627
            [SERVER_NAME] => Tii.Triones
            [HTTP_HOST] => 
            [HTTP_USER_AGENT] => Tii/2.16.0223.1627
            [HTTP_ACCEPT] => 
            [HTTP_ACCEPT_LANGUAGE] => 
            [HTTP_ACCEPT_ENCODING] => 
            [HTTP_COOKIE] => 
            [HTTP_CONNECTION] => close
            [REMOTE_ADDR] => 127.0.0.1
            [REMOTE_PORT] => 1234
            [HTTP_CONTENT_LENGTH] => 4
            [HTTP_FOO] => bar
        )

    [1] => Array
        (
        )

    [2] => Array
        (
        )

    [3] => Array
        (
        )

    [4] => Array
        (
        )

    [5] => Array
        (
        )

    [6] => 
)
*/
```

#### ::parseCookie()

解析cookie字符串为cookie数组

##### 说明

`array Tii_Http::parseCookie(string $cookie)`

##### 参数

cookie

> cookie字符串

##### 返回值

返回cookie数组

##### 范例

Example #1 示例

``` php
<?php
print_r(Tii_Http::parseCookie('LAST_LANG=zh; expires=Sat, 14-Oct-2017 14:14:35 GMT; Max-Age=31536000; path=/; domain=.php.net'));
/**
Array
(
    [LAST_LANG] => zh
    [expires] => Sat, 14-Oct-2017 14:14:35 GMT
    [Max-Age] => 31536000
    [path] => /
    [domain] => .php.net
)
*/
```


#### ::tidyCookie()

合并多个cookie字符串

##### 说明

`string Tii_Http::tidyCookie([mixed $args [, mixed $... ]])`

整理多个cookie字符串为一个新的字符串，常用于组装新的cookie头来模拟登录状态。
同时去以下字段：

* domain
* httponly
* path
* secure
* expires

##### 参数

args

> cookie字符串

##### 返回值

返回cookie字符串

##### 范例

Example #1 示例

``` php
<?php
print_r(Tii_Http::tidyCookie('LAST_LANG=zh; expires=Sat, 14-Oct-2017 14:14:35 GMT;','Max-Age=31536000; path=/; domain=.php.net'));
//LAST_LANG=zh; Max-Age=31536000
```

#### ::urlAppend()

组装url，主要是减少重复判断url中带参数后，到底是加?还是&的问题。

##### 说明

`string Tii_Http::urlAppend(string $url[, mixed $append = NULL])`

##### 参数

url

> url前缀

append

> 附加到url的数据，如果是字符串直接附加，如果是数组，那么会先对数组进行`http_build_query`处理，然后再附加。


##### 返回值

返回新的URL

##### 范例

Example #1 示例

``` php
<?php
print_r(Tii_Http::urlAppend('http://host.name', 'foo=bar'));
//http://host.name?foo=bar

print_r(Tii_Http::urlAppend('http://host.name?abc=xyz', 'foo=bar'));
//http://host.name?abc=xyz&foo=bar

print_r(Tii_Http::urlAppend('http://host.name', ['foo' => 'bar']));
//http://host.name?foo=bar
```

#### ::concat()

组装url路径，修剪掉多余的 / 字符

##### 说明

`string Tii_Http::concat([mixed $args [, mixed $... ]])`

##### 参数

args

> url的路径

##### 返回值

返回新的URL

##### 范例

Example #1 示例

``` php
<?php
echo Tii_Http::concat('http://host.name/', '/a/b/c', '/d/e/f');
//http://host.name/a/b/c/d/e/f
```

#### ::getMimeType()

根据后缀返回HTTP的内容类型

##### 说明

`string getMimeType(string $ext)`

##### 参数

ext

> 后缀，如：ext,html,txt,...

##### 返回值

返回内容类型，如：`html` 返回 `text/html`

##### 范例

Example #1 示例

``` php
<?php
echo Tii_Http:: getMimeType('html');//text/html
```


#### ::getHttpStatus()

根据状态码返回对应的信息字符串

##### 说明

`string Tii_Http::getHttpStatus([int $code = 200])`

##### 参数

code

> http状态码

##### 返回值

返回信息字符串，不存在返回NULL

##### 范例

Example #1 示例

``` php
<?php
echo Tii_Http:: getHttpStatus();//OK
echo Tii_Http:: getHttpStatus(200);//OK
echo Tii_Http:: getHttpStatus(302);//Moved Temporarily
echo Tii_Http:: getHttpStatus(400);//Bad Request
echo Tii_Http:: getHttpStatus(404);//Not Found
```

### 过滤器

在 ::post() 和 ::get() 方法中，存在过滤器：

`Tii_Event::filter('tii.http.options', $options)`

可对options参数进行变更，这个很有用，请查看举一反三部分。

### 举一反三

* 通过设置context来使用证书。

```
$options = [
	'remote_socket' => 'ssl://www.host.com:443',
	'context' => stream_context_create([
		'ssl' => [
			'local_cert' => "xxx.pem",
		]
	]),
];
Tii_Http::get('https://www.host.com', [], $options);
```	

* 通过代理访问

通过更改 `remote_socket`来达到移（通）花（过）接（代）木（理）来访问目标请求的目的。

 + 单独指定
 
	>直接设置options参数
	
 + 全局批量指定
 
	>通过过滤器实现
	
	```
	Tii_Event::register('tii.http.options', function($options)
	{
		$proxies = ['host.com' => 'tcp://proxy:port']
		if (isset($proxies[$options['parseUrl']['host']])) {
			$options['is_proxy_mode'] = true;
			$options['original_remote_socket'] = $options['remote_socket'];
			$options['remote_socket'] = $proxies[$options['parseUrl']['host']];
		}
		return $options;
	});
	```

日志
----

### 简介
主要是用来在开发的时候调试BUG记录日志用。

### 配置 

* tii.logger.handler 默认为：Tii_Logger_File
* tii.logger.priority 默认为：Tii_Logger_Constant::ERR


### 预定义常量

`Tii_Logger_Constant`

* EMERG = 0; // 系统不可用
* ALERT = 1; // **必须**立刻采取行动
	
	>例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下，**应该**发送一条警报短信把你叫醒。
* CRIT = 2; // 紧急情况

	>例如：程序组件不可用或者出现非预期的异常。
* ERR = 3; // 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
* WARN = 4; // 出现非错误性的异常。

	>例如：使用了被弃用的API、错误地使用了API或者非预想的不必要错误。
* NOTICE = 5; // 一般性重要的事件。
* INFO = 6; // 重要事件

	>例如：用户登录和SQL记录。
* DEBUG = 7; // debug 详情
* ALL = 8; // 任意等级的日志记录

### 类摘要
```php
Tii_Logger_Abstract
{
	public void setPriority([int $priority = Tii_Logger_Constant::ERR])
	public int function getPriority(void)
	public string function getPriorityName([int $priority])
	abstract public int function doLog(string $message[, int $priority = Tii_Logger_Constant::ERR[, mixed $extras = NULL]]);
}
```
### Tii\_Logger\_Abstract 抽象类

#### ->setPriority()

设置日志级别

##### 说明

`void setPriority([int $priority = Tii_Logger_Constant::ERR])`

设置处理日志级别，默认：`Tii_Logger_Constant::ERR`

##### 返回值

无返回值

#### ->getPriority()

获取处理日志级别

##### 说明

`int getPriority(void)`

##### 返回值

返回设置的日志级别

#### ->getPriorityName()

获取处理日志级别的名称

##### 说明

`string getPriorityName([int $priority])`

##### 参数

priority
>日志级别

##### 返回值

返回级别名称


#### ->doLog()

抽象方法，具体处理日志的方法

##### 说明

`public int function doLog(string $message[, int $priority = Tii_Logger_Constant::ERR[, mixed $extras = NULL]]);
`

##### 参数

message
>日志内容

priority
>级别

extras
>扩展数据

##### 返回值

返回记录号

### Tii\_Logger\_File 类

日志存储到文本的实现

#### ->__construct()

构造函数

##### 说明

`void __construct([$path = NULL[, $format = 'Ymd']])`

参数初始化设置

##### 参数

path
>日志输出路径，默认为缓存目录。参考：`Tii_Filesystem::getTempDir`

format
>文件名生成格式， 默认：Ymd。参考：`Tii_Time::format`

##### 返回值

无返回值

### Tii\_Logger\_Dao 类

日志存储到数据库的实现

#### ->__construct()

构造函数

##### 说明

`void __construct([$dao = NULL[, $table = 'Ymd'[, $fields = []]]])`

参数初始化设置

##### 参数

dao
>数据库句柄，默认：`Tii_Dao`

table
>表名，默认：`logger`

fields
>数据库表字段
> 
> | 字段       | 默认值 | 字段类型 | 描述 |
> | ------------- | -----| -----| -----|
> | id | id | bigint,AUTO_INCREMENT | 自增ID |
> | priority | priority | tinyint | 级别 |
> | message | message | varchar(255) | 日志 |
> | extras |  extras | LONGTEXT | 扩展数据 |
> | gmt_created | gmt_created | datetime | 创建日期 Y-m-d H:i:s |

##### 返回值

无返回值


数学
----
### 简介
经常用到的一些数学相关的计算方法

### 配置 

### 预定义常量

### 类摘要
```php
Tii_Math
{
	public static int function getSequenceId(void)
	public static float function getSystemBits(void)
	public static int function getStatus(int $status, int $bit[, int $span = 1])
	public static mixed function setStatus(int $status, int $bit[, mixed $val = true[, int $span = 1]])
	public static int function getFloatLength(float $num)
	public static bool function pather(array &$chain, array $arr, string $n, string $v, string $c[, bool $o = true])
	public static mixed function toGuidString(mixed $mix[, callable $func = 'md5'])
	public static string function random([int $len = 6[, string $str1 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz'[, string $str2 = '']]])
	public static string function randomDigital([int $len = 6])
	public static string function randomReadableString([int $len = 6])
	public static string function guid16(void)
	public static string function mask(string $str, array $indexes[, string $mask = '*'])
	public static string function guid([string $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz'])
	public static mixed function getScaleRandom(array $scales[, array $weight = []])
	public static string function hash(string $key[, array $nodes = [][, int $replicas = 32]])
	public static string function decst(int $number)
	public static int function stdec(string $str)
	public static string function uniqId([string $prefix = ''])
	public static string function hashStr(string $str[, bool $decst = true])
	public static string function hashArr([mixed $args [, mixed $... ]])
}
```
### Tii_Math 类

#### ::getSequenceId()

获取自增值

##### 说明

`int Tii_Math::getSequenceId ( void )`

同一个进程中获取自增的整数，每次获取都会自动增加1

##### 返回值

返回 int

##### 范例
Example #1 自增
``` php
<?php
echo Tii_Math::getSequenceId();//1
echo Tii_Math::getSequenceId();//2
```

#### ::getSystemBits()

获取系统的进制

##### 说明

`float Tii_Math::getSystemBits ( void )`

##### 返回值

返回float：32、64

##### 参见

#### ::getStatus()

获取二进制的某跨度代表的值

##### 说明

`int Tii_Math::getStatus(int $status, int $bit[, int $span = 1])`

##### 参数

status
> 当前数值

bit
> 位移的开始位数

span
> 跨度

##### 返回值

成功调用则返回跨度位数代表的数值。

##### 范例

Example #1 获值
``` php
<?php
$status = 5;
echo Tii_Math::getStatus($status, 1);//1
echo Tii_Math::getStatus($status, 2);//0
echo Tii_Math::getStatus($status, 3);//1
```

##### 参见


#### ::setStatus()

设置数值的第几位进制数值

##### 说明

`mixed Tii_Math::setStatus(int $status, int $bit[, mixed $val = true[, int $span = 1]])`

##### 参数

status
> 当前数值

bit
> 位移的开始位数

val
> 为bool值的时候，true为1，false为0，其余都是int，要注意和span配合是否会溢出
> 值要符合[$val <= (pow(2, $span) - 1)]的要求

span
> 跨度

##### 返回值

成功调用则返回新的数值。不成功则返回 false。

##### 范例

Example #1 设值
``` php
<?php
$status = 0;
echo $status = Tii_Math::setStatus($status, 1, true);//1
echo $status = Tii_Math::setStatus($status, 2, false);//1
echo $status = Tii_Math::setStatus($status, 3, true);//5
```


#### ::getFloatLength()

计算浮点的小数点位数

##### 说明

`int  Tii_Math::getFloatLength(float $num)`

##### 参数

num
> 浮点数

##### 返回值

小数点的位数

##### 范例

Example #1 取小数点的位数
``` php
<?php
echo Tii_Math::getFloatLength(0.123456);//6
```
##### 参见


#### ::pather()

从嵌套中筛选值，常用于路径递归取值

##### 说明

`bool Tii_Math::pather(array &$chain, array $arr, string $n, string $v, string $c[, bool $o = true])`

##### 参数

chain
> 结果集

arr
> 嵌套体

n
> 比对的值

v
> 数组的键值

c
> 数组中子集的键值

o
> 返回被选中的值还是整个数组，true为返回整个数组


##### 返回值

返回值为数组

##### 范例

Example #1 递归取值
``` php
<?php
$chain = [];
Tii_Math::pather($chain, [['id' => 1, 'key' => 'founder', 'childKey'=>[['id' => 11, 'key' => 'me', 'childKey'=>[]]]]], 'me', 'key', 'childKey');
print_r($chain);
/**
Array
(
    [0] => founder
    [1] => me
)
*/

$chain = [];
Tii_Math::pather($chain, [['id' => 1, 'key' => 'founder', 'childKey'=>[['id' => 11, 'key' => 'me', 'childKey'=>[]]]]], 'me', 'key', 'childKey', false);
print_r($chain);
/**
Array
(
    [0] => Array
        (
            [id] => 1
            [key] => founder
            [childKey] => Array
                (
                    [0] => Array
                        (
                            [id] => 11
                            [key] => me
                            [childKey] => Array
                                (
                                )

                        )

                )

        )

    [1] => Array
        (
            [id] => 11
            [key] => me
            [childKey] => Array
                (
                )

        )

)
*/
```

##### 参见

#### ::toGuidString()

生成目标对象唯一的标识符

##### 说明

`mixed Tii_Math::toGuidString(mixed $mix[, callable $func = 'md5'])`

##### 参数

mix
> 待混淆值

func
> 混淆的函数，如：md5,crc32,sha1,...

##### 返回值

返回混淆函数对应的返回值

##### 范例

Example #1 用md5做混淆函数返回唯一值
``` php
	echo Tii_Math::toGuidString('tii');//dae63fb4ce0398632fc27357e9cba450
```
##### 参见

#### ::random()

获取随机字符串

##### 说明

`string Tii_Math::random([int $len = 6[, string $str1 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz'[, string $str2 = '']]])`

##### 参数

len
> 需要生成的字符串长度

str1
> 前段参与随机的字符串，类似：声母

str2
> 后段参与随机的字符串，类似：韵母

##### 返回值

返回随机后的字符串

##### 范例

Example #1 返回默认随机字符串
``` php
	echo Tii_Math::random();//ppuJR8
```
##### 参见

#### ::randomReadableString()

获取随机“可读”字符串

##### 说明

`string Tii_Math::randomReadableString([int $len = 6])`

##### 参数

len
> 需要生成的字符串长度

##### 返回值

返回随机后的字符串

##### 范例

Example #1 返回默认随机字符串
``` php
	echo Tii_Math::randomReadableString();//pucugu
```

##### 参见

`Tii_Math::random()`


#### ::randomDigital()

获取随机“数字”字符串

##### 说明

`string Tii_Math::randomDigital([int $len = 6])`

##### 参数

len
> 需要生成的字符串长度

##### 返回值

返回随机后的纯数字字符串

##### 范例

Example #1 用md5做混淆函数返回唯一值
``` php
	echo Tii_Math:: randomDigital();//821456
```
##### 参见

`Tii_Math::random()`

#### ::mask()

给字符串中指定的位置打上马赛克（替换字符）

##### 说明

`string Tii_Math::mask(string $str, array $indexes[, string $mask = '*'])`

##### 参数

str
> 待处理的字符串

indexes
> 字符中指定的坐标数组

mask
> 替换字符串

##### 返回值

返回马赛克后的字符串

##### 范例

Example #1 数据脱敏（对某些敏感信息进行数据变形,实现敏感隐私数据的可靠保护）
``` php
$email = 'alacner@gmail.com';
echo Tii_Math::mask($email, range(3, (strpos($email, '@')-1)));//ala****@gmail.com
echo Tii_Math::mask('12312345678', range(3, 6));;//123****5678
```

Example #2 唯一码生成器
``` php
echo Tii_Math::mask(Tii_Math::random(32), [8, 13, 18, 23], '-');//XembMRna-QZlh-J368-1nT0-fURVuIiI
```
##### 参见

#### ::guid()

随机生成唯一的标识符，类似："xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"

##### 说明

`string Tii_Math::guid([string $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz'])`

##### 参数

chars
> 参与随机的字符串，默认为大小写+数字

##### 返回值

返回标示符字符串

##### 范例

Example #1 英文+数字的字符串
``` php
	echo Tii_Math::guid();//XembMRna-QZlh-J368-1nT0-fURVuIiI
```
##### 参见

`Tii_Math::random()`

`Tii_Math::mask()`


#### ::guid16()

随机16进制生成唯一的标识符（小写字符）

##### 说明

`mixed Tii_Math::guid16(void)`

##### 返回值

返回混淆函数对应的返回值

##### 范例

Example #1
``` php
	echo Tii_Math:: guid16();//cf814149-cf73-9b2a-d8d7-f35283c7
```
##### 参见

`Tii_Math::guid()`


#### :: getScaleRandom()

按比例随机获取值，应用场景很多，如：

* 抽奖
* 负载均衡

##### 说明

`mixed Tii_Math::getScaleRandom(array $scales[, array $weight = []])`

##### 参数

scales
> 比例，如：['一等奖' => 10,'二等奖' => 20,'三等奖' => 70]

weight
> 权重，比例是固定的，那我要提高某些同志的中奖概率，遇到张三的时候传入:['一等奖' => 100]

##### 返回值

返回scales中的键值，如：一等奖

##### 范例

Example #1 概率抽奖（总数100）（一定数量的需要在此基础上做限数）
``` php
$result = [];
for($i = 0; $i < 1000; $i++) {
        $result[Tii_Math::getScaleRandom(['一等奖' => 10, '二等奖' => 20, '三等奖' => 70])]++;
}
print_r($result);
/**
Array
(
    [三等奖] => 706
    [一等奖] => 102
    [二等奖] => 192
)
*/
```

Example #1 负载均衡
``` php
$result = [];
for($i = 0; $i < 1000; $i++) {
        $result[Tii_Math::getScaleRandom(['192.168.1.100' => 100, '192.168.1.101' => 200, '192.168.1.102' => 50])]++;
}
print_r($result);
/**
Array
(
    [192.168.1.101] => 582
    [192.168.1.100] => 268
    [192.168.1.102] => 150
)
*/
```

##### 参见

#### ::hash()

一致性哈希算法

##### 说明

`string Tii_Math::hash(string $key[, array $nodes = [][, int $replicas = 32]])`

##### 参数

key
> 待确定节点的key

nodes
> 节点数据

replicas
> 副本数量（虚拟节点）个数，默认32个。数目越大，hash分布越均匀。

##### 返回值

返回对应节点数据

##### 范例

Example #1 分布式缓存服务器
``` php
	echo Tii_Math::hash('cache_key', ['192.168.1.1', '192.168.1.2', '192.168.1.3']);//192.168.1.1
	echo Tii_Math::hash('change_cache_key', ['192.168.1.1', '192.168.1.2', '192.168.1.3']);//192.168.1.2
```
##### 参见


#### ::decst()

十进制的数字转换为64进制，转换顺序：0123456789ABCDEFGHIGKLMNOPQRSTUVWXYZabcdefghigklmnopqrstuvwxyz

##### 说明

`string Tii_Math::decst(int $number)`

##### 参数

number
> 十进制数字

##### 返回值

返回64进制的字符串

##### 范例

Example #1 十进制转换成64进制
``` php
	echo Tii_Math::decst(1234567890);//1LY7VK
```
##### 参见


#### ::stdec()

64进制转换为十进制的数字，转换顺序：0123456789ABCDEFGHIGKLMNOPQRSTUVWXYZabcdefghigklmnopqrstuvwxyz


##### 说明

`int Tii_Math::stdec(string $str)`

##### 参数

str
> 64进制的字符串

##### 返回值

返回十进制的数字

##### 范例

Example #1 64进制转十进制
``` php
	echo Tii_Math::stdec('1LY7VK');//1234567890
```
##### 参见


#### ::uniqId()

获得一个前缀根据当前时间以微秒为单位唯一标识符。

##### 说明

`string Tii_Math::uniqId([string $prefix = ''])`

##### 参数

prefix
> 前缀字符串

##### 返回值

返回唯一标识符字符串

##### 范例

Example #1 唯一值生成器
``` php
echo Tii_Math::uniqId();//1bslnCazHO
echo Tii_Math::uniqId('prev');//prev1bslnCazUc
```
##### 参见

#### ::hashStr()

哈希字符串为数字

##### 说明

`mixed Tii_Math::hashStr(string $str[, bool $decst = true])`

##### 参数

str
> 待混淆值

decst
> 是否转成64进制

##### 返回值

返回哈希值

##### 范例

Example #1 用md5做混淆函数返回唯一值
``` php
	echo Tii_Math::hashStr('tii');//1zwDRE
	echo Tii_Math::hashStr('tii', false);//1831364012
```
##### 参见


#### ::hashArr()

对多参数进行SORT_STRING排序，然后利用sha1生成哈希值

##### 说明

`mixed Tii_Math::hashArr([mixed $args [, mixed $... ]])`

##### 参数

args
> 待混淆值

##### 返回值

返回哈希值

##### 范例

Example #1 生成哈希
``` php
echo Tii_Math::hashArr('tii');//c3c84bf0c498946f97959101e536652e6353eb59
echo Tii_Math::hashArr('tii', 'is', 'usefull');//c69a2f9480d2a38ebcaf6250a9d6512c600470cb
echo Tii_Math::hashArr('tii', 'usefull', 'is');//c69a2f9480d2a38ebcaf6250a9d6512c600470cb
```
##### 参见

网络
----

### 简介
经常用到的一些网络相关的方法

### 配置 

### 预定义常量

### 类摘要
```php
Tii_Network
{
	public static int function getAvailablePort([int $start = 10000[, int $end = 20000]])
	public static string function getIp(void)
	public static string function getMacAddr(void)
	public static array function getMacAddrs(void)
	public static array function parseHost(string $host[, $port = 22])
	public static int function getPort(string $host[, int $port = 22])
	public static array function parseIpRange(string $range)	public static bool function ipInRange(string $ip, string $range)	public static bool function ipInRanges(string $host[, array $rules = [][, bool $default = true]])
}
```
### Tii_Network 类

#### ::getAvailablePort()

获取本地可用端口

##### 说明

`int Tii_Network::getAvailablePort([int $start = 10000[, int $end = 20000]])`

利用fsockopen试探本地端口是否可用，如果不能打开句柄，则表示可以使用，假设在区间都没有找到，那么会sleep 1秒，然后继续循环重试，直到找到为止。

##### 参数

start
> 起始端口

end
> 截止端口
 
##### 返回值

返回本地可用端口

##### 范例
Example #1 获取可用端口
``` php
<?php
echo Tii_Network::getAvailablePort();//10000
echo Tii_Network::getAvailablePort(3456, 5678);//3456
```

#### ::getIp()

获取本地可用端口

##### 说明

`string Tii_Network::getIp(void)`

利用exec执行本地的ipconfig或ifconfig来解析当前服务器ip地址。

##### 返回值

返回当前服务器ip地址

##### 范例
Example #1 获取当前服务器ip地址
``` php
<?php
echo Tii_Network::getIp();//192.168.1.101
```

#### ::getMacAddr()

获取当前服务器网卡mac地址

##### 说明

`string Tii_Network::getMacAddr(void)`

利用exec执行本地的ipconfig或ifconfig来解析当前服务器网卡的mac地址。

##### 返回值

返回网卡mac地址

##### 范例
Example #1 获取网卡mac地址
``` php
<?php
echo Tii_Network::getMacAddr();//00:1c:40:20:10:08
```

#### ::getMacAddrs()

获取当前服务器网卡的所有mac地址

##### 说明

`array Tii_Network::getMacAddrs(void)`

利用exec执行本地的ipconfig或ifconfig来解析当前服务器网卡的所有mac地址。

##### 返回值

返回网卡所有mac地址

##### 范例
Example #1 返回网卡所有mac地址
``` php
<?php
print_r(Tii_Network::getMacAddrs());
/**
Array
(
	[0] => 00:1c:40:20:10:08
	[1] => 00:1c:43:13:05:12
)
*/
```

#### ::parseHost()

获取本地可用端口

##### 说明

`array Tii_Network::parseHost(string $host[, $port = 22])`

解析host为ip和port

##### 参数

host
> 待解析的host，如：192.168.1.2:1234，12.34.56.78

port 
> 默认端口

##### 返回值

返回[ip地址，端口号]

##### 范例
Example #1 解析host
``` php
<?php
print_r(Tii_Network::parseHost('192.168.1.2:1234', 3456));
/**
Array
(
	[0] => 192.168.1.2
	[1] => 1234
)
*/

print_r(Tii_Network::parseHost('12.34.56.78', 3456));
/**
Array
(
	[0] => 12.34.56.78
	[1] => 3456
)
*/
```

#### ::getPort()

根据host获取端口

##### 说明

`int Tii_Network::getPort(string $host[, int $port = 22])`

正则解析出端口。

##### 参数

host
> 待解析的host，如：192.168.1.2:1234，12.34.56.78

port 
> 默认端口

##### 返回值

返回端口

##### 范例
Example #1 获取端口
``` php
<?php
echo Tii_Network::getPort('192.168.1.2:1234', 3456);//1234
echo Tii_Network::getPort('12.34.56.78', 3456);//3456
```

#### ::parseIpRange()

解析ip地址的网络范围

##### 说明

`array Tii_Network::parseIpRange(string $range)`

网络范围表示方法：

* 单IP: 1.2.3.4
* 通配符: 1.2.3.*
* CIDR值: 1.2.3.0/24 或 1.2.3.4/255.255.255.0
* IP段: 1.2.3.0-1.2.3.255

只支持IPv4，如实现IPv6的支持，需要至少32位无符号整数进行位运算，而PHP只支持32位有符号整数，虽然可以使用float类型突破这个限制，但float类型不支持位运算。

##### 参数

range
> 网络范围

##### 返回值

返回[起始ip地址, 截止ip地址]

##### 范例
Example #1 解析ip地址的网络范围
``` php
<?php
print_r(Tii_Network::parseIpRange('1.2.3.*'));
/**
Array
(
	[0] => 1.2.3.0
	[1] => 1.2.3.255
)
*/
```

#### ::ipInRange()

检查IP是否在指定的网络范围内

##### 说明

`bool Tii_Network::ipInRange(string $ip, string $range)`

##### 参数

ip
> IP地址

range
> 网络范围

##### 返回值

如果在返回true，否则返回false

##### 范例
Example #1 检查IP是否在指定的网络范围内
``` php
<?php
echo Tii_Network::ipInRange('1.2.3.4', '1.2.3.*');//true
```

#### ::ipInRanges()

检查IP是否在多个指定的网络范围规则内

##### 说明

`bool Tii_Network::ipInRanges(string $ip[, array $rules = [][, bool $default = true]])`

##### 参数

ip
> IP地址

rules
> 网络范围规则，如：[range => deny_or_not[,...]] 或 [['range' => '1.2.3.*', 'allow' => ''][,...]]

default
> 默认值

##### 返回值

如果在返回true，否则返回false

##### 范例

Example #1 检查IP是否在多个指定的网络范围规则内
``` php
<?php
echo Tii_Network::ipInRanges('1.2.3.4', ['1.2.3.*' => false], true);//false
```

打包器
----
安全
----
服务类
----

### 简介
自动利用`Tii::object`加载子目录下的同级类。

静态类加载器，只需继承 `Tii_Service` 类，如：

```php
<?php
class ClassName_Service extends Tii_Service {}
```

同级类实现，如：

```php
<?php
class ClassName_Service_Foo {
	public function __construct($arg1, $arg2...){...}
	public function bar(...){...}
}
```

可以用2种方式来调用:

* `ClassName_Service::foo($arg1, $arg2...)->bar(...);`

	> 方法名小写，实例化这个类的时候，会自动采用`Tii_Delegate`代理类方式，这样被调用的方法都会解析注释，如：自动增加缓存等，参考：`Tii_Delegate`章节
* `ClassName_Service::Foo($arg1, $arg2...)->bar(...);`

	> 方法名大写，直接实例化，等同一般的实例化。
 
时间
----

### 简介
时间封装

### 配置 

### 预定义常量

### 类摘要
```php
Tii_Time
{
	public static bool function timezone([string $timezone = 'UTC'])
	public static float function micro(void)
	public static float function milli(void)
	public static int function now([bool $online = false])
	public static int function online(void)
	public static int function locale(void)
	public static int function totime([mixed $time = NULL[, mixed $now = NULL]])	
	public static string function format([string $format = 'Y-m-d H:i:s'[, mixed $time = NULL[, mixed $now = NULL]]])	
	public static int function formatted(string $format[, mixed $time = NULL[, mixed $now = NULL]])
	public static int function interval(mixed $time1[, mixed $time2 = NULL])
	public static bool function inSeconds(mixed $time1[, int $forward = -60[, int $afterward = 60[, mixed $time2 = NULL]]])
	public static string function readable(float $microtime[, string $format = '%.3f%s'[, int $round = 3]])
	public static int function nexttime([string $cron = '* * * * *'[, mixed $time = NULL[, mixed $now = NULL]]])
}
```
### Tii_Time 类

#### ::timezone()

设置时区

##### 说明

`bool Tii_Time::timezone([string $timezone = 'UTC'])`

设定用于一个脚本中所有日期时间函数的默认时区

##### 参数

timezone
> 时区标识符，例如 UTC 或 Asia/Shanghai。合法标识符列表见[所支持的时区列表](http://php.net/manual/zh/timezones.php)。
 
##### 返回值

如果 timezone 参数无效则返回 FALSE，否则返回 TRUE。

##### 范例
Example #1 时区变化导致格式化显示有变化
``` php
<?php
Tii_Time::timezone('Aisa/Shanghai');
$time = strtotime("2012-07-05 16:43:21");//1341477801
Tii_Time::timezone('UTC'); 
echo date("Y-m-d h:i:s", $time); //2012-07-05 08:43:21
```

#### ::micro()

返回当前 Unix 时间戳和微秒数

##### 说明

`float Tii_Time::micro(void)`

当前 Unix 时间戳以及微秒数。本函数仅在支持 gettimeofday() 系统调用的操作系统下可用。

##### 返回值

返回一个浮点数

##### 范例
Example #1 
``` php
<?php
echo Tii_Time::micro();//1476109725.7075
```

#### ::milli()

返回当前 Unix 的毫秒时间戳

##### 说明

`float Tii_Time::milli(void)`

在Tii_Time::micro()扩大了1000倍并取整

##### 返回值

返回一个没小数点的浮点数

##### 范例
Example #1 
``` php
<?php
echo Tii_Time::milli();//1476109725707
```

#### ::now()

返回当前时间戳

##### 说明

`int Tii_Time::now([bool $online = false])`

##### 参数

online
> 是否实时调用NTP服务器并返回时间。
 
##### 返回值

如果online为TRUE返回NTP服务器的时间，其他为本地机器时间

##### 范例
Example #1 获取当前时间
``` php
<?php
echo Tii_Time::now();//1476109725
```

##### 参考

`int Tii_Time::online(void)` `int Tii_Time::locale(void)`


#### ::online()

返回NTP服务器的时间

##### 说明

`int Tii_Time::online(void)`

[http://tycho.usno.navy.mil/ntp.html](http://tycho.usno.navy.mil/ntp.html)

[http://tf.nist.gov/tf-cgi/servers.cgi](http://tf.nist.gov/tf-cgi/servers.cgi)

默认从东区的["time.nist.gov", "time-d.nist.gov"]获取NTP服务器时间，如果需要添加更多服务器，可以通过配置配置项：'tii.time.servers'来增加。
 
##### 返回值

时间戳

##### 范例
Example #1 获取NTP服务器时间戳
``` php
<?php
echo Tii_Time::online();//1476109725
```

#### ::locale()

返回当前的 Unix 时间戳

##### 说明

`int Tii_Time::locale(void)`

返回自从 Unix 纪元（格林威治时间 1970 年 1 月 1 日 00:00:00）到当前时间的秒数。
 
##### 返回值

时间戳

##### 范例
Example #1 获取时间戳
``` php
<?php
echo Tii_Time::locale();//1476109725
```

#### ::totime()

将任何英文文本的日期时间描述解析为 Unix 时间戳

##### 说明

`int Tii_Time::totime([mixed $time = NULL[, mixed $now = NULL]])`

本函数预期接受一个包含美国英语日期格式的字符串并尝试将其解析为 Unix 时间戳（自 January 1 1970 00:00:00 GMT 起的秒数），其值相对于 now 参数给出的时间，如果没有提供此参数则用系统当前时间。

##### 参数

time
> 日期/时间字符串

now
> 用来计算返回值的日期/时间字符串
 
##### 返回值

成功则返回时间戳，否则返回 FALSE。

##### 范例
Example #1 时间戳
``` php
<?php
echo Tii_Time::totime("now");//1476110816
echo Tii_Time::totime("10 September 2000");//968515200
echo Tii_Time::totime("+1 day");//1476197216
echo Tii_Time::totime("+1 week");//1476715616
echo Tii_Time::totime("+1 week 2 days 4 hours 2 seconds");//1476902818
echo Tii_Time::totime("next Thursday");//1476288000
echo Tii_Time::totime("last Monday");//1475424000
echo Tii_Time::totime("last Monday", "+1 day");//1476028800
```

#### ::format()

格式化一个本地时间／日期

##### 说明

`string Tii_Time::format([string $format = 'Y-m-d H:i:s'[, mixed $time = NULL[, mixed $now = NULL]]])	`

返回将时间字符串 time 按照给定的格式字串而产生的字符串。如果没有给出时间戳则使用本地当前时间。换句话说，time 是可选的，默认值为 time()。而now是time的相对时间。

> 有效的时间戳典型范围是格林威治时间 1901 年 12 月 13 日 20:45:54 到 2038 年 1 月 19 日 03:14:07。（此范围符合 32 位有符号整数的最小值和最大值）。不过在 PHP 5.1 之前此范围在某些系统（如 Windows）中限制为从 1970 年 1 月 1 日到 2038 年 1 月 19 日。

##### 参数

format
> 输出的日期 string 格式。 参见下文中的 格式化选项。 同时，还可以使用 预定义日期常量 ，例如：常量 DATE_RSS 表示格式化字符串 'D, d M Y H:i:s'。
>
> | format 字符        | 描述           | 返回值示例  |
> | ------------- |:-------------:| -----:|
> | 日      | --- | --- |
> | 天	 | ---	 | --- |
> | d |	一个月中的第几天，有前导 0 的 2 位数字 |	从 01 到 31 |
> | D |	3 个字符表示的星期几 |	从 Mon 到 Sun |
> | j |	一个月中的第几天，无前导 0 |	从 1 到 31 |
> | l (lowercase 'L') |	星期几，英文全称 |	从 Sunday 到 Saturday |
> | N |	ISO-8601 规定的数字表示的星期几（PHP 5.1.0 新加 ）	 |从 1 （表示星期一）到 7 （表示星期日） |
> | S |	一个月中的第几天，带有 2 个字符表示的英语序数词。 |	st， nd， rd 或者 th。 可以和 j 联合使用。
> | w |	数字表示的星期几 |	从 0 （星期日） 到 6 （星期六） |
> | z |	一年中的第几天，从 0 开始计数 |	从 0 到 365 |
> | 周 |	--- |	--- |
> | W |	ISO-8601 规范的一年中的第几周，周一视为一周开始。（PHP 4.1.0 新加）	 | 示例： 42 （本年第42周） |
> | 月 |	--- |	--- |
> | F |	月份英文全拼，例如：January 或 March |	从 January 到 December |
> | m |	带有 0 前导的数字表示的月份	 | 从	 01 到 12 |
> | M |	3 个字符表示的月份的英文简拼 |	从 Jan 到 Dec |
> | n |	月份的数字表示，无前导 0 |	1 through 12 |
> | t |	给定月份中包含多少天 |	从 28 到 31 |
> | 年 |	--- |	--- |
> | L |	是否为闰年 |	如果是闰年，则返回 1，反之返回 0。 |
> | o |	ISO-8601 规范的年份，同 Y 格式。有一种情况除外：当 ISO 的周数（W）属于前一年或者后一年时，会返回前一年或者后一年的年份数字表达。 属于前一年或者后一年时，会返回前一年或者后一年的年份数字表达。 （PHP 5.1.0 新加）	 | 示例：1999 或 2003 |
> | Y |	4 位数字的年份 |	示例：1999 或 2003 |
> | y |	2 位数字的年份 |	示例： 99 或 03 |
> | 时间 |	--- |	--- |
> | a |	上午还是下午，2 位小写字符 |	am 或 pm |
> | A |	上午还是下午，2 位大写字符 |	AM 或 PM |
> | B |	斯沃琪因特网时间 |	从 000 到 999 |
> | g |	小时，12时制，无前导 0 |	从 1 到 12 |
> | G |	小时，24时制，无前导 0 |	从 0 到 23 |
> | h |	小时，12时制，有前导 0 的 2 位数字 |	从 01 到 12 |
> | H |	小时，24时制，有前导 0 的 2 位数字 |	00 through 23 |
> | i |	分钟，有前导 0 的 2 位数字 |	从 00 到 59 |
> | s |	秒，有前导 0 的 2 位数字 |	从 00 到 59 |
> | u |	毫秒 （PHP 5.2.2 新加） |	示例： 654321 |
> | 时区 |	--- |	--- |
> | e |	时区标识（PHP 5.1.0 新加） |	示例: UTC, GMT, Atlantic/Azores |
> | I （大写字母 i） |	是否夏令时 |	如果是夏令时则返回 1，反之返回 0。 |
> | O |	和格林威治时间（GMT）的时差，以小时为单位	 | 示例： +0200 |
> | P |	和格林威治时间（GMT）的时差，包括小时和分钟，小时和分钟之间使用冒号（:）分隔（PHP 5.1.3 新加） |	示例： +02:00 |
> | T |	时区缩写 |	示例：EST, MDT ... |
> | Z |	以秒为单位的时区偏移量。UTC 以西的时区返回负数，UTC 以东的时区返回正数。 | 	从 -43200 到 50400 |
> | 完整的日期/时间 |	--- |	--- |
> | c |	ISO 8601 日期及时间（PHP 5 新加） |	2004-02-12T15:19:21+00:00 |
> | r |	» RFC 2822 格式的日期和时间 |	示例：Thu, 21 Dec 2000 16:01:07 +0200 |
> | U |	自 1970 年 1 月 1 日 0 时 0 分 0 秒（GMT 时间）以来的时间，以秒为单位 |	参见[time()](http://php.net/manual/zh/function.time.php) |
 
time
> 日期/时间字符串

now
> 用来计算返回值的日期/时间字符串

##### 返回值

返回格式化后的日期时间的字符串表达。 如果 time 参数和 now 参数不是一个有效数值，则返回 FALSE。

##### 范例
Example #1 format() 示例
``` php
<?php
echo Tii_Time::format();//"2012-07-05 16:43:21"
echo Tii_Time::format('l jS \of F Y h:i:s A');//Monday 8th of August 2005 03:12:46 PM
echo Tii_Time::format('l jS \of F Y h:i:s A', "+1 day");//Tuesday 9th of August 2005 03:12:46 PM
```

#### ::formatted()

将format的结果再totime成时间戳

##### 说明

`int Tii_Time::formatted(string $format[, mixed $time = NULL[, mixed $now = NULL]])`

可用于计算整点时间戳，如，当前小时：`Tii_Time::formatted('Y-m-d H:00:00')`

##### 参数

与Tii_Time::format()同
 
##### 返回值

时间戳

##### 范例
Example #1 formatted 示例
``` php
<?php
echo Tii_Time::formatted('Y-m-d H:00:00');//1476115200
```

#### ::interval()

计算结束时间减去开始时间 的时间差

##### 说明

`int Tii_Time::interval(mixed $time1[, mixed $time2 = NULL])`

在计算有效期方面非常有效。。。

##### 参数

time1
> 开始时间字符串

time2
> 结束时间字符串（默认为当前时间）
 
##### 返回值

时间差，单位：秒。

##### 范例
Example #1 interval() 示例
``` php
<?php
echo Tii_Time::interval("-1 day");//86400
echo Tii_Time::interval("+1 day", "+2 days");//86400
```

#### ::inSeconds()

判断是否在有效时间范围内

##### 说明

`bool Tii_Time::inSeconds(mixed $time1[, int $forward = -60[, int $afterward = 60[, mixed $time2 = NULL]]])`

在计算调用服务器时差在N秒内的特别有效。

##### 参数

time1
> 开始时间字符串

forward
> 往前范围

afterward
> 往后范围

time2
> 结束时间字符串（默认为当前时间）
 
##### 返回值

如果在范围内返回 TRUE, 否则返回 FALSE。

##### 范例
Example #1 获取可用端口
``` php
<?php
echo Tii_Time::inSeconds("+1 days");//false
echo Tii_Time::inSeconds("+1 seconds");//true
```

#### ::readable()

便于阅读的格式显示时间

##### 说明

`string Tii_Time::readable(float $microtime[, string $format = '%.3f%s'[, int $round = 3]])`

##### 参数

microtime
> 毫秒
 
format
> 格式，默认：'%.3f%s'

round
> 小数点

##### 返回值

格式化后的字符串

##### 范例
Example #1 readable 示例
``` php
<?php
echo Tii_Time::readable(0.032);//32ms
echo Tii_Time::readable(110.032);//110.032s
```

#### ::nexttime()

发现下一个执行时间戳解析crontab语法。

##### 说明

`int Tii_Time::nexttime([string $cron = '* * * * *'[, mixed $time = NULL[, mixed $now = NULL]]])`

非此即彼的用于指定一个星期和一个月的日期。

 * 30 21 * * * => every 21:30
 * 45 4 1,10,22 * * => every 4:45 at 1,10,22 days a month
 * 10 1 * * 6,0 => every saturday and sunday 1:10
 * 0,30 18-23 * * * => every 18:00 to 23:00 between every 30 minutes
 * 0 23 * * 6 => every saturday 11:00 pm
 * * *\/1 * * * => every hour
 * * 23-7/1 * * * => at 11 pm to 7 am, between every two hours

##### 参数

cron
> crontab格式的时间表达式
> 
> | 第几个       | 描述 |
> | ------------- | -----|
> | 0 | min (0 - 59) |
> | 1 | hour (0 - 23) |
> | 2 | day of month (1 - 31) |
> | 3 |  month (1 - 12) |
> | 4 |  day of week (0 - 6) (Sunday=0) |

time
> 日期/时间字符串

now
> 用来计算返回值的日期/时间字符串
 
##### 返回值

如果 存在就返回下一个执行时间戳值，否则返回 NULL。

##### 范例
Example #1 nextime 示例
``` php
<?php
echo Tii_Time::nexttime();//1476117600
echo Tii_Time::nexttime("* 1 * * *");//1476118800
```

计时器
----
当前版本
----

### 简介
框架版本

### 预定义常量

VERSION
> 当前版本数据，如：2.16.0223.1627

网络编程
----

### 简介

该部分inspired by Workerman，是高性能的PHP socket 服务器实现，基于PHP多进程以及libevent事件轮询库，PHP开发者只要实现一两个接口，便可以很容易的开发出基于socket的高性能的网络应用，例如Rpc服务、聊天室服务器、手机游戏服务器等，而不用去了解PHP socket以及PHP多进程细节。

以为支持一个PHP多进程服务器框架，具有PHP进程管理以及socket通信的模块，所以不依赖php-fpm、nginx或者apache等这些容器便可以独立运行。


### 特性

* 纯PHP开发

>完全使用PHP开发，应用程序不依赖php-fpm、apache、nginx这些容器就可以独立运行。 所以PHP开发者开发、部署、调试应用程序非常方便。

* 支持PHP多进程

>为了充分发挥服务器多CPU的性能，支持多进程多任务。采用一个主进程和多个子进程对外提供服务的方式， 主进程负责监控子进程退出信号，并负责生成新的子进程去处理服务，这样做不仅提高了应用程序的性能，而且使得系统更加稳定。
	
* 支持TCP、UDP

>支持TCP和UDP两种传输层协议，只需要更改配置的一个字段，便可以更换传输层协议，业务代码无需任何改动。
	
	
* 支持各种应用层协议
	
>接口上支持各种应用层协议，包括自定义协议。

> 默认集成的协议:
>
> | 协议名       | 协议方式 | 描述 |
> | ------------- | -----| -----|
> | text | \n 分割报文 | 注意内容体不能出现\n |
> | chunk | [4字节报文长度]内容 | 支持二进制数据|
> | chunk.gz | [4字节报文长度]gzcompress(内容) | 处理大数据传输的应用|
> | text.serialize | serialize(内容)\n  | 收到数据自动完成 unserialize |
> | text.json | json_encode(内容)\n | 收到数据自动完成json\_decode |
> | chunk.serialize | [4字节报文长度]serialize(内容) | chunk中叠加serialize |
> | chunk.json | [4字节报文长度]json_encode(内容) | chunk中叠加json |
> | chunk.gz.serialize | [4字节报文长度] gzcompress(serialize(内容)) |  chunk中叠加serialize和gzcompress|
> | chunk.gz.json | [4字节报文长度]gzcompress(json(内容)) | chunk中叠加json和gzcompress |
	
>开发者可以很方便的开发出自己的协议，为了方便使用，我们提供了websocket(ws)，http协议的实现。实现自己的协议后，只需要注册到`tii.worker.protocols`就可以使用这个协议了。。。
	
* 支持长连接

>很多时候需要PHP应用程序要与客户端保持长连接，比如聊天室、游戏等，但是传统的PHP容器（apache、nginx、php-fpm）很难做到这一点。 使用该部分可以轻松使用PHP长连接。php单个进程可以支持几千甚至几万的并发连接，多进程则可支持数十万甚至上百万的并发连接。
	
* 高性能

>由于php文件从磁盘读取解析一次后便会常驻内存，下次使用时直接使用内存中的opcode， 极大的减少了磁盘IO及PHP中请求初始化、创建执行环境、词法解析、语法解析、编译opcode、请求关闭等诸多耗时过程， 并且不依赖nginx、apache等容器，少了nginx等容器与PHP通信的网络开销，最主要的是资源可以永久保持，不必每次初始化数据库连接等等， 所以使用该部分开发应用程序，性能非常高。

* 支持对象或者资源永久保持

>在一个进程生命周期内静态成员或者全局变量在不主动销毁的情况下是永久保持的，也就是只要初始化一次静态成员或者全局变量在当前进程的整个生命周期内的所有请求都可以复用这个 静态成员或者全局变量。例如只要单个进程内初始化一次数据库连接，则以后这个进程的所有请求都可以复用这个数据库连接，不用每个用户请求都去重连数据库，避免了频繁连接数据库过程中TCP三次握手、 数据库权限验证、断开连接时TCP四次握手的过程，极大的提高了应用程序效率。memcache、redis等初始化也是同样的道理。
	
* 支持高并发

>支持Libevent事件轮询库（需要安装Libevent扩展）， 使用Libevent在高并发时性能非常卓越，如果没有安装Libevent则使用PHP内置的Select相关系统调用。

* 支持异步IO

>自带的网络IO接口是异步的，开发者可实现基于事件的异步编程
	
* 支持HHVM

>支持HHVM，对于php性能有大幅度（一般为50%左右甚至更高）的提升，尤其是在cpu密集运算中。实际经过压力测试确实有明显性能提升效果。
	
* 支持毫秒级别定时器

>支持毫秒级别定时器，可以做定时任务或者定时计算，如游戏中地图上AI相关计算。
	
* 支持分布式部署

>支持分布式部署，可以平滑的动态添加减少服务器而不影响服务质量。从而使得系统能够支持相当大的吞吐量或者并发TCP连接。

* 支持以指定用户运行子进程

>因为子进程是实际处理用户请求的进程，为了安全考虑，子进程不能有太高的权限，支持设置子运行进程运行的用户。

* 自带监控

>内部带有监控统计模块，能够统计程序自身的一些数据，如进程退出次数及退出状态，每个进程占用内存大小及监听的ip端口、每个进程启动时间、 进程运行的服务名、每个进程处理请求数、连接数、数据包发送失败量等等。这些信息可以本地运行php start.php status本地查看。

* 支持心跳检测

>支持服务端的心跳检测，可以定时向客户端发送应用层的心跳，能够及时检测到客户端极端掉线情况（掉电、突发网络故障等）。

* 诸多应用

>拥有诸多的应用，如Thrift-Rpc、Json-Rpc、 聊天室、统计监控服务以及本站Web程序等。
	
### 原理

#### 为什么拥有卓越的性能

请求执行周期
![](images/worker.jpeg)

* 避免读取磁盘和反复编译

>运行过程中，单个进程生命周期内只要PHP文件被载入编译过一次，便会常驻内存，不会再去从磁盘读取或者再去编译。省去了重复的请求初始化、创建执行环境、词法解析、语法解析、编译生成opcode以及请求关闭等诸多耗时的步骤。 实际上运行起来后便几乎没有磁盘IO及PHP文件编译开销，剩下的只是简单的计算过程，这使得运行速度飞快。

* 数据或者资源可以全局共享

>多个请求是可以共享数据或者资源的，当前请求产生的全局变量或者类的静态成员在下一次请求中仍然有效。 这对于减少开销，提高运行效率非常有用。例如业务只要初始化一次数据库连接，那么全局都可以共享这个连接，这实现了真正意义上的数据库长连接。 从而不必每次用户请求都去创建新的连接，避免了连接时三次握手、连接后权限验证以及断开连接时四次挥手等耗时的交互过程。不仅数据库，像redis、 memcache等同样有效。少了这些开销和网络交互，使得运行速度更快。

* 没有多余的网络开销

>传统PHP应用程序需要借助apache、nginx等容器才能对外提供网络服务，这就导致多了一层apache、nginx等容器到PHP进程的数据传输开销。 并且由于运行了apache或者nginx容器，这将进一步消耗服务器的资源。现在却没有这部分开销，这是由于其自身便是一个服务器容器具有PHP进程管理以及网络通讯功能， 完全不依赖于apache、nginx、php-fpm等这些容器便可以独立运行，所以性能更高。

进程模型：主进程与子进程关系
![](images/worker2.jpeg)

* 进程模型简单

>使用多进程（也有多线程版本）的，可以充分利用服务器多核资源。采用简单的进程模型，主进程只负责监控子进程，而每个子进程独自接受维护客户端的连接，独自读取连接上发来的数据，独自处理。 子进程间默认没有任何数据通讯，主进程和子进程之间只有一次信号通讯。简单的进程通讯模型使得相比其它复杂的进程模型的软件更高效。

客户端与进程的关系
![](images/worker1.jpeg)

* 客户端和服务端实时通讯

>从图上我们可以看出每个子进程维持着各自的客户端连接，能够方便的实现客户端与服务端的实时通讯，基于这种模型我们可以方便实现一些基本的开发需求，例如HTTP服务器、Rpc服务器、一些智能硬件实时上报数据、服务端推送数据、游戏服务器等等。

### 定时器
### 事件
### 协议
### TCP异步
### 客户端类
更多扩展类
----
### Tattoo
Tattoo扩展类，主要是对Tii的补充，将Tii并不常用的类和方法剥离出来，让Tii可以很方便的打成一个文件

FAQ：常见问题
----
```php
<?php define('CONFIGURATION_DIRECTORY', dirname(__FILE__));
require_once CONFIGURATION_DIRECTORY . '/../../../build/Tii-0.4.8.1445.Packer.php';// 加载 tii 库 //require_once CONFIGURATION_DIRECTORY . '/../../../library/Bootstrap.php';// 加载 tii 库
/* 添加一些系统路径 */ Tii_Config::set(); 详细见 library .configs 目录中的tii.config.php
Tii_Config::setDir(TII_DIRECTORY . '/.configs'); //默认配置文件存放目录为框架目录的.config目录，如果么有正常设置，不能正常运行框架。
唯一定义了一个常量：TII_DIRECTORY 框架的路径
Tii_Event::filter('tii.application.processor.http.getView'
require_once Tii_Config::get('library_dir') . '/Bootstrap.php'; //加载Blaze引导文件
Tii_Application_Session::start();//开启session
Tii_Controller_Front::run(Tii_Config::get('application_dir'));
lang-*.config.php为了多语言，请不要使用。
推荐方法，可以满足大部分的加载方式，如：本地配置不同 <?php
$_directories = array(
    dirname(__FILE__) . '/../configs/local/configuration.php',// local first
    dirname(__FILE__) . '/../configs/configuration.php',
);
foreach ($_directories as $_file) {
    if (is_file($_file)) {
        require_once $_file;
        break;
    }
} unset($_directories, $_file);
if (!class_exists('Tii_Version')) {//check framework has already loaded
    trigger_error("The tii framework not loaded correctly", E_USER_ERROR);
}
//session start if (Tii_Config::get('tii.application.session.start', false)) {
    Tii_Application_Session::start(Tii_Config::get('tii.application.session.handler', null));
}
Tii_Application::run();
基础 *************
可以利用 Tii::object方法来创建单例，call的参数可以参考 call_user_func 参数，第一个是class，其他为传入__construct的参数； 为了便于简化调用单例模式的对象，可以都放在 Blaze_Factory 中，比如：
	/**
	 * @return Tii_Cache
	 */
	public function getCache()
	{
		return Tii::object('Tii_Cache');
	}
如：Blaze_Factory::getInstance()->getCache()来调用
__config_dir__
tii.debug_mode tii.timezone tii.library.include tii.library.* path/to/some/class lang-*[default].*
tii.auth_code_key md5(__FILE__)
tii.application.directory tii.application.rewrite.http array() preg_replace tii.application.rewrite.cli //Is not implemented
	const CONFIG_MODULE = 'tii.application.module';
	const CONFIG_CONTROLLER = 'tii.application.controller';
	const CONFIG_ACTION = 'tii.application.action';
		$path = Tii_Config::get('tii.controller.cookie.path', '/');
		$domain = Tii_Config::get('tii.controller.cookie.domain', null);
		$secure = Tii_Config::get('tii.controller.cookie.secure', false);
		$httponly = Tii_Config::get('tii.controller.cookie.httponly', false);
tii.logger.priority tii.logger.handler
tii.cache.chain array('memcache', 'apc', 'file') tii.cache.memcache' tii.cache.file', Tii_Config::get('tii.temp_dir', sys_get_temp_dir())
tii.database.*[default] array(//default config
		'dsn' => array(//@link http://php.net/manual/en/pdo.drivers.php
			'host' => 'localhost',
			'port' => 3306,
			'dbname'=> 'test',
		),
		'charset' => 'UTF8',
		'username' => 'root',
		'passwd' => '',
	)
Tii_Application_Processor_Abstract get(.*)Helper/
创建session：
可以使用下面2种方式： Tii_Application_Session::start();
or
Tii_Application_Session::start('Tii_Application_SessionHandler_Cache');
Logger：
继承 Tii_Logger_Abstract 实现 Blaze_Logger
DAO：
Tii_Dao
默认是tii.config.php中的 database 的值 将继承创建对象，更改成配置生成模式，如果需要单例，可以用 Tii::object 的方法调用。
Cache： 默认是tii.config.php中的 cache 的值 将继承创建对象，更改成配置生成模式，如果需要单例，可以用 Tii::object 的方法调用。
Tii_Cache
配置：
array(
    memcache => array(
       'prefix' => ?,
       'servers' => array(
            'servername' => array('host' => ?, 'port' => ?)
       ),
    ),
    apc => array(
       'prefix' => ?
    )
    file => array(
       'prefix' => ?,
       'directory' => ?,
    )
    'locker' => ?,
)
	'cache' => array(
		'memcache' => array(
			'prefix' => 'blaze',
			'servers' => array(
				'server1' => array(
					'host' => 'localhost',
					'port' => 11211,
				),
			),
		),
		'apc' => array(
			'prefix' => 'blaze',
		),
		'file' => array(
			'prefix' => 'blaze',
			//'directory' => Tii_Config::get(_temp_dir_),
		),
		//'locker' => Tii_Config::get(_temp_dir_),
	),
如果需要使用过滤器： 继承 Tii_Controller_Filter_Abstract 实现 Blaze_Controller_Filter_xxx
需要创建助手：
继承 Tii_Controller_Helper_Abstract 实现 Blaze_Controller_Helper_Something
则在view中调用
$this->getController()->getBlazeHelper()->something()->method(); Tii中的基础助手csrf则可以： $this->getController()->getHelper()->csrf()->getName(); $this->getController()->getHelper()->csrf()->getValue(); $this->getController()->getHelper()->csrf()->getInput();
view的安全输出： $this->output($now);//html的防xss $this->output($var, 'javascript');//javascript的防xss
========================
如果需要定制 Router 继承 Tii_Controller_Router_Abstruct
扩展 *************
当设置好： Tii_Config::set('__extend_library_dir', dirname(__FILE__) . '/../../../extras/extends'); // tii extend library
会自动在__extend_library_dir目录加载ETii_*的类
验证码： 继承 ETii_Captcha_Abstract
/application/$pro/filters/beforeFilter.php 变量 Task_beforeFilter
	/**
	 * @return Tii_Application_Controller_Abstract
	 */
	public function getController()
//action当中缓存出渲染的结果，只是在渲染的部分缓存，并不是整个过程缓存。 $this->setRenderCache(时间，标识字符串);
        <?php
        echo $this->getController()->getCaipuHelper()->baiduPager(array(
            'num' => $search['result']['viewtotal'],//需要分页的总条数
            'perpage' => $perpage,//每页页数
            'curpage' => $page,//当前页码
            'mpurl' => '/list/' . $keyword . '-',//页面url前半部分
            'ext' => '.html',//页面url后半部分
            'page' => 10,//每版面显示几个页码
        ))->render();
        ?>



	function decst(n) {
		var s = '';
		while (n != 0) {
			var t = n % 62;
			if ((t >= 10) && (t < 36)) {
				s += String.fromCharCode(t + 55);
			} else if ((t >= 36) && (t < 62)) {
				s += String.fromCharCode(t + 61);
			} else {
				s += t;
			}
			n = parseInt(n / 62);
		}
		return s.split().reverse().join();
	}

	function stdec(s) {
		var n = 0;
		for (var i = 0; i <= s.length - 1; i++) {
			var t = s.substring(i, i + 1);
			var o = s.charCodeAt(i);
			if (o <= 57) {
				n += t * Math.pow(62, s.length - i - 1);
			} else if ((o >= 65) && (o <= 90)) {
				n += (o - 55) * Math.pow(62, s.length - i - 1);
			} else {
				n += (o - 61) * Math.pow(62, s.length - i - 1);
			}
		}
		return n;
	}
```
关于本手册
----
附录
----
### tii.config.php
```
EVENT: tii.application.event |- router |- controller |- action |- view |- response
CONFIG: tii.debug_mode => true, //开启debug模式 tii.logger_priority => Tii_Logger_Constant::ALL, //日志的等级 tii.temp_dir => sys_get_temp_dir(), //临时文件路径 **
tii.application |- directory => dirname(__DIR__) . '/application', |- module => default |- controller => index |- action => index |- parameter.xxxxx |- context //入口？
|- cookie
	|- path
	|- domain
	|- secure
	|- httponly
|- modules
	|- default =>
		|- directory => tii.application.directory + default
tii.controller.runtime |- router |- dispatcher |- action |- view |- response
tii.database => array(
	'dsn' => array(
	'host' => 'localhost',
	'port' => 3306,
	'dbname' => 'dbname',
	),
	'username' => 'root',
	'passwd' => 'kernel',
)
tii.cache => array(
	'chain' => array('memcache', 'apc', 'file'),//可以用Tii_Cache->setChain()设置
	'memcache' => array(
		'server1' => array( string host [, int port = 11211 [, bool persistent [, int weight [, int timeout [, int retry_interval [, bool status [, callback failure_callback [, int timeoutms ]]]]]]]] )
		'server2' => ...
	)
	'file' => array(
		directory 路径，string
		gc_probality
	)
)
```

更新日志
----

### Version 3.16 (2016-12-06)

* 框架命名空间从Desire换成Tii
* 修复了已知bug
* 完善了文档和例子

----

### Version 2.16.917（2.16.0223.1627） (2016-09-17)

* 优化Worker网络socket模块，支持http，websocket
* 修复已知bug，规范命名和注释

### Version 2.16.811 (2016-08-11)

* 日志增加DAO方式
* 参考workerman，新增Worker网络socket模块，支持text，chunk
* 增加mock解析器
* 助手类增加template（模板）
* 优化配置加载机制，实现基于setter和render模式
* 优化页面缓存机制，扩充事件点

### Version 2.16.223 (2016-02-23)

* 修复已知bug，确定为相对稳定版本

### Version 2.15.416 (2015-04-16)

* 全面优化加载机制，增加Tattoo附属框架，用于迁移Tii非常用功能
* 优化打包，打包时进行混淆，并进一步缩小文件大小
* 将应用助手，优化为全局助手类
* 优化委托代理类，增加反注释配置功能，实现缓存配置化
* 加入信息对称算法，同时支持java、php、js版本
* 加入定时器机制
* 优化crontab表达式计算
* 增加页面缓存机制

### Version 1.14.917 (2014-09-17)

* 结构调整，支持单文件打包
* DAO加入帮助类
* 会话加入DAO模式
* 升级钩子（Hook）为事件（event）
* 增加应用模块，方便MVC模式开发
* 应用助手类：ajax、html、CSRF，pager（页码）
* 基于配置，多语言I18N支持
* 时间类加入crontab表达式计算器

### Version 0.13.327 (2013-03-27)

* 委托模式引入
* 会话封装，加入缓存模式
* 安全过滤机制加入
* 钩子模式（Hook）
* 本地文件处理封装
* 配置文件机制

### Version 0.8.1130 (2008-11-30)

* 多模缓存
* 单例文件日志
* HTTP请求封装
* 时间函数封装

### Version 0.6.922 (2006-09-22)

* 引入PropelPDO替换mysqli数据库操作类
* 单例模式

### Version 0.5.1216 (2005-12-16)

* 数据库操作
* 实用性函数

鸣谢
----
贡献者名单（根据国际惯例，排名不分先后）

@alacner @andy @future5985 @kimmking @simon


[Tii PHP框架]: #tii-php-framework
[序言]: #preface
[经典案例]: #projects
[例子]: #examples
[版权申明]: #copyright
[文档手册]: #manual
[入门指引]: #getting-started
[特性]: #features
[总体结构和模块接口设计]: #architecture
[加载]: #loader
[加载器]: #bootstrap
[应用程序]: #application
[控制器]: #controller
[助手类]: #helper
[逻辑处理器]: #process
[路由]: #route
[会话]: #session
[缓存]: #cache
[配置]: #config
[变量包装器]: #config-wrapper
[数据库]: #dao
[委托]: #delegate
[注释解析器]: #doc-comment-parser
[事件]: #event
[异常处理]: #exception
[文件系统]: #filesystem
[HTTP请求]: #http
[日志]: #logger
[数学]: #math
[网络]: #network
[打包器]: #packer
[安全]: #security
[服务类]: #service
[时间]: #time
[计时器]: #timer
[当前版本]: #version
[网络编程]: #socket
[定时器]: #socket-timer
[事件]: #socket-event
[协议]: #socket-protocol
[TCP异步]: #socket-tcp
[客户端类]: #socket-client
[更多扩展类]: #extension-class
[Tattoo]: #tattoo
[FAQ：常见问题]: #faq
[关于本手册]: #about
[附录]: #appendix
[tii.config.php]: #tii-config-php
[更新日志]: #change-log
[鸣谢]: #thanks
