## Session 机制

Http 是一种无状态的协议，即说明每次请求都与之前之后的请求无关。为了解决这个问题，引入了 cookie。在客户端上可以存储少量的信息。但是，cookie 有大小的限制，而且各浏览器的实现方式也不一致。 所以为了解决  cookie 的问题，引入 Session 会话机制来解决这个问题。为第个客户分配一个会话 ID (SID)。

在 php 中会话的实现有下面两种方式：

1. 基于 cookie 的实现。客户端与服务器通信通过 cookie 携带的 sid 信息来完成会话。
2. 基于 URL 实现。客户端与服务器通信时的 url 上都附代上 sid 信息，这们的 URL 也称为胖 URL (Fat URL)。

一般的实现方式都是基于 cookie 来实现。



### 1. php Session 相关配置

客户端与服务器之前通过 sid 来标识会话，服务器需要根据 sid 来查找这个会话 id 所存储的信息。sid 所标识的会话信息应该存在那里那，我们可以指定的方式有：文件，共享内存，DB(eg. redis, mysql, sqlite)，或者用户自定义函数。

#### 1.1 使用 files 存储 session 

查看当前 php 的配置。可以看出 session.save_handler 设置的为 files 类型，但并没有为 session 指定 session.save_path。

```
$ php -i | grep session.save
session.save_handler => files => files
session.save_path => no value => no value
```

##### demo

- 服务器代码。

```php
<?php
  ini_set('session.save_handler', 'files');
  ini_set('session.save_path', '/tmp');
  session_start(); //  启动新会话或者重用现有会话。
  $sid = session_id();
  // 如果请求附带的 cookie 的 session id 的信息，那么就会重现会话。否则就启动新的会话。
  if (isset($_SESSION['username'])) {
      echo "Hi, {$_SESSION['username']}, Welcome back to the session. Your session id is $sid \n";
  } else {
      $_SESSION['username'] = 'eason';
      echo "Hi, {$_SESSION['username']}, We create a new session for you. Your session id is $sid. \n You can append it your http request cookie. \n";
  }
?php>
```

- apache 服务器配置。

```
<VirtualHost *:80>
	DocumentRoot /php-tutorial/session
	ServerName session.files.com
	<Directory "/">
		DirectoryIndex 00-session-files.php
		Require all granted
	</Directory>
</VirtualHost>
```

- 测试代码

```
/etc/apache2 ⌚ 23:28:37
$ curl -i http://session.files.com # 第一次请求
HTTP/1.1 200 OK
Date: Mon, 01 May 2017 15:29:07 GMT
Server: Apache/2.4.25 (Unix) PHP/5.6.30
X-Powered-By: PHP/5.6.30
Set-Cookie: PHPSESSID=2e5db94d360e5df907425ff2d2fb379e; path=/
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Content-Length: 144
Content-Type: text/html; charset=UTF-8

Hi, eason, We create a new session for you. Your session id is 2e5db94d360e5df907425ff2d2fb379e.
 You can append it your http request cookie.

/etc/apache2 ⌚ 23:29:07
$ curl -i http://session.files.com # 第二次请求，还没有带上 cookie
HTTP/1.1 200 OK
Date: Mon, 01 May 2017 15:29:14 GMT
Server: Apache/2.4.25 (Unix) PHP/5.6.30
X-Powered-By: PHP/5.6.30
Set-Cookie: PHPSESSID=d0a339984606fa2721ae131f012918ec; path=/
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Content-Length: 144
Content-Type: text/html; charset=UTF-8

Hi, eason, We create a new session for you. Your session id is d0a339984606fa2721ae131f012918ec.
 You can append it your http request cookie.
```

可以看出通过简单的 `$ curl -i http://session.files.com` ， 每次都会为我们的请求生成一个新的 PHPSESSID。如果我们把这个 PHPSESSID 在 cookie 上附带传给服务器，那么服务器就不会为我们开启新的会话了，而是使用之前的会话。如`$ curl -i http://session.files.com --cookie "PHPSESSID=d0a339984606fa2721ae131f012918ec;"`：

```
/etc/apache2 ⌚ 23:29:14
$ curl -i http://session.files.com --cookie "PHPSESSID=d0a339984606fa2721ae131f012918ec;"
HTTP/1.1 200 OK
Date: Mon, 01 May 2017 15:32:58 GMT
Server: Apache/2.4.25 (Unix) PHP/5.6.30
X-Powered-By: PHP/5.6.30
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Content-Length: 93
Content-Type: text/html; charset=UTF-8

Hi, eason, Welcome back to the session. Your session id is d0a339984606fa2721ae131f012918ec
```

另外，可以到我们指定的 session.save_path 目录下查看我们的 session 所存储的文件。以 sess_ 开头。即：

```
$ ll /tmp/sess*
-rw-------  1 _www  wheel    21B May  1 23:29 /tmp/sess_2e5db94d360e5df907425ff2d2fb379e
-rw-------  1 _www  wheel    21B May  1 23:32 /tmp/sess_d0a339984606fa2721ae131f012918ec
```

查看一下 session 文件的内容：

```
$ sudo cat /tmp/sess_2e5db94d360e5df907425ff2d2fb379e
username|s:5:"eason";
```

可以看出，session 文件中的变量内容是用分号（;）分隔的。每个变量格式为：名|类型：长度：值。可以使用`session_decode(), seesion_encode()`函数来完成参 SESSION 编码和解码。

- 注销会话

当用户退出或者需要重新登录时，就需要销毁会话了。有两种方法，分别是使用` session_unset(), session_destroy() `来完成。区别是前者并不会从 session 的存储中删除，后者会从存储中删除。

- `$_SESSION` 全局变量是用来设置和删除会话信息的。如上面的用 `$_SESSION['username'] = 'eason'` 设置一个变量信息，或者是用 `unset($_SESSION['username'])` 来删除一个会话变量。
- `session_id([string sid])` 获取或设置当前会话 id.
- `session_regenerate_id([boolean delete_old_session])` **每次重新生成会话 ID**, 有时候需要通过每次生成新会话 ID， 来避免 “会话固定（session-fixation）”攻击。

#### 1.2 使用 Redis 来存储 session

使用 files 或者共享内存存储 session 存在一个问题是当虚拟主机有多个服务器时，无法共享 session。所以在多个服务器时，需要用 DB 来存储 session. 这里用 redis 来演示。

##### demo

-  服务器源代码

```php
<?php
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://localhost:6379');
session_start();
$sid = session_id();
if ($_SESSION['username']) {
	echo "Hi,{$_SESSION['username']}, Welcome back to our site. your session id is $sid. \n";
} else {
	var_dump($_GET);
	$username = $_GET['username'] ? $_GET['username'] : 'eason';
	$age = $_GET['age'] ? $_GET['age'] : 23;
	$_SESSION['username'] = $username;
	$_SESSION['age'] = $age;
	echo "Hi, {$_SESSION['username']}, We create a new session for you. Your session id is $sid. \n You can append it your http request cookie. \n";
}
```

- 测试

```
$ curl -i http://session.redis.com?"username=jack&age=33"
HTTP/1.1 200 OK
Date: Mon, 01 May 2017 17:29:13 GMT
Server: Apache/2.4.25 (Unix) PHP/5.6.30
X-Powered-By: PHP/5.6.30
Set-Cookie: PHPSESSID=0fa4c6887a5f976aaf0ba3ad14eb495a; path=/
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Content-Length: 143
Content-Type: text/html; charset=UTF-8

Hi, jack, We create a new session for you. Your session id is 0fa4c6887a5f976aaf0ba3ad14eb495a.
 You can append it your http request cookie.

$ curl -i http://session.redis.com?"username=jack&age=33"
HTTP/1.1 200 OK
Date: Mon, 01 May 2017 17:31:50 GMT
Server: Apache/2.4.25 (Unix) PHP/5.6.30
X-Powered-By: PHP/5.6.30
Set-Cookie: PHPSESSID=3ee67324cb6b8178d4d756fcde8c1828; path=/
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Content-Length: 143
Content-Type: text/html; charset=UTF-8

Hi, jack, We create a new session for you. Your session id is 3ee67324cb6b8178d4d756fcde8c1828.
 You can append it your http request cookie.
```

前两请求服务器都会开启新的会话。如果请求时附带上  cookie 就是恢复会话了。

```
$ curl -i http://session.redis.com?"username=jack&age=33" --cookie "PHPSESSID=3ee67324cb6b8178d4d756fcde8c1828;"
HTTP/1.1 200 OK
Date: Mon, 01 May 2017 17:32:19 GMT
Server: Apache/2.4.25 (Unix) PHP/5.6.30
X-Powered-By: PHP/5.6.30
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Content-Length: 89
Content-Type: text/html; charset=UTF-8

Hi,jack, Welcome back to our site. your session id is 3ee67324cb6b8178d4d756fcde8c1828.
```

我们登上 redis 查看下 session 存储情况。因为我们第一次虽然创建了会话，但是并没有附带上 cookie 请求， 所以又创建了一个会话。

```
~/redis-3.2.8/src ⌚ 1:34:49
$ ./redis-cli
127.0.0.1:6379> keys PHPREDIS_SESSION:*
1) "PHPREDIS_SESSION:3ee67324cb6b8178d4d756fcde8c1828"
2) "PHPREDIS_SESSION:0fa4c6887a5f976aaf0ba3ad14eb495a"
127.0.0.1:6379> get PHPREDIS_SESSION:3ee67324cb6b8178d4d756fcde8c1828
"username|s:4:\"jack\";age|s:2:\"33\";"
127.0.0.1:6379> get PHPREDIS_SESSION:0fa4c6887a5f976aaf0ba3ad14eb495a
"username|s:4:\"jack\";age|s:2:\"33\";"
```

