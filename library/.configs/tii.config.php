<?php
/**
 * Tii Configure
 *
 * Copyright (c) 2005 - 2017, Fitz Zhang <alacner@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: tii.config.php 8915 2017-11-05 03:38:45Z alacner $
 */

return [
    'debug_mode' => true, //debug mode
    'timezone' => 'UTC',//Asia/Chongqing Asia/Shanghai Asia/Urumqi Asia/Hong_Kong Etc/GMT-8 Singapore Hongkong PRC
    'logger' => [
        'handler' => ['Tii_Logger_File', '/path/to/save/logger/file'],//The default configuration using `tii.temp_dir'
        'priority' => Tii_Logger_Constant::ALL,
    ],
    'temp_dir' => sys_get_temp_dir(), //Note: the cli and HTTP mode maybe inconsistent.
    'data_dir' => '/tii/data',//path to save permanent data
    'library' => [
        //'include' => [],
        //'Tattoo' => '/path/to/tattoo/class',
        //'SomeOther' => '/path/to/some/other/class',
        //'*' => '/root/path/to/some/other/class',
    ],

    'auth_code_key' => Tii_Config::getIdentifier(),//for Tii_Security_Encryption
    'http_mime_type_mapper' => [//for Tii_Http::getMimeType
        'application' => [
            'envoy' => ['evy'],
            'fractals' => ['fif'],
            'futuresplash' => ['spl'],
            'hta' => ['hta'],
            'internet-property-stream' => ['acx'],
            'json' => ['json'],
            'java-archive' => ['ear', 'jar', 'war'],
            'mac-binhex40' => ['hqx'],
            'msword' => ['doc', 'dot', 'docx'],
            'octet-stream' => ['*', 'bin', 'class', 'dms', 'exe', 'lha', 'lzh', 'msm', 'msp', 'msi', 'img', 'deb', 'dmg', 'eot', 'iso'],
            'oda' => ['oda'],
            'atom+xml' => ['atom'],
            'olescript' => ['axs'],
            'pdf' => ['pdf'],
            'pics-rules' => ['prf'],
            'pkcs10' => ['p10'],
            'pkix-crl' => ['crl'],
            'postscript' => ['ai', 'eps', 'ps'],
            'rtf' => ['rtf'],
            'rss+xml' => ['rss'],
            'set-payment-initiation' => ['setpay'],
            'set-registration-initiation' => ['setreg'],
            'vnd.ms-excel' => ['xla', 'xlc', 'xlm', 'xls', 'xlt', 'xlw'],
            'vnd.ms-outlook' => ['msg'],
            'vnd.ms-pkicertstore' => ['sst'],
            'vnd.ms-pkiseccat' => ['cat'],
            'vnd.ms-pkistl' => ['stl'],
            'vnd.ms-powerpoint' => ['pot', 'pps', 'ppt'],
            'vnd.ms-project' => ['mpp'],
            'vnd.ms-works' => ['wcm', 'wdb', 'wks', 'wps'],
            'vnd.wap.wmlc' => ['wmlc'],
            'vnd.google-earth.kml+xml' => ['kml'],
            'vnd.google-earth.kmz' => ['kmz'],
            'winhlp' => ['hlp'],
            'x-7z-compressed' => ['7z'],
            'x-bcpio' => ['bcpio'],
            'x-netcdf' => ['cdf', 'nc'],
            'x-cocoa' => ['cco'],
            'x-compress' => ['z'],
            'x-compressed' => ['tgz'],
            'x-cpio' => ['cpio'],
            'x-csh' => ['csh'],
            'x-director' => ['dcr', 'dir', 'dxr'],
            'x-dvi' => ['dvi'],
            'x-gtar' => ['gtar'],
            'x-gzip' => ['gz'],
            'x-hdf' => ['hdf'],
            'x-internet-signup' => ['ins', 'isp'],
            'x-iphone' => ['iii'],
            'x-javascript' => ['js'],
            'x-java-archive-diff' => ['jardiff'],
            'x-java-jnlp-file' => ['jnlp'],
            'x-latex' => ['latex'],
            'x-makeself' => ['run'],
            'x-msaccess' => ['mdb'],
            'x-mscardfile' => ['crd'],
            'x-msclip' => ['clp'],
            'x-msdownload' => ['dll'],
            'x-msmediaview' => ['m13', 'm14', 'mvb'],
            'x-msmetafile' => ['wmf'],
            'x-msmoney' => ['mny'],
            'x-mspublisher' => ['pub'],
            'x-msschedule' => ['scd'],
            'x-msterminal' => ['trm'],
            'x-mswrite' => ['wri'],
            'x-perfmon' => ['pma', 'pmc', 'pml', 'pmr', 'pmw'],
            'x-pkcs12' => ['p12', 'pfx'],
            'x-pkcs7-certificates' => ['p7b', 'spc'],
            'x-pkcs7-certreqresp' => ['p7r'],
            'x-pkcs7-mime' => ['p7c', 'p7m'],
            'x-pkcs7-signature' => ['p7s'],
            'x-perl' => ['pl', 'pm'],
            'x-pilot' => ['prc', 'pdb'],
            'x-redhat-package-manager' => ['rpm'],
            'x-rar-compressed' => ['rar'],
            'x-sea' => ['sea'],
            'x-sh' => ['sh'],
            'x-shar' => ['shar'],
            'x-shockwave-flash' => ['swf'],
            'x-stuffit' => ['sit'],
            'x-sv4cpio' => ['sv4cpio'],
            'x-sv4crc' => ['sv4crc'],
            'x-tar' => ['tar'],
            'x-tcl' => ['tcl', 'tk'],
            'x-tex' => ['tex'],
            'x-texinfo' => ['texi', 'texinfo'],
            'x-troff' => ['roff', 't', 'tr'],
            'x-troff-man' => ['man'],
            'x-troff-me' => ['me'],
            'x-troff-ms' => ['ms'],
            'x-ustar' => ['ustar'],
            'x-wais-source' => ['src'],
            'x-x509-ca-cert' => ['cer', 'crt', 'der', 'pem'],
            'x-xpinstall' => ['xpi'],
            'xhtml+xml' => ['xhtml'],
            'ynd.ms-pkipko' => ['pko'],
            'zip' => ['zip'],
        ],
        'audio' => [
            '3gpp' => ['3gp'],
            'basic' => ['au', 'snd'],
            'midi' => ['mid', 'rmi', 'midi', 'kar'],//also: mid,x-mid,x-midi
            'mpeg' => ['mp3'],
            'ogg' => ['ogg'],
            'x-aiff' => ['aif', 'aifc', 'aiff'],
            'x-mpegurl' => ['m3u'],
            'x-pn-realaudio' => ['ra', 'ram'],//also: x-realaudio
            'x-wav' => ['wav'],
        ],
        'image' => [
            'bmp' => ['bmp'],//also: x-ms-bmp
            'cis-cod' => ['cod'],
            'gif' => ['gif'],
            'ief' => ['ief'],
            'jpeg' => ['jpe', 'jpeg', 'jpg'],
            'pipeg' => ['jfif'],
            'png' => ['png'],
            'svg+xml' => ['svg'],
            'tiff' => ['tif', 'tiff'],
            'x-cmu-raster' => ['ras'],
            'x-cmx' => ['cmx'],
            'x-icon' => ['ico'],
            'x-jng' => ['jng'],
            'x-portable-anymap' => ['pnm'],
            'x-portable-bitmap' => ['pbm'],
            'x-portable-graymap' => ['pgm'],
            'x-portable-pixmap' => ['ppm'],
            'x-rgb' => ['rgb'],
            'x-xbitmap' => ['xbm'],
            'x-xpixmap' => ['xpm'],
            'x-xwindowdump' => ['xwd'],
            'vnd.wap.wbmp' => ['wbmp'],
        ],
        'message' => [
            'rfc822' => ['mht', 'mhtml', 'nws'],
        ],
        'text' => [
            'css' => ['css'],
            'h323' => ['323'],
            'html' => ['htm', 'html', 'stm', 'shtml'],
            'iuls' => ['uls'],
            'plain' => ['bas', 'c', 'h', 'txt', 'serialize'],
            'richtext' => ['rtx'],
            'scriptlet' => ['sct'],
            'tab-separated-values' => ['tsv'],
            'webviewhtml' => ['htt'],
            'x-component' => ['htc'],
            'x-setext' => ['etx'],
            'x-vcard' => ['vcf'],
            'xml' => ['xml'],
            'mathml' => ['mml'],
            'vnd.sun.j2me.app-descriptor' => ['jad'],
            'vnd.wap.wml' => ['wml'],
        ],
        'video' => [
            'mpeg' => ['mp2', 'mpa', 'mpe', 'mpeg', 'mpg', 'mpv2'],
            'quicktime' => ['mov', 'qt'],
            'x-la-asf' => ['lsf', 'lsx'],
            'x-ms-asf' => ['asf', 'asr', 'asx'],
            'x-msvideo' => ['avi'],
            'x-sgi-movie' => ['movie'],
            'x-ms-wmv' => ['wmv'],
            'x-mng' => ['mng'],
            'x-flv' => ['flv'],
        ],
        'x-world' => [
            'x-vrml' => ['flr', 'vrml', 'wrl', 'wrz', 'xaf', 'xof'],
        ],
    ],
    'application' => [
        //'instance' => NULL, //instance
        'session' => [
            'start' => false, //session start?
            'handler' => NULL,//change handler?
        ],
        'directory' => '/path/to/application',//all in one, ${module}/[controllers|views|hooks|library]/*
        //'directory' => [//${module}/*
        //    'controllers' => '/path/to/controllers',
        //    'views' => '/path/to/views',
        //    'hooks' => '/path/to/hooks',
        //    'library' => '/path/to/library',
        //],
        //
        'module' => 'default',//default module name
        'controller' => 'index',//default controller name
        'action' => 'index',//default action name
        //
        'cookie' => [//cookie
            'path' => '/',
            'domain' => NULL,
            'secure' => false,
            'httponly' => false,
        ],
        'server' => [
            'access' => [//@see
                'enable' => false,
                'rules' => [
                    //'127.0.0.1/8' => true,//allow
                    //'0.0.0.0/0' => false,//deny
                ],
                'message' => 'Access to this resource on the server is denied!',
                'message_html' => <<<eot
<html>
<head>
<title>Service Unavailable</title>
</head>
<body bgcolor="#FFFFFF">
<table cellpadding="0" cellspacing="0" border="0" width="700" align="center" height="85%">
  <tr align="center" valign="middle">
    <td>
    <table cellpadding="10" cellspacing="0" border="0" width="80%" align="center" style="font-family: Verdana, Tahoma; color: #666666; font-size: 11px">
    <tr>
      <td valign="middle" align="center" bgcolor="#EBEBEB">
        <br /><b style="font-size: 16px">403 Forbidden</b>
        <br /><br />Access to this resource on the server is denied!
        <br /><br />
      </td>
    </tr>
    </table>
    </td>
  </tr>
</table>
</body>
</html>
eot
            ],
            'busy_error' => [//Only be used on Unix/Linux host
                'loadctrl' => 0,//5 ~ 10, 0 for no limit.
                'message' => "The server can't process your request due to a high load, please try again later.",
                'message_html' => <<<eot
<html>
<head>
<title>Service Unavailable</title>
</head>
<body bgcolor="#FFFFFF">
<table cellpadding="0" cellspacing="0" border="0" width="700" align="center" height="85%">
  <tr align="center" valign="middle">
    <td>
    <table cellpadding="10" cellspacing="0" border="0" width="80%" align="center" style="font-family: Verdana, Tahoma; color: #666666; font-size: 11px">
    <tr>
      <td valign="middle" align="center" bgcolor="#EBEBEB">
        <br /><b style="font-size: 16px">Service Unavailable</b>
        <br /><br />The server can't process your request due to a high load, please try again later.
        <br /><br />
      </td>
    </tr>
    </table>
    </td>
  </tr>
</table>
</body>
</html>
eot
            ]
        ],
        'rewrite' => [//rewrite input to other
            'pseudo' => [//pseudo rewrite ...
                'http' => [//path  => render
                    //"/path/to/page.html" => "/path/to/page-{foo}.html",///path/to/page.html?foo=bar => /path/to/page-bar.html
                ],
                'cli' => [],
            ],
            'http' => [//preg_replace, [pattern => replacement,...]
                //'*' => function($uri){return $uri;},//callable
                //'|^/$|' => '/path',
                //'|^/old_path/|' => '/new/path/to/',
            ],
            'cli' => [],
        ],
        'filters' => [
            //'*' => '/path/to/filters',
            //'tii.application.processor' => function($processor) {return $processor;},
            /** The following part @see Tii_Event::action */
            //'tii.error.handler' => function($errno , $errstr, $errfile, $errline, $errcontext /** @see set_error_handler */) {},
            //'tii.exception.handler' => function($exception){},
            //'tii.shutdown.handler' => function() {},
        ],
        'helper' => [
            'html' => [
                'base_url' => '',//base url for css or script
            ],
            'csrf' => [
                'name' => '__csrf_token__',//default is __csrf_token__
            ],
            'template' => [
                'filters' => [
                    '|<!--{if (.+)}-->|U' => '<?php if (\1): ?>',
                    '|<!--{else}-->|U' => '<?php ; else: ?>',
                    '|<!--{elseif (.+)}-->|U' => '<?php ; elseif (\1): ?>',
                    '|<!--{/if}-->|U' => '<?php endif; ?>',
                    '|<!--{for (.+)}-->|U' => '<?php for (\1): ?>',
                    '|<!--{/for}-->|U' => '<?php endfor; ?>',
                    '|<!--{foreach (.+)}-->|U' => '<?php foreach (\1): ?>',
                    '|<!--{/foreach}-->|U' => '<?php endforeach; ?>',
                    '|<!--{while (.+)}-->|U' => '<?php while (\1): ?>',
                    '|<!--{/while}-->|U' => '<?php endwhile; ?>',
                    '|<!--{continue}-->|U' => '<?php continue; ?>',
                    '|<!--{break}-->|U' => '<?php break; ?>',
                    '|<!--{$(.+)=(.+)}-->|U' => '<?php $\1 = \2; ?>',
                    '|<!--{$(.+)++}-->|U' => '<?php $\1++; ?>',
                    '|<!--{$(.+)--}-->|U' => '<?php $\1--; ?>',
                    '|<!--{$(.+)}-->|U' => '<?php echo $\1; ?>',
                    '|<!--{/*}-->|U' => '<?php /*',
                    '|<!--{*/}-->|U' => '*/ ?>',
                    '|<!--{(.+)}-->|Us' => '<?php \1; ?>',
                ],
            ],
        ],
    ],
    //validator
    'validators' => [//Also inject rules use filter with name 'tii.validators'
        //'rule' => function($arr, $k, $arg1,...) { return true|false; },
    ],
    //databases
    'database' => [
        'default' => [//default config
            'dsn' => [
                'host' => 'localhost',
                'port' => 3306,
                'dbname' => 'dbname',
            ],
            'charset' => 'UTF8',//default is UTF8
            'username' => 'root',
            'passwd' => 'kernel',
        ],
        //'other' => [],
    ],
    //cache
    'cache' => [
        'chain' => ['memcache', 'apc', 'file'],//use Tii_Cache->setChain() to set chain
        'memcache' => [
            'server1' => ['localhost'],
        ],
        'file' => [
            'directory' => sys_get_temp_dir(),//The default configuration using `tii.temp_dir'
            'gc_probality' => 1,//The GC PPM * execution probability
        ],
    ],
];
