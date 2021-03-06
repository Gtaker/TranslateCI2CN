<?php
/**
 * CodeIgniter
 *
 * 一款开源的PHP应用开发框架
 *
 * （注：以下为 MIT协议声明 原文）
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
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
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 公共函数
 *
 * 载入基础类并处理请求
 *
 * @package		CodeIgniter
 * @subpackage	CodeIgniter
 * @category	Common Functions
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/
 */

// ------------------------------------------------------------------------

if ( ! function_exists('is_php'))
{
	/**
     * 判断当前的PHP版本是否大于或等于传入的值
	 *
	 * @param	string
     * @return bool     如果当前版本号大于或等于 $version 的值时，返回 TRUE
	 */
	function is_php($version)
	{
		static $_is_php;
		$version = (string) $version;

		if ( ! isset($_is_php[$version]))
		{
			$_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
		}

		return $_is_php[$version];
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('is_really_writable'))
{
	/**
     * 测试文件的可写性
     *
     * 在 Windows 系统中，只有当文件标志了只读属性时，is_writable() 函数才返回 FALSE，
     * 当 safe_mode 选项开启时，is_writable() 函数在 Unix 服务器上将变得同样不可靠。
	 *
	 * @link	https://bugs.php.net/bug.php?id=54709
	 * @param	string
	 * @return	bool
	 */
	function is_really_writable($file)
	{
        // 如果运行环境为Unix服务器，且 safe_mode 选项是关闭的，那么我们可以使用 is_writable
		if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') OR ! ini_get('safe_mode')))
		{
			return is_writable($file);
		}

		/* 对于 safe_mode 为开启状态下的 Windows 服务器，
		 * 我们确实可以写入并读取一个文件，呸...
		 */
		if (is_dir($file))
		{
			$file = rtrim($file, '/').'/'.md5(mt_rand());
			if (($fp = @fopen($file, 'ab')) === FALSE)
			{
				return FALSE;
			}

			fclose($fp);
			@chmod($file, 0777);
			@unlink($file);
			return TRUE;
		}
		elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE)
		{
			return FALSE;
		}

		fclose($fp);
		return TRUE;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('load_class'))
{
	/**
	 * 类注册机
     *
     * 这个函数实现一个单例模式，如果请求的类没有被实例化，
     * 则将其实例化一个对象，存入一个静态变量中，
     * 如果该类已被实例化，则返回包含该类对象的变量。
	 *
	 * @param	string  $class  想要请求的类名
	 * @param	string  $directory  该类所在的目录
	 * @param	mixed	$param  可选项，向类的构造函数传递参数
	 * @return	object
	 */
	function &load_class($class, $directory = 'libraries', $param = NULL)
	{
		static $_classes = array();

        //该类已存在？那我们的活做完了...
		if (isset($_classes[$class]))
		{
			return $_classes[$class];
		}

		$name = FALSE;

        // 首先在本地的 "application"/$directory 文件夹下寻找匹配的类文件，
        // 然后在 "system"/$directory 文件夹下寻找匹配的类文件。
		foreach (array(APPPATH, BASEPATH) as $path)
		{
			if (file_exists($path.$directory.'/'.$class.'.php'))
			{
				$name = 'CI_'.$class;

				if (class_exists($name, FALSE) === FALSE)
				{
					require_once($path.$directory.'/'.$class.'.php');
				}

				break;
			}
		}

        // 如果是请求的是扩展类，那么我们也加载它（注：寻找 "application"/$directory/子类前缀+类名.php 文件）
		if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
		{
			$name = config_item('subclass_prefix').$class;

			if (class_exists($name, FALSE) === FALSE)
			{
				require_once(APPPATH.$directory.'/'.$name.'.php');
			}
		}

        // 我们找到这个类了吗？
		if ($name === FALSE)
		{
            // 注意：我们使用 exit() 而不是 show_error() 来避免异常类自我调用导致的死循环
			set_status_header(503);
			echo 'Unable to locate the specified class: '.$class.'.php';
			exit(5); // EXIT_UNKNOWN_CLASS（类不存在）
		}

        // 记录我们刚刚加载的类
		is_loaded($class);

		$_classes[$class] = isset($param)
			? new $name($param)
			: new $name();
		return $_classes[$class];
	}
}

// --------------------------------------------------------------------

if ( ! function_exists('is_loaded'))
{
	/**
     * 记录已经被加载的类库。
     * 该函数由 load_class() 函数调用。
	 *
	 * @param	string
	 * @return	array
	 */
	function &is_loaded($class = '')
	{
		static $_is_loaded = array();

		if ($class !== '')
		{
			$_is_loaded[strtolower($class)] = $class;
		}

		return $_is_loaded;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('get_config'))
{
	/**
     * 载入主配置文件 config.php
	 *
     * 这个函数可以让我们读取 Config 类实例化前的配置文件。
	 *
	 * @param	array
	 * @return	array
	 */
	function &get_config(Array $replace = array())
	{
		static $config;

		if (empty($config))
		{
			$file_path = APPPATH.'config/config.php';
			$found = FALSE;
			if (file_exists($file_path))
			{
				$found = TRUE;
				require($file_path);
			}

            // 在环境文件夹中是否存在配置文件？
			if (file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/config.php'))
			{
				require($file_path);
			}
			elseif ( ! $found)
			{
				set_status_header(503);
				echo 'The configuration file does not exist.';
				exit(3); // EXIT_CONFIG（配置错误）
			}

            //$config 数组是否已存在？
			if ( ! isset($config) OR ! is_array($config))
			{
				set_status_header(503);
				echo 'Your config file does not appear to be formatted correctly.';
				exit(3); // EXIT_CONFIG（配置错误）
			}
		}

        // 是否有需要动态添加或替换的值？
		foreach ($replace as $key => $val)
		{
			$config[$key] = $val;
		}

		return $config;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('config_item'))
{
	/**
     * 返回指定的配置项
	 *
	 * @param	string
	 * @return	mixed
	 */
	function config_item($item)
	{
		static $_config;

		if (empty($_config))
		{
            // 不能直接将一个引用赋值给静态变量（注：否则第二次调用函数时将会丢失静态变量中的数据），
            // 所以我们使用一个数组
			$_config[0] =& get_config();
		}

		return isset($_config[0][$item]) ? $_config[0][$item] : NULL;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('get_mimes'))
{
	/**
	 * 返回 config/mimes.php 文件中定义的 MIME 数组（的引用）
     *
	 * @return	array
	 */
	function &get_mimes()
	{
		static $_mimes;

		if (empty($_mimes))
		{
			$_mimes = file_exists(APPPATH.'config/mimes.php')
				? include(APPPATH.'config/mimes.php')
				: array();

			if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'))
			{
				$_mimes = array_merge($_mimes, include(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'));
			}
		}

		return $_mimes;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('is_https'))
{
	/**
     * 是否是HTTPS？
     *
     * 判断是否通过加密连接（HTTPS）访问应用程序
	 *
	 * @return	bool
	 */
	function is_https()
	{
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
		{
			return TRUE;
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
		{
			return TRUE;
		}
		elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
		{
			return TRUE;
		}

		return FALSE;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('is_cli'))
{

	/**
     * 是否是 CLI （命令行）模式？
	 *
     * 通过测试查看一个请求是否来自 cmd 命令行模式
	 *
	 * @return 	bool
	 */
	function is_cli()
	{
		return (PHP_SAPI === 'cli' OR defined('STDIN'));
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('show_error'))
{
	/**
     * 错误处理
	 *
     * 这个函数让我们调用异常类，并使用本地
     * application/views/errors/error_general.php
     * 文件中的错误模版来显示错误信息。
     * 这个函数将会直接将报错页面发送给浏览器并退出脚本执行。
	 *
	 * @param	string
	 * @param	int
	 * @param	string
	 * @return	void
	 */
	function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
	{
		$status_code = abs($status_code);
		if ($status_code < 100)
		{
			$exit_status = $status_code + 9; // 9是可分配的最小错误码常量（EXIT__AUTO_MIN）的值
			$status_code = 500;
		}
		else
		{
			$exit_status = 1; // EXIT_ERROR（一般错误）
		}

		$_error =& load_class('Exceptions', 'core');
		echo $_error->show_error($heading, $message, 'error_general', $status_code);
		exit($exit_status);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('show_404'))
{
	/**
     * 404页面处理
     *
     * 这个函数与上面的 show_error() 函数相似，
     * 不过显示的不是标准错误模版，而是404错误提示。
	 *
	 * @param	string
	 * @param	bool
	 * @return	void
	 */
	function show_404($page = '', $log_error = TRUE)
	{
		$_error =& load_class('Exceptions', 'core');
		$_error->show_404($page, $log_error);
		exit(4); // EXIT_UNKNOWN_FILE（文件没有找到）
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('log_message'))
{
	/**
     * 错误日志接口
	 *
     * 我们以此作为一种简单机制来访问日志类并将日志信息保存下来。
     *
	 * @param	string	$level  错误等级：'error','debug' 或 'info'
	 * @param	string	$message    错误信息
	 * @return	void
	 */
	function log_message($level, $message)
	{
		static $_log;

		if ($_log === NULL)
		{
            // 不能直接将一个引用赋值给静态变量（注：否则第二次调用函数时将会丢失静态变量中的数据），
            // 所以我们使用一个数组
			$_log[0] =& load_class('Log', 'core');
		}

		$_log[0]->write_log($level, $message);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('set_status_header'))
{
	/**
	 * 设置HTTP状态头
     *
	 * @param	int	$code   状态码
	 * @param	string
	 * @return	void
	 */
	function set_status_header($code = 200, $text = '')
	{
		if (is_cli())
		{
			return;
		}

		if (empty($code) OR ! is_numeric($code))
		{
			show_error('Status codes must be numeric', 500);
		}

		if (empty($text))
		{
			is_int($code) OR $code = (int) $code;
			$stati = array(
				100	=> 'Continue',
				101	=> 'Switching Protocols',

				200	=> 'OK',
				201	=> 'Created',
				202	=> 'Accepted',
				203	=> 'Non-Authoritative Information',
				204	=> 'No Content',
				205	=> 'Reset Content',
				206	=> 'Partial Content',

				300	=> 'Multiple Choices',
				301	=> 'Moved Permanently',
				302	=> 'Found',
				303	=> 'See Other',
				304	=> 'Not Modified',
				305	=> 'Use Proxy',
				307	=> 'Temporary Redirect',

				400	=> 'Bad Request',
				401	=> 'Unauthorized',
				402	=> 'Payment Required',
				403	=> 'Forbidden',
				404	=> 'Not Found',
				405	=> 'Method Not Allowed',
				406	=> 'Not Acceptable',
				407	=> 'Proxy Authentication Required',
				408	=> 'Request Timeout',
				409	=> 'Conflict',
				410	=> 'Gone',
				411	=> 'Length Required',
				412	=> 'Precondition Failed',
				413	=> 'Request Entity Too Large',
				414	=> 'Request-URI Too Long',
				415	=> 'Unsupported Media Type',
				416	=> 'Requested Range Not Satisfiable',
				417	=> 'Expectation Failed',
				422	=> 'Unprocessable Entity',
				426	=> 'Upgrade Required',
				428	=> 'Precondition Required',
				429	=> 'Too Many Requests',
				431	=> 'Request Header Fields Too Large',

				500	=> 'Internal Server Error',
				501	=> 'Not Implemented',
				502	=> 'Bad Gateway',
				503	=> 'Service Unavailable',
				504	=> 'Gateway Timeout',
				505	=> 'HTTP Version Not Supported',
				511	=> 'Network Authentication Required',
			);

			if (isset($stati[$code]))
			{
				$text = $stati[$code];
			}
			else
			{
				show_error('No status text available. Please check your status code number or supply your own message text.', 500);
			}
		}

		if (strpos(PHP_SAPI, 'cgi') === 0)
		{
			header('Status: '.$code.' '.$text, TRUE);
			return;
		}

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2'), TRUE))
			? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
		header($server_protocol.' '.$code.' '.$text, TRUE, $code);
	}
}

// --------------------------------------------------------------------

if ( ! function_exists('_error_handler'))
{
	/**
     * 错误处理
	 *
     * 这是在 CodeIgniter.php 头部注册的自定义错误处理程序（注：通过php的 set_error_handler() 函数）。
     * 我们使用这个函数的主要原因是，可以在用户没有访问服务器日志的情况下，
     * 将 PHP 错误记录到我们自己的日志文件中。因为该函数会有效的拦截 PHP 错误，
     * 所以我们也需要根据当前的错误提示等级显示错误。
     * 我们通过使用 PHP 的错误模版来实现这一功能。
	 *
	 * @param	int	$severity
	 * @param	string	$message
	 * @param	string	$filepath
	 * @param	int	$line
	 * @return	void
	 */
	function _error_handler($severity, $message, $filepath, $line)
	{
		$is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

        // 当一个错误发生时，设置状态头为"500 Internal Server Error"（500 内部服务器错误）
        // 以表明客户端的某个地方出现了错误。
        // 这不能完全依靠 $_error->show_php_error 方法中完成，
        // 因为该方法只能在设置了 display_errors 标记(生产环境通常不会设置)
        // 或出现的错误等级低于 error_reporting 中设置的等级而被忽略时调用。
		if ($is_error)
		{
			set_status_header(500);
		}

        // 获取当前的 error_reporting 等级，并判断是否应该忽略错误
        // 并将它和传入的 $severity 做按位与运算来得到结果。
		if (($severity & error_reporting()) !== $severity)
		{
			return;
		}

		$_error =& load_class('Exceptions', 'core');
		$_error->log_exception($severity, $message, $filepath, $line);

        // 我们应该显示错误吗？
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors')))
		{
			$_error->show_php_error($severity, $message, $filepath, $line);
		}

        // 如果发生了一个致命错误，那么应该终止脚本的执行，因为该错误是不可被修复的。
        // 通过PHP默认错误处理的方式终止脚本。
        // 请参照：http://www.php.net/manual/en/errorfunc.constants.php
		if ($is_error)
		{
			exit(1); // EXIT_ERROR（一般错误）
		}
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('_exception_handler'))
{
	/**
     * 异常处理
	 *
     * 发送一个捕获到的异常到日志记录器，
     * 并在 display_errors 开启的情况下将其显示出来，
     * 即在生产环境中不会显示异常。
	 *
	 * @param	Exception	$exception
	 * @return	void
	 */
	function _exception_handler($exception)
	{
		$_error =& load_class('Exceptions', 'core');
		$_error->log_exception('error', 'Exception: '.$exception->getMessage(), $exception->getFile(), $exception->getLine());

		is_cli() OR set_status_header(500);
        // 是否应该显示错误？
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors')))
		{
			$_error->show_exception($exception);
		}

		exit(1); // EXIT_ERROR（一般错误）
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('_shutdown_handler'))
{
	/**
     * 终止处理
	 *
	 * 这是一个在 CodeIgniter.php 顶部注册的错误处理函数。（注：通过php原生函数 register_shutdown_function() 注册）
     * 我们可以使用这个函数模拟完整的自定义错误处理机制。
     *
     * E_STRICT 类型的错误被有意忽略，因为这类错误可能已被捕获，
     * 将该错误回收还是忽略它？目前来说忽略它是更好的选择。
	 *
	 * @link	http://insomanic.me.uk/post/229851073/php-trick-catching-fatal-errors-e-error-with-a
	 * @return	void
	 */
	function _shutdown_handler()
	{
		$last_error = error_get_last();
		if (isset($last_error) &&
			($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
		{
			_error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
		}
	}
}

// --------------------------------------------------------------------

if ( ! function_exists('remove_invisible_characters'))
{
	/**
     * 移除不可见字符
	 *
	 * 该函数可以防止在ascii字符中夹带空字符，如 Java\0script。
     *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();

        // 移除除换行符(十进制ascii码为10)，回车符（十进制ascii码为13）
        // 和水平制表符（十进制ascii码值为09）外的所有控制字符。
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/i';	// 编码的ascii值为00-08，11，12，14，15的url字符
			$non_displayables[] = '/%1[0-9a-f]/i';	// 编码的ascii值为16-31的url字符
			$non_displayables[] = '/%7f/i';	// 编码的ascii值为127的url字符
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	//  ascii值为00-08，11，12，14-31，127的字符

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('html_escape'))
{
	/**
     * 将变量中的特殊字符转换为HTML实体并将结果返回
	 *
	 * @param	mixed	$var        输入的需要转义的字符串或字符串数组。
	 * @param	bool	$double_encode      将 $double_encode 设置为FALSE可以避免对现有的HTML实体转义。
	 * @return	mixed			将转义后的字符串或字符串数组作为结果返回。
	 */
	function html_escape($var, $double_encode = TRUE)
	{
		if (empty($var))
		{
			return $var;
		}

		if (is_array($var))
		{
			foreach (array_keys($var) as $key)
			{
				$var[$key] = html_escape($var[$key], $double_encode);
			}

			return $var;
		}

		return htmlspecialchars($var, ENT_QUOTES, config_item('charset'), $double_encode);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('_stringify_attributes'))
{
	/**
     * 在HTML标记中使用字符串化的属性
	 *
     * 该函数通常被用来将字符串，数组，或对象的属性转换为字符串
	 *
	 * @param	mixed   $attributes string（字符串）, array（数组）, object（对象）
	 * @param	bool    $js （注：如果$js的值为true，那么字符串化后的多个属性之间会加一个空格）
	 * @return	string
	 */
	function _stringify_attributes($attributes, $js = FALSE)
	{
		$atts = NULL;

		if (empty($attributes))
		{
			return $atts;
		}

		if (is_string($attributes))
		{
			return ' '.$attributes;
		}

		$attributes = (array) $attributes;

		foreach ($attributes as $key => $val)
		{
			$atts .= ($js) ? $key.'='.$val.',' : ' '.$key.'="'.$val.'"';
		}

		return rtrim($atts, ',');
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('function_usable'))
{
	/**
     * 函数可用性
     *
     * 执行 function_exists() 进行检查时，如果加载了 Suhosin PHP 扩展，
     * 将不会检查是否在 Suhosin扩展 （的配置）里禁用了被检查的函数。
	 *
     * 对于通过 php.ini 的 *disable_functions* 设置禁用的函数，
     * 这（function_exists()）将正确地返回FALSE，
     * 但是却不能正确检测在 *suhosin.executor.func.blacklist*
     * 和 *suhosin.executor.disable_eval* 设置中禁用的函数。
     * 这些设置只会在（用户）调用被禁用的函数时，终止脚本的执行。
     *
	 * The above described behavior turned out to be a bug in Suhosin,
	 * but even though a fix was committed for 0.9.34 on 2012-02-12,
	 * that version is yet to be released. This function will therefore
	 * be just temporary, but would probably be kept for a few years.
     *
     * 以上的描述为 Suhosin扩展 中的一个 bug，虽然该 bug 在 2012年02月12日
     * 提交的 0.9.34 版本中已被修复，但这个版本至今仍没有发布。（注：...）
     * 因此虽然这个函数只是临时的，但很可能这一等就又得好几年。
	 *
	 * @link	http://www.hardened-php.net/suhosin/
	 * @param	string	$function_name	需要检查的函数名
	 * @return	bool	如果函数存在并且允许被调用则返回TRUE，否则返回FALSE。
	 */
	function function_usable($function_name)
	{
		static $_suhosin_func_blacklist;

		if (function_exists($function_name))
		{
			if ( ! isset($_suhosin_func_blacklist))
			{
				$_suhosin_func_blacklist = extension_loaded('suhosin')
					? explode(',', trim(ini_get('suhosin.executor.func.blacklist')))
					: array();
			}

			return ! in_array($function_name, $_suhosin_func_blacklist, TRUE);
		}

		return FALSE;
	}
}
