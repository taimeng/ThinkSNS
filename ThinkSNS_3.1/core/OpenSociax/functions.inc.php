<?php
/**
 * Cookie 设置、获取、清除 (支持数组或对象直接设置) 2009-07-9
 * 1 获取cookie: cookie('name')
 * 2 清空当前设置前缀的所有cookie: cookie(null)
 * 3 删除指定前缀所有cookie: cookie(null,'think_') | 注：前缀将不区分大小写
 * 4 设置cookie: cookie('name','value') | 指定保存时间: cookie('name','value',3600)
 * 5 删除cookie: cookie('name',null)
 * $option 可用设置prefix,expire,path,domain
 * 支持数组形式:cookie('name','value',array('expire'=>1,'prefix'=>'think_'))
 * 支持query形式字符串:cookie('name','value','prefix=tp_&expire=10000')
 * 2010-1-17 去掉自动序列化操作，兼容其他语言程序。
 */
function cookie($name,$value='',$option=null) 
{
    // 默认设置
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire' => C('COOKIE_EXPIRE'), // cookie 保存时间
        'path'   => C('COOKIE_PATH'),   // cookie 保存路径
        'domain' => C('COOKIE_DOMAIN'), // cookie 有效域名
    );

    // 参数设置(会覆盖黙认设置)
    if (!empty($option)) 
    {
        if (is_numeric($option)) 
        {
            $option = array('expire'=>$option);
        }
        else if ( is_string($option) ) 
        {
            parse_str($option,$option);
        }
        $config = array_merge($config,array_change_key_case($option));
    }

    // 清除指定前缀的所有cookie
    if (is_null($name)) 
    {
       if (empty($_COOKIE)) return;
       // 要删除的cookie前缀，不指定则删除config设置的指定前缀
       $prefix = empty($value)? $config['prefix'] : $value;
       if (!empty($prefix))// 如果前缀为空字符串将不作处理直接返回
       {
           foreach($_COOKIE as $key=>$val) 
           {
               if (0 === stripos($key,$prefix))
               {
                    //todo:https判断
                    setcookie($_COOKIE[$key],'',time()-3600,$config['path'],$config['domain'],false,true);
                    unset($_COOKIE[$key]);
               }
           }
       }
       return;
    }
    $name = $config['prefix'].$name;

    if ('' === $value)
    {
        //return isset($_COOKIE[$name]) ? unserialize($_COOKIE[$name]) : null;// 获取指定Cookie
        return isset($_COOKIE[$name]) ? ($_COOKIE[$name]) : null;// 获取指定Cookie
    }
    else 
    {
        if (is_null($value)) 
        {
            setcookie($name,'',time()-3600,$config['path'],$config['domain']);
            unset($_COOKIE[$name]);// 删除指定cookie
        }
        else 
        {
            // 设置cookie
            $expire = !empty($config['expire'])? time()+ intval($config['expire']):0;
            //setcookie($name,serialize($value),$expire,$config['path'],$config['domain']);
            setcookie($name,($value),$expire,$config['path'],$config['domain']);
            //$_COOKIE[$name] = ($value);
        }
    }
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session ($name, $value = '') 
{
    $prefix = C('SESSION_PREFIX');
    if (is_array($name)) 
    {
        // session初始化 在session_start之前调用
        if (isset($name['prefix']))
        {
            C('SESSION_PREFIX', $name['prefix']);
        }
        if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) 
        {
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        } 
        else if (isset($name['id'])) 
        {
            session_id($name['id']);
        }
        ini_set('session.auto_start', 0);
        if (isset($name['name'])) 
        {
            session_name($name['name']);
        }
        if (isset($name['path'])) 
        {
            session_save_path($name['path']);
        }
        if (isset($name['domain'])) 
        {
            ini_set('session.cookie_domain', $name['domain']);
        }
        if (isset($name['expire'])) 
        {
            ini_set('session.gc_maxlifetime', $name['expire']);
        }
        if (isset($name['use_trans_sid'])) 
        {
            ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
        }
        if (isset($name['use_cookies'])) 
        {
            ini_set('session.use_cookies', $name['use_cookies']?1:0);
        }
        if (isset($name['cache_limiter'])) 
        {
            session_cache_limiter($name['cache_limiter']);
        }
        if (isset($name['cache_expire'])) 
        {
            session_cache_expire($name['cache_expire']);
        }
        if (isset($name['type'])) 
        {
            C('SESSION_TYPE', $name['type']);
        }
        if (C('SESSION_TYPE')) 
        { 
            // 读取session驱动
            $class = 'Session'.ucwords(strtolower(C('SESSION_TYPE')));
            // 检查驱动类
            if (require_once(CORE_LIB_PATH.'/Session/'.$class.'.class.php')) 
            {
                $hander = new $class();
                $hander->execute();
            }
            else 
            {
                // 类没有定义
                throw_exception(L('_CLASS_NOT_EXIST_').': '.$class);
            }
        }
        // 启动session
        if (C('SESSION_AUTO_START')) 
        {
            session_start();
        }
    } 
    else if ('' === $value) 
    { 
        if (0 === strpos($name, '[')) 
        { // session 操作
            if ('[pause]' == $name) 
            {
                // 暂停session
                session_write_close();
            } 
            else if ('[start]' == $name) 
            {
                // 启动session
                session_start();
            } 
            else if ('[destroy]' == $name) 
            {
                // 销毁session
                $_SESSION = array();
                session_unset();
                session_destroy();
            } 
            else if ('[regenerate]' == $name) 
            {
                // 重新生成id
                session_regenerate_id();
            }
        } 
        else if (0 === strpos($name, '?')) 
        { 
        	// 检查session
            $name = substr($name, 1);
            if (strpos($name, '.')) 
            {
                // 支持数组
                list($name1, $name2) = explode('.', $name);
                return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
            } 
            else 
            {
                return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
            }
        } 
        else if (is_null($name)) 
        { 
        	// 清空session
            if ($prefix) 
            {
                unset($_SESSION[$prefix]);
            } 
            else 
            {
                $_SESSION = array();
            }
        } 
        else if ($prefix) 
        { 
        	// 获取session
            if (strpos($name, '.')) 
            {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;  
            } 
            else 
            {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;                
            }            
        } 
        else 
        {
            if (strpos($name, '.')) 
            {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;  
            } 
            else 
            {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }            
        }
    } 
    else if (is_null($value)) 
    { 
    	// 删除session
        if ($prefix) 
        {
            unset($_SESSION[$prefix][$name]);
        } 
        else 
        {
            unset($_SESSION[$name]);
        }
    } 
    else 
    { 
    	// 设置session
        if ($prefix) 
        {
            if (!is_array($_SESSION[$prefix])) 
            {
                $_SESSION[$prefix] = array();
            }
            $_SESSION[$prefix][$name] = $value;
        } 
        else 
        {
            $_SESSION[$name] = $value;
        }
    }
}

/**
 * 获取站点唯一密钥，用于区分同域名下的多个站点
 * @return string
 */
function getSiteKey()
{
    return md5(C('SECURE_KEY').C('SECURE_CODE').C('COOKIE_PREFIX'));
}

/**
 * 是否AJAX请求
 * @return bool
 */
function isAjax() 
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) 
    {
        if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
            return true;
    }
    if (!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
    {
    	return true;
    }
    
    return false;
}

/**
 * 字符串命名风格转换
 * type
 * =0 将Java风格转换为C的风格
 * =1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name,$type=0) 
{
    if ($type) 
    {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    }
    else
    {
        $name = preg_replace("/[A-Z]/", "_\\0", $name);
        return strtolower(trim($name, "_"));
    }
}

/**
 * 优化格式的打印输出
 * @param string $var 变量
 * @param bool $return 是否return
 * @return mixed
 */
function dump($var, $return=false) 
{
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    if (!extension_loaded('xdebug')) 
    {
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        $output = '<pre style="text-align:left">'. htmlspecialchars($output, ENT_QUOTES). '</pre>';
    }
    if (!$return) 
    {
        echo '<pre style="text-align:left">';
        echo($output);
        echo '</pre>';
    }
    else
        return $output;
}

/**
 * 自定义异常处理
 * @param string $msg 异常消息
 * @param string $type 异常类型
 * @return string
 */
function throw_exception($msg,$type='') 
{
    header("Content-Type:text/html; charset=UTF8");
    if (defined('IS_CGI') && IS_CGI)   
    	exit($msg);
    
    if (class_exists($type,false))
        throw new $type($msg,$code,true);
    else
        die($msg); // 异常类型不存在则输出错误信息字串
}

/**
 * 系统自动加载ThinkPHP基类库和当前项目的model和Action对象
 * 并且支持配置自动加载路径
 * @param string $name 对象类名
 * @return void
 */
function halt($text) 
{
    return dump($text);
}

/**
 * 区分大小写的文件存在判断
 * @param string $filename 文件明
 * @return bool
 */
function file_exists_case($filename) 
{
    if (is_file($filename)) 
    {
        if (IS_WIN && C('APP_FILE_CASE')) 
        {
            if (basename(realpath($filename)) != basename($filename))
            {
            	return false;
            }
        }
        return true;
    }
    return false;
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 输入变量
 * @return string 输出唯一编号
 */
function to_guid_string($mix) 
{
    if (is_object($mix) && function_exists('spl_object_hash')) 
    {
        return spl_object_hash($mix);
    }
    elseif (is_resource($mix))
    {
        $mix = get_resource_type($mix).strval($mix);
    }
    else
    {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * 取得对象实例 支持调用类的静态方法
 * @param string $name 类名
 * @param string $method 方法
 * @param string $args 参数
 * @return object 对象实例
 */
function get_instance_of($name,$method='',$args=array()) 
{
    static $_instance = array();
    $identify = empty($args)?$name.$method:$name.$method.to_guid_string($args);
    if (!isset($_instance[$identify])) 
    {
        if (class_exists($name))
        {
            $o = new $name();
            if (method_exists($o,$method))
            {
                if (!empty($args)) 
                {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                }
                else 
                {
                    $_instance[$identify] = $o->$method();
                }
            }
            else
            {
                $_instance[$identify] = $o;
            }
        }
        else
        {
            halt(L('_CLASS_NOT_EXIST_').':'.$name);
        }
    }
    return $_instance[$identify];
}

/**
 * 自动加载类
 * @param string $name 类名
 * @return void
 */
function __autoload($name) 
{
    // 检查是否存在别名定义
    if (import($name)) 
    	return ;
    // 自动加载当前项目的Actioon类和Model类
    if (substr($name,-5)=="Model") 
    {
        import(APP_LIB_PATH.'Model/'.ucfirst($name).'.class.php');
    }
    elseif (substr($name,-6)=="Action")
    {
        import(APP_LIB_PATH.'Action/'.ucfirst($name).'.class.php');
    }
    else 
    {
        // 根据自动加载路径设置进行尝试搜索
        if (C('APP_AUTOLOAD_PATH')) 
        {
            $paths  =   explode(',',C('APP_AUTOLOAD_PATH'));
            foreach ($paths as $path)
            {
                if (import($path.'/'.$name.'.class.php')) 
                {
                    // 如果加载类成功则返回
                    return ;
                }
            }
        }
    }
    return ;
}

/**
 * 导入类库
 * @param string $name 类名
 * @return bool
 */
function import($filename) 
{
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) 
    {
        if (file_exists($filename))
        {
            require $filename;
            $_importFiles[$filename] = true;
        }
        else
        {
            $file = explode('.', $filename);
            if (file_exists(APPS_PATH.'/'.$file[0].'/Lib/'.$file[1].'/'.$file[2].'.class.php'))
            {
                require APPS_PATH.'/'.$file[0].'/Lib/'.$file[1].'/'.$file[2].'.class.php';
                $_importFiles[$filename] = true;
            }
            else
            {
                $_importFiles[$filename] = false;
            }
        }
    }
    return $_importFiles[$filename];
}

/**
 * C函数用于读取/设置系统配置
 * @param string name 配置名称
 * @param string value 值
 * @return mixed 配置值|设置状态
 */
function C($name=null,$value=null) 
{
    global $ts;
    // 无参数时获取所有
    if (empty($name)) return $ts['_config'];
    // 优先执行设置获取或赋值
    if (is_string($name))
    {
        if (!strpos($name,'.')) 
        {
            $name = strtolower($name);
            if (is_null($value))
                return isset($ts['_config'][$name])? $ts['_config'][$name] : null;
            $ts['_config'][$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.',$name);
        $name[0]   = strtolower($name[0]);
        if (is_null($value))
            return isset($ts['_config'][$name[0]][$name[1]]) ? $ts['_config'][$name[0]][$name[1]] : null;
        $ts['_config'][$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if (is_array($name))
        return $ts['_config'] = array_merge((array)$ts['_config'],array_change_key_case($name));
    return null;// 避免非法参数
}

//D函数的别名
function M($name='',$app='@') 
{
    return D($name,$app);
}

/**
 * D函数用于实例化Model
 * @param string name Model名称
 * @param string app Model所在项目
 * @return object
 */
function D($name='', $app='@', $inclueCommonFunction=true) 
{
    static $_model = array();

    if (empty($name)) 
    	return new Model;
    
    if (empty($app) || $app=='@')   
    	$app = APP_NAME;

    $name = ucfirst($name);
        
    if (isset($_model[$app.$name]))
        return $_model[$app.$name];

    $OriClassName = $name;
    $className =  $name.'Model';

    //优先载入核心的 所以不要和核心的model重名
    if (file_exists(ADDON_PATH.'/model/'.$className.'.class.php'))
    {
        tsload(ADDON_PATH.'/model/'.$className.'.class.php');
    }
    elseif (file_exists(APPS_PATH.'/'.$app.'/Lib/Model/'.$className.'.class.php'))
    {
        $common = APPS_PATH.'/'.$app.'/Common/common.php';
        if (file_exists($common) && $inclueCommonFunction)
        {
            tsload($common);
        }
        tsload(APPS_PATH.'/'.$app.'/Lib/Model/'.$className.'.class.php');
    }
    
    if (class_exists($className)) 
    {
        $model = new $className();
    }
    else
    {
        $model = new Model($name);
    }
    $_model[$app.$OriClassName] = $model;
    return $model;
}

/**
 * A函数用于实例化Action
 * @param string name Action名称
 * @param string app Model所在项目
 * @return object
 */
function A($name,$app='@') 
{
    static $_action = array();

    if (empty($app) || $app=='@')
    {   
    	$app =  APP_NAME;
    }

    if (isset($_action[$app.$name]))
    {
        return $_action[$app.$name];
    }

    $OriClassName = $name;
    $className =  $name.'Action';
    tsload(APP_ACTION_PATH.'/'.$className.'.class.php');

    if (class_exists($className)) 
    {
        $action = new $className();
        $_action[$app.$OriClassName] = $action;
        return $action;
    }
    else 
    {
        return false;
    }
}

/**
 * L函数用于读取/设置语言配置
 * @param string name 配置名称
 * @param string value 值
 * @return mixed 配置值|设置状态
 */
function L($key,$data = array())
{
    $key = strtoupper($key);
    if (!isset($GLOBALS['_lang'][$key]))
    {
     	  $notValveForKey = F('notValveForKey', '', DATA_PATH.'/develop');
     	  if ($notValveForKey==false)
     	  {
     	  	 $notValveForKey = array();
     	  }
     	  if (!isset($notValveForKey[$key]))
     	  {
     	  	 $notValveForKey[$key] = '?app='.APP_NAME.'&mod='.MODULE_NAME.'&act='.ACTION_NAME;
     	  }
     	  F('notValveForKey', $notValveForKey, DATA_PATH.'/develop');
     	  
          return $key;
     }
     if (empty($data))
     {
          return $GLOBALS['_lang'][$key];
     }
     $replace = array_keys($data);
     foreach($replace as &$v)
     {
        $v = "{".$v."}";
     }
     return str_replace($replace,$data,$GLOBALS['_lang'][$key]);
}

/**
 * 记录和统计时间（微秒）和内存使用情况
 * 使用方法:
 * <code>
 * G('begin'); // 记录开始标记位
 * // ... 区间运行代码
 * G('end'); // 记录结束标签位
 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
 * echo G('begin','end','m'); // 统计区间内存使用情况
 * 如果end标记位没有定义，则会自动以当前作为标记位
 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
 * </code>
 * @param string $start 开始标签
 * @param string $end 结束标签
 * @param integer|string $dec 小数位或者m 
 * @return mixed
 */
function G($start,$end='',$dec=4) 
{
    static $_info = array();
    static $_mem = array();
    if (is_float($end)) 
    { 
    	// 记录时间
        $_info[$start] = $end;
    }
    elseif (!empty($end))
    { 
    	// 统计时间和内存使用
        if (!isset($_info[$end]))
        {
        	$_info[$end] = microtime(TRUE);
        }
        if (MEMORY_LIMIT_ON && $dec=='m')
        {
            if (!isset($_mem[$end])) 
            {
            	$_mem[$end] = memory_get_usage();
            }
            return number_format(($_mem[$end]-$_mem[$start])/1024);          
        }
        else
        {
            return number_format(($_info[$end]-$_info[$start]),$dec);
        }       
            
    }
    else
    { 
    	// 记录时间和内存使用
        $_info[$start] = microtime(TRUE);
        if (MEMORY_LIMIT_ON) 
        {
        	$_mem[$start] = memory_get_usage();
        }
    }
}

/**
 * 设置和获取统计数据
 * 使用方法:
 * <code>
 * N('db',1); // 记录数据库操作次数
 * N('read',1); // 记录读取次数
 * echo N('db'); // 获取当前页面数据库的所有操作次数
 * echo N('read'); // 获取当前页面读取次数
 * </code> 
 * @param string $key 标识位置
 * @param integer $step 步进值
 * @return mixed
 */
function N($key, $step=0,$save=false) 
{
    static $_num = array();
    if (!isset($_num[$key])) 
    {
        $_num[$key] = (false !== $save)? S('N_'.$key) :  0;
    }
    
    if (empty($step))
    {
        return $_num[$key];
    }
    else
    {
        $_num[$key] = $_num[$key] + (int) $step;
    }
    
    if (false !== $save)
    { 
    	// 保存结果
        S('N_'.$key,$_num[$key],$save);
    }
}

/**
 * 用于判断文件后缀是否是图片
 * @param string file 文件路径，通常是$_FILES['file']['tmp_name']
 * @return bool
 */
function is_image_file($file)
{
  $fileextname = strtolower(substr(strrchr(rtrim(basename($file),'?'),"."),1,4));
  if (in_array($fileextname,array('jpg','jpeg','gif','png','bmp')))
  {
    return true;
  }
  else
  {
    return false;
  }
}

/**
 * 用于判断文件后缀是否是PHP、EXE类的可执行文件
 * @param string file 文件路径
 * @return bool
 */
function is_notsafe_file($file)
{
  $fileextname = strtolower(substr(strrchr(rtrim(basename($file),'?'), "."),1,4));
  if (in_array($fileextname,array('php','php3','php4','php5','exe','sh')))
  {
    return true;
  }
  else
  {
    return false;
  }
}

/**
 * t函数用于过滤标签，输出没有html的干净的文本
 * @param string text 文本内容
 * @return string 处理后内容
 */
function t($text)
{
    $text = nl2br($text);
    $text = real_strip_tags($text);
    $text = addslashes($text);
    $text = trim($text);
    return $text;
}

/** 
 * h函数用于过滤不安全的html标签，输出安全的html
 * @param string $text 待过滤的字符串
 * @param string $type 保留的标签格式
 * @return string 处理后内容
 */
function h($text, $type = 'html')
{
    // 无标签格式
    $text_tags = '';
    //只保留链接
    $link_tags = '<a>';
    //只保留图片
    $image_tags = '<img>';
    //只存在字体样式
    $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    //标题摘要基本格式
    $base_tags = $font_tags.'<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
    //兼容Form格式
    $form_tags = $base_tags.'<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    //内容等允许HTML的格式
    $html_tags = $base_tags.'<meta><ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    //专题等全HTML格式
    $all_tags = $form_tags.$html_tags.'<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    //过滤标签
    $text = real_strip_tags($text, ${$type.'_tags'});
    // 过滤攻击代码
    if ($type != 'all') 
    {
        // 过滤危险的属性，如：过滤on事件lang js
        while(preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i',$text,$mat))
        {
            $text = str_ireplace($mat[0], $mat[1].$mat[3], $text);
        }
        while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat))
        {
            $text = str_ireplace($mat[0], $mat[1].$mat[3], $text);
        }
    }
    return $text;
}

/** 
 * U函数用于生成URL地址
 * @param string $url ThinkSNS特有URL标识符
 * @param array $params URL附加参数
 * @param bool $redirect 是否自动跳转到生成的URL
 * @return string 输出URL
 */
function U($url,$params=false,$redirect=false) 
{
    //普通模式
    if (false==strpos($url,'/'))
    {
        $url .='//';
    }

    //填充默认参数
    $urls = explode('/',$url);
    $app = isset($urls[0]) && !empty($urls[0]) ? $urls[0] : APP_NAME;
    $mod = isset($urls[1]) && !empty($urls[1]) ? ucfirst($urls[1]) : 'Index';
    $act = isset($urls[2]) && !empty($urls[2]) ? $urls[2] : 'index';

    //组合默认路径
    $site_url = SITE_URL.'/index.php?app='.$app.'&mod='.$mod.'&act='.$act;

    //填充附加参数
    if ($params)
    {
        if (is_array($params))
        {
            $params = http_build_query($params);
            $params = urldecode($params);
        }
        $params = str_replace('&amp;','&',$params);
        $site_url .= '&'.$params;
    }

    //开启路由和Rewrite
    if (C('URL_ROUTER_ON'))
    {
        //载入路由
        $router_ruler = C('router');
        $router_key = $app.'/'.$mod.'/'.$act;

        //路由命中
        if (isset($router_ruler[$router_key]))
        {
            //填充路由参数
            if (false==strpos($router_ruler[$router_key],'://'))
            {
                $site_url = SITE_URL.'/'.$router_ruler[$router_key];
            }
            else
            {
                $site_url = $router_ruler[$router_key];
            }

            //填充附加参数
            if ($params)
            {
                //解析替换URL中的参数
                parse_str($params,$r);
                foreach($r as $k=>$v)
                {
                    if (strpos($site_url,'['.$k.']'))
                    {
                        $site_url = str_replace('['.$k.']',$v,$site_url);
                    }
                    else
                    {
                        $lr[$k] = $v;
                    }
                }

                //填充剩余参数
                if (isset($lr) && is_array($lr) && count($lr)>0)
                {
                    $site_url .= '?'.http_build_query($lr);
                }

            }
        }
    }

    //输出地址或跳转
    if ($redirect)
    {
        redirect($site_url);
    }
    else
    {
        return $site_url;
    }
}

/** 
 * URL跳转函数
 * @param string $url ThinkSNS特有URL标识符
 * @param integer $time 跳转延时(秒)
 * @param string $msg 提示语
 * @return void
 */
function redirect($url,$time=0,$msg='') 
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
    {
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    }
    if (!headers_sent()) 
    {
        // redirect
        if (0===$time) 
        {
            header("Location: ".$url);
        }
        else 
        {
            header("Content-type: text/html; charset=utf-8");
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    }
    else 
    {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time!=0)
        {
            $str .= $msg;
        }
        exit($str);
    }
}

/**
 * 用来对应用缓存信息的读、写、删除
 *
 * $expire = null/0 表示永久缓存，否则为缓存有效期
 */
function S($name,$value='',$expire=null) 
{
    static $_cache = array();   //减少缓存读取

    $cache = model('Cache');

    //$name = C('DATA_CACHE_PREFIX').$name;

    if ('' !== $value) 
    {
        if (is_null($value)) 
        {
            // 删除缓存
            $result = $cache->rm($name);
            if ($result)  
            { 
            	unset($_cache[$name]);
            }
            return $result;
        }
        else
        {
            // 缓存数据
            $cache->set($name,$value,$expire);
            $_cache[$name] = $value;
        }
        return true;
    }
    if (isset($_cache[$name]))
    {
        return $_cache[$name];
    }
    // 获取缓存数据
    $value = $cache->get($name);
    $_cache[$name] = $value;
    return $value;
}

/**
 * 文件缓存,多用来缓存配置信息
 *
 */
function F($name,$value='',$path=false) 
{
    static $_cache = array();
    if (!$path) 
    {
        $path = C('F_CACHE_PATH');
    }
    if (!is_dir($path)) 
    {
        mkdir($path,0777,true);
    }
    $filename = $path.'/'.$name.'.php';
    if ('' !== $value) 
    {
        if (is_null($value)) 
        {
            // 删除缓存
            return unlink($filename);
        }
        else
        {
            // 缓存数据
            $dir = dirname($filename);
            // 目录不存在则创建
            if (!is_dir($dir))  
            {
            	mkdir($dir,0777,true);
            }
            return @file_put_contents($filename,"<?php\nreturn ".var_export($value,true).";\n?>");
        }
    }
    if (isset($_cache[$name])) 
    {
    	return $_cache[$name];
    }
    // 获取缓存数据
    if (is_file($filename)) 
    {
        $value = include $filename;
        $_cache[$name] = $value;
    }
    else
    {
        $value = false;
    }
    return $value;
}

function W($name,$data=array(),$return=false) 
{
    $class = $name.'Widget';
    
//     //////////////////
//     echo '<br/>';
//     echo '< '.$class.' >';
//     echo '<br/>';
//     //////////////////
    
    if (file_exists(APP_WIDGET_PATH.'/'.$class.'/'.$class.'.class.php'))
    {
        tsload(APP_WIDGET_PATH.'/'.$class.'/'.$class.'.class.php');
    }
    elseif (!empty($data['widget_appname']) && 
    		file_exists(APPS_PATH.'/'.$data['widget_appname'].'/Lib/Widget/'.$class.'/'.$class.'.class.php'))
    {
        addLang($data['widget_appname']);
        tsload(APPS_PATH.'/'.$data['widget_appname'].'/Lib/Widget/'.$class.'/'.$class.'.class.php');
    }
    else
    {
        tsload(ADDON_PATH.'/widget/'.$class.'/'.$class.'.class.php');
    }
    
    if (!class_exists($class))
    {
        throw_exception(L('_CLASS_NOT_EXIST_').':'.$class);
    }
    
    $widget = new $class();
    $content = $widget->render($data);
    
    if ($return)
        return $content;
    else
        echo $content;
}

// 实例化服务
function api($name,$params=array()) 
{
    static $_api = array();
    if (isset($_api[$name]))
    {
        return $_api[$name];
    }
    $OriClassName = $name;
    $className = $name.'Api';
    require_once(ADDON_PATH.'/api/'.$name.'Api.class.php');
    if (class_exists($className)) 
    {
        $api = new $className(true);
        $_api[$OriClassName] = $api;
        return $api;
    }
    else 
    {
        return false;
    }
}

// 实例化服务
function service($name,$params=array()) 
{
    return X($name,$params,'service');
}

// 实例化服务
function widget($name,$params=array(),$return=false) 
{
    return X($name,$params,'widget');
}

// 实例化model
function model($name,$params=array()) 
{
    return X($name,$params,'model');
}

// 调用接口服务
function X($name,$params=array(),$domain='model') 
{
    static $_service = array();
    
    $app = C('DEFAULT_APP');

    $domain = ucfirst($domain);

    if (isset($_service[$domain.'_'.$app.'_'.$name]))
        return $_service[$domain.'_'.$app.'_'.$name];

    $class = $name.$domain;
    if (file_exists(APP_LIB_PATH.$domain.'/'.$class.'.class.php'))
    {
        tsload(APP_LIB_PATH.$domain.'/'.$class.'.class.php');
    }
    else
    {
        tsload(ADDON_PATH.'/'.strtolower($domain).'/'.$class.'.class.php',true);
    }
    //服务不可用时 记录日志 或 抛出异常
    if (class_exists($class))
    {
        $obj = new $class($params);
        $_service[$domain.'_'.$app.'_'.$name] = $obj;
        return $obj;
    }
    else
    {
        throw_exception(L('_CLASS_NOT_EXIST_').':'.$class);
    }
}

// 渲染模板
//$charset 不能是UTF8 否则IE下会乱码
function fetch($templateFile='',$tvar=array(),$charset='utf-8',$contentType='text/html',$display=false) 
{
    //注入全局变量ts
    global  $ts;
    $tvar['ts'] = $ts;
    //$GLOBALS['_viewStartTime'] = microtime(TRUE);

    if (null===$templateFile)
    {
        // 使用null参数作为模版名直接返回不做任何输出
        return ;
    }

    if (empty($charset))  
    {
    	$charset = C('DEFAULT_CHARSET');
    }

    // 网页字符编码
    header("Content-Type:".$contentType."; charset=".$charset);

    header("Cache-control: private");  //支持页面回跳

    //页面缓存
    ob_start();
    ob_implicit_flush(0);

    // 模版名为空.
    if (''==$templateFile)
    {
        $templateFile = APP_TPL_PATH.'/'.MODULE_NAME.'/'.ACTION_NAME.'.html';

    // 模版名为ACTION_NAME
    }
    elseif (file_exists(APP_TPL_PATH.'/'.MODULE_NAME.'/'.$templateFile.'.html')) 
    {
        $templateFile = APP_TPL_PATH.'/'.MODULE_NAME.'/'.$templateFile.'.html';

    // 模版是绝对路径
    }
    elseif (file_exists($templateFile))
    {

    // 模版不存在
    }
    else
    {
        throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
    }

    //模版缓存文件
    $templateCacheFile = C('TMPL_CACHE_PATH').'/'.APP_NAME.'_'.tsmd5($templateFile).'.php';

    //载入模版缓存
    if (!$ts['_debug'] && file_exists($templateCacheFile)) 
    {
    //if (1==2){ //TODO  开发
        extract($tvar, EXTR_OVERWRITE);

        //载入模版缓存文件
        include $templateCacheFile;

    //重新编译
    }
    else
    {
        tshook('tpl_compile',array('templateFile',$templateFile));

        // 缓存无效 重新编译
        tsload(CORE_LIB_PATH.'/Template.class.php');
        tsload(CORE_LIB_PATH.'/TagLib.class.php');
        tsload(CORE_LIB_PATH.'/TagLib/TagLibCx.class.php');

        $tpl = Template::getInstance();
        // 编译并加载模板文件
        $tpl->load($templateFile,$tvar,$charset);
    }

    // 获取并清空缓存
    $content = ob_get_clean();

    // 模板内容替换
    $replace = array(
        '__ROOT__'      =>  SITE_URL,           // 当前网站地址
        '__UPLOAD__'    =>  UPLOAD_URL,         // 上传文件地址
        //'__PUBLIC__'    =>  PUBLIC_URL,       // 公共静态地址
        '__PUBLIC__'    =>  THEME_PUBLIC_URL,   // 公共静态地址
        '__THEME__'     =>  THEME_PUBLIC_URL,   // 主题静态地址
        '__APP__'       =>  APP_PUBLIC_URL,     // 应用静态地址
        '__URL__'       =>  __ROOT__.'/index.php?app='.APP_NAME.'&mod='.MODULE_NAME,
    );

    if (C('TOKEN_ON')) 
    {
        if (strpos($content,'{__TOKEN__}')) 
        {
            // 指定表单令牌隐藏域位置
            $replace['{__TOKEN__}'] = $this->buildFormToken();
        }
        elseif (strpos($content,'{__NOTOKEN__}'))
        {
            // 标记为不需要令牌验证
            $replace['{__NOTOKEN__}'] = '';
        }
        elseif (preg_match('/<\/form(\s*)>/is',$content,$match)) 
        {
            // 智能生成表单令牌隐藏域
            $replace[$match[0]] = $this->buildFormToken().$match[0];
        }
    }

    // 允许用户自定义模板的字符串替换
    if (is_array(C('TMPL_PARSE_STRING')))
    {
    	$replace = array_merge($replace,C('TMPL_PARSE_STRING'));
    }

    $content = str_replace(array_keys($replace),array_values($replace),$content);

    // 布局模板解析
    //$content = $this->layout($content,$charset,$contentType);
    // 输出模板文件
    if ($display)
        echo $content;
    else
        return $content;
}

// 输出模版
function display($templateFile='',$tvar=array(),$charset='UTF8',$contentType='text/html') 
{
    fetch($templateFile,$tvar,$charset,$contentType,true);
}

function mk_dir($dir, $mode = 0755)
{
  if (is_dir($dir) || @mkdir($dir,$mode)) 
  	return true;
  
  if (!mk_dir(dirname($dir),$mode)) 
  	return false;
  
  return @mkdir($dir,$mode);
}

/**
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 * @return string
 */
function byte_format($size, $dec=2) 
{
    $a = array("B", "KB", "MB", "GB", "TB", "PB");
    $pos = 0;
    while ($size >= 1024) 
    {
         $size /= 1024;
         $pos++;
    }
    return round($size,$dec)." ".$a[$pos];
}

/**
 * 获取客户端IP地址
 */
function get_client_ip($type = 0) 
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) 
    {
    	return $ip[$type];
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) 
    {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown',$arr);
        if (false !== $pos) 
        {
        	unset($arr[$pos]);
        }
        $ip = trim($arr[0]);
    }
    elseif (isset($_SERVER['HTTP_CLIENT_IP'])) 
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (isset($_SERVER['REMOTE_ADDR'])) 
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip = $long ? array($ip, $long) : array('127.0.0.1', 0);
    return $ip[$type];
}

/**
 * 记录日志
 * Enter description here ...
 * @param unknown_type $app_group
 * @param unknown_type $action
 * @param unknown_type $data
 * @param unknown_type $isAdmin 是否管理员日志
 */
function LogRecord($app_group,$action,$data,$isAdmin=false)
{
    static $log = null;
    if ($log == null)
    {
        $log = model('Logs');
    }
    return $log->load($app_group)->action($action)->record($data,$isAdmin);
}

/**
 * 验证权限方法
 * @param string $load 应用 - 模块 字段
 * @param string $action 权限节点字段
 * @param unknown_type $group 是否指定应用内部用户组
 */
function CheckPermission($load = '', $action = '', $group = '')
{
    if (empty($load) || empty($action)) 
    {
        return false;
    }
    $Permission = model('Permission')->load($load);
    if (!empty($group))
    {
        return $Permission->group($group)->check($action);
    }

    return $Permission->check($action);
}
/**
 * 微吧管理权限判断
 * @param int $id 微吧id
 * @param string $action 动作
 * @param int $uid 用户uid
 * @return boolean
 */
function CheckWeibaPermission( $weiba_admin , $id , $action , $uid)
{
	!$uid && $uid = $GLOBALS['ts']['mid'];
	//超级管理员判断
	if ( CheckPermission('core_admin','admin_login') )
	{
		return true;
	}
	if ( $action )
	{
		//用户组权限判断
		if ( CheckPermission( 'weiba_admin' , $action ) )
		{
			return true;
		}
	}
	//吧主判断
	if ( !$weiba_admin && $id )
	{
		$map['weiba_id'] = $id;
		$map['level'] = array('in','2,3');
		$weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid,level')->findAll();
		$weiba_admin = getSubByKey( $weiba_admin , 'follower_uid' );
	}
	return in_array( $uid , $weiba_admin);
}
function CheckTaskSwitch()
{
	$taskswitch = model('Xdata')->get('task_config:task_switch');
	!$taskswitch && $taskswitch = 1;
	
	return $taskswitch == 1;
}
//获取当前用户的前台管理权限
function manageList($uid)
{
    $list = model('App')->getManageApp($uid);
    return $list;
}

/**
 * 指定用户是否申请认证通过
 * @param integer $uid 用户UID
 * @return boolean 是否申请认证通过
 */
function isVerified ($uid) 
{
    $isMidVerify = D('user_verified')->where('verified=1 AND uid='.$uid)->find();
    return (boolean)$isMidVerify;
}

/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey="", $pCondition="")
{
    $result = array();
    if (is_array($pArray))
    {
        foreach($pArray as $temp_array)
        {
            if (is_object($temp_array))
            {
                $temp_array = (array) $temp_array;
            }
            if ((""!=$pCondition && $temp_array[$pCondition[0]]==$pCondition[1]) || ""==$pCondition) 
            {
                $result[] = (""==$pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            }
        }
        return $result;
    }
    else
    {
        return false;
    }
}

/**
 * 获取字符串的长度
 *
 * 计算时, 汉字或全角字符占1个长度, 英文字符占0.5个长度
 *
 * @param string  $str
 * @param boolean $filter 是否过滤html标签
 * @return int 字符串的长度
 */
function get_str_length($str, $filter = false)
{
    if ($filter) 
    {
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        $str = strip_tags($str);
    }
    return (strlen($str) + mb_strlen($str, 'UTF8')) / 4;
}

function getShort($str, $length = 40, $ext = '') 
{
    $str = htmlspecialchars($str);
    $str = strip_tags($str);
    $str = htmlspecialchars_decode($str);
    $strlenth = 0;
    $out = '';
    preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);
    foreach($match[0] as $v)
    {
        preg_match("/[\xe0-\xef][\x80-\xbf]{2}/",$v, $matchs);
        if (!empty($matchs[0]))
        {
            $strlenth += 1;
        }
        elseif (is_numeric($v))
        {
            //$strlenth += 0.545;  // 字符像素宽度比例 汉字为1
            $strlenth += 0.5;    // 字符字节长度比例 汉字为1
        }
        else
        {
            //$strlenth += 0.475;  // 字符像素宽度比例 汉字为1
            $strlenth += 0.5;    // 字符字节长度比例 汉字为1
        }

        if ($strlenth > $length) 
        {
            $output .= $ext;
            break;
        }

        $output .= $v;
    }
    return $output;
}

/**
 * 检查字符串是否是UTF8编码
 * @param string $string 字符串
 * @return Boolean
 */
if (!function_exists('is_utf8'))
{
    function is_utf8($string) 
    {
        return preg_match('%^(?:
             [\x09\x0A\x0D\x20-\x7E]            # ASCII
           | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
           |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
           | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
           |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
           |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
           | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
           |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
       )*$%xs', $string);
    }
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents,$from,$to)
{
    $from = strtoupper($from)=='UTF8'? 'utf-8':$from;
    $to = strtoupper($to)=='UTF8'? 'utf-8':$to;
    if ( strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)) )
    {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) 
    {
        if (function_exists('iconv'))
        {
            return iconv($from,$to,$fContents);
        }
        else
        {
            return $fContents;
        }
    }
    elseif (is_array($fContents))
    {
        foreach ( $fContents as $key => $val ) 
        {
            $_key = auto_charset($key,$from,$to);
            $fContents[$_key] = auto_charset($val,$from,$to);
            if ($key != $_key )
            {
            	unset($fContents[$key]);
            }
        }
        return $fContents;
    }
    else
    {
        return $fContents;
    }
}

/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @param string $alt   已失效
 * @return string
 */
function friendlyDate($sTime,$type = 'normal',$alt = 'false') 
{
    if (!$sTime)
    {
    	return '';
    }
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime = time();
    $dTime = $cTime - $sTime;
    $dDay  = intval(date("z",$cTime)) - intval(date("z",$sTime));
    //$dDay = intval($dTime/3600/24);
    $dYear = intval(date("Y",$cTime)) - intval(date("Y",$sTime));
    //normal：n秒前，n分钟前，n小时前，日期
    if ($type == 'normal')
    {
        if ( $dTime < 60 )
        {
            if ($dTime < 10)
            {
                return '刚刚';    //by yangjs
            }
            else
            {
                return intval(floor($dTime / 10) * 10)."秒前";
            }
        }
        elseif ( $dTime < 3600 )
        {
            return intval($dTime/60)."分钟前";
        //今天的数据.年份相同.日期相同.
        }
        elseif ( $dYear==0 && $dDay == 0 )
        {
            //return intval($dTime/3600)."小时前";
            return '今天'.date('H:i',$sTime);
        }
        elseif ($dYear==0)
        {
            return date("m月d日 H:i",$sTime);
        }
        else
        {
            return date("Y-m-d H:i",$sTime);
        }
    }
    elseif ($type=='mohu')
    {
        if ( $dTime < 60 )
        {
            return $dTime."秒前";
        }
        elseif ( $dTime < 3600 )
        {
            return intval($dTime/60)."分钟前";
        }
        elseif ( $dTime >= 3600 && $dDay == 0  )
        {
            return intval($dTime/3600)."小时前";
        }
        elseif ( $dDay > 0 && $dDay<=7 )
        {
            return intval($dDay)."天前";
        }
        elseif ( $dDay > 7 &&  $dDay <= 30 )
        {
            return intval($dDay/7) . '周前';
        }
        elseif ( $dDay > 30 )
        {
            return intval($dDay/30) . '个月前';
        }
    //full: Y-m-d , H:i:s
    }
    elseif ($type == 'full')
    {
        return date("Y-m-d , H:i:s", $sTime);
    }
    elseif ($type == 'ymd')
    {
        return date("Y-m-d", $sTime);
    }
    else
    {
        if ( $dTime < 60 )
        {
            return $dTime."秒前";
        }
        elseif ( $dTime < 3600 )
        {
            return intval($dTime/60)."分钟前";
        }
        elseif ( $dTime >= 3600 && $dDay == 0  )
        {
            return intval($dTime/3600)."小时前";
        }
        elseif ($dYear==0)
        {
            return date("Y-m-d H:i:s", $sTime);
        }
        else
        {
            return date("Y-m-d H:i:s", $sTime);
        }
    }
}

/**
 * 
 * 正则替换和过滤内容
 * 
 * @param  $html
 * @author jason
 */
function preg_html($html)
{
    $p = array("/<[a|A][^>]+(topic=\"true\")+[^>]*+>#([^<]+)#<\/[a|A]>/",
            "/<[a|A][^>]+(data=\")+([^\"]+)\"[^>]*+>[^<]*+<\/[a|A]>/",
            "/<[img|IMG][^>]+(src=\")+([^\"]+)\"[^>]*+>/");
    $t = array('topic{data=$2}','$2','img{data=$2}');
    $html = preg_replace($p, $t, $html);
    $html   = strip_tags($html,"<br/>");
    return $html;
}

//解析数据成网页端显示格式
function parse_html($html)
{
    $html = htmlspecialchars_decode($html);
    //以下三个过滤是旧版兼容方法-可屏蔽
    $html = preg_replace("/img{data=([^}]*)}/"," ", $html);
    $html = preg_replace("/topic{data=([^}]*)}/",'<a href="$1" topic="true">#$1#</a>', $html);
    $html = preg_replace_callback("/@{uid=([^}]*)}/", "_parse_at_by_uid", $html);
    //链接替换
    $html = str_replace('[SITE_URL]',SITE_URL,$html);
    //外网链接地址处理
    //$html = preg_replace_callback('/((?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*\.)?[a-zA-Z0-9][a-zA-Z0-9\-]*(?:\.[a-zA-Z0-9]+)+(?:\:[0-9]*)?(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', '_parse_url', $html);
    //表情处理
    $html = preg_replace_callback("/(\[.+?\])/is",_parse_expression,$html);
    //话题处理
    $html = str_replace("＃", "#", $html);
    $html = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is",_parse_theme,$html);
    //@提到某人处理
    $html = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", "_parse_at_by_uname",$html);

    return $html;
}

//解析成api显示格式
function parseForApi($html)
{
    $html = h($html);
    //以下三个过滤是旧版兼容方法-可屏蔽
    $html = preg_replace_callback("/img{data=([^}]*)}/",'_parse_img_forapi', $html);
    $html = preg_replace_callback("/@{uid=([^}]*)}/", '_parse_wap_at_by_uname', $html);
    $html = preg_replace("/topic{data=([^}]*)}/",'#$1#', $html);
    $html = str_replace(array('[SITE_URL]','&nbsp;'),array(SITE_URL,' '),$html);
    //@提到某人处理
    $html = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", "_parse_wap_at_by_uname",$html);
    //敏感词过滤
    return $html;
}

/**
 * 格式化活动,替换话题
 * @param string  $content 待格式化的内容
 * @param boolean $url     是否替换URL
 * @return string
 */
function format($content,$url=false)
{
    $content = stripslashes($content);
    return $content;
}

function replaceTheme($content)
{
    $content = str_replace("＃", "#", $content);
    $content = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is",_parse_theme,$content);
    return $content;
}

function replaceUrl($content)
{
    //$content = preg_replace_callback('/((?:https?|ftp):\/\/(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)*(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', '_parse_url', $content);
    $content = str_replace('[SITE_URL]', SITE_URL, $content);
    $content = preg_replace_callback('/((?:https?|mailto|ftp):\/\/([^\x{2e80}-\x{9fff}\s<\'\"“”‘’，。}]*)?)/u', '_parse_url', $content);
    return $content;
}


/**
 * 表情替换 [格式化活动与格式化评论专用]
 * @param array $data
 */
function _parse_expression($data) 
{
    if (preg_match("/#.+#/i",$data[0])) 
    {
        return $data[0];
    }
    $allexpression = model('Expression')->getAllExpression();
    $info = $allexpression[$data[0]];
    if ($info) 
    {
        return preg_replace("/\[.+?\]/i","<img src='".__THEME__."/image/expression/miniblog/".$info['filename']."' />",$data[0]);
    }
    else 
    {
        return $data[0];
    }
}

/**
 * 格式化活动,替换链接地址
 * @param string $url
 */
function _parse_url($url)
{
    $str = '<div class="url">';
    if ( preg_match("/(youku.com|youtube.com|ku6.com|sohu.com|mofile.com|sina.com.cn|tudou.com|yinyuetai.com)/i", $url[0] , $hosts) )
    {
        $str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-video"></a>';
    } 
    else if ( strpos( $url[0] , 'taobao.com') )
    {
        $str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-taobao"></a>';
    } 
    else 
    {
        $str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-web"></a>';
    }
    $str .= '<div class="url-detail" style="display:none;">'.$url[0].'</div></div>';
    return $str;
}

/**
 * 话题替换 [格式化活动专用]
 * @param array $data
 * @return string
 */
function _parse_theme($data)
{
    //如果话题被锁定，则不带链接
    if (!model('FeedTopic')->where(array('name'=>$data[1]))->getField('lock'))
    {
        return "<a href=".U('public/Topic/index',array('k'=>urlencode($data[1]))).">".$data[0]."</a>";
    }
    else
    {
        return $data[0];
    }
}

/**
 * 根据用户昵称获取用户ID [格式化活动与格式化评论专用]
 * @param array $name
 * @return string
 */
function _parse_at_by_uname($name) 
{
    $info = static_cache( 'user_info_uname_'.$name[1]);
    if ( !$info)
    {
        $info = model( 'User')->getUserInfoByName($name[1]);
        if ( !$info )
        {
            $info = 1;
        }
        static_cache( 'user_info_uname_'.$name[1] , $info);
    }
    if ( $info && $info['is_active'] && $info['is_audit'] && $info['is_init'] ) 
    {
        return '<a href="'.$info['space_url'].'" uid="'.$info['uid'].'" event-node="face_card" target="_blank">'.$name[0]."</a>";
    }
    else 
    {
        return $name[0];
    }
}

/**
 * 解析at成web端显示格式
 */
function _parse_at_by_uid($result)
{
    $_userInfo = explode("|",$result[1]);
    $userInfo = model('User')->getUserInfo($_userInfo[0]);
    return '<a uid="'.$userInfo['uid'].'" event-node="face_card" data="@{uid='.$userInfo['uid'].'|'.$userInfo['uname'].'}" 
            href="'.$userInfo['space_url'].'">@'.$userInfo['uname'].'</a>';
}

function _parse_wap_at_by_uname($name) 
{
    $info = static_cache( 'user_info_uname_'.$name[1]);
    if ( !$info)
    {
        $info = model( 'User')->getUserInfoByName($name[1]);
        if ( !$info )
        {
            $info = 1;
        }
        static_cache( 'user_info_uname_'.$name[1] , $info);
    }
    if ( $info && $info['is_active'] && $info['is_audit'] && $info['is_init'] ) 
    {
        return '<a href="'.U('wap/Index/weibo',array('uid'=>$info['uid'])).'" >'.$name[0]."</a>";
    }
    else 
    {
        return $name[0];
    }
}

/**
 * 解析at成api显示格式
 */
function _parse_at_forapi($html)
{
    $_userInfo = explode("|",$html[1]);
    return "@".$_userInfo[1];
}

/**
 * 解析图片成api格式
 */
function _parse_img_forapi($html)
{
    $basename = basename($html[1]);
    return "[".substr($basename,0, strpos($basename, "."))."]";
}

/**
 * 敏感词过滤
 */
function filter_keyword($html)
{
    static $audit  =null;
    static $auditSet = null;
    if ($audit == null)
    { 
    	//第一次
        $audit = model('Xdata')->get('keywordConfig');
        $audit = explode(',',$audit);
        $auditSet =  model('Xdata')->get('admin_Config:audit');
    }
    // 不需要替换
    if (empty($audit) || $auditSet['open'] == '0')
    {
        return $html;
    }
    return str_replace($audit, $auditSet['replace'], $html);
}

//文件名
/**
 * 获取缩略图
 * @param unknown_type $filename 原图路劲、url
 * @param unknown_type $width 宽度
 * @param unknown_type $height 高
 * @param unknown_type $cut 是否切割 默认不切割
 * @return string
 */
function getThumbImage($filename,$width=100,$height='auto',$cut=false,$replace=false)
{
    $filename  = str_ireplace(UPLOAD_URL,'',$filename); //将URL转化为本地地址
    $info      = pathinfo($filename);
    $oldFile   = $info['dirname'].DIRECTORY_SEPARATOR.$info['filename'].'.'.$info['extension'];
    $thumbFile = $info['dirname'].DIRECTORY_SEPARATOR.$info['filename'].'_'.$width.'_'.$height.'.'.$info['extension'];

    $oldFile = str_replace('\\','/', $oldFile);
    $thumbFile = str_replace('\\','/',$thumbFile);

    $filename   = '/'.ltrim($filename,'/');
    $oldFile    = '/'.ltrim($oldFile,'/');
    $thumbFile  = '/'.ltrim($thumbFile,'/');

    //原图不存在直接返回
    if (!file_exists(UPLOAD_PATH.$oldFile))
    {
        @unlink(UPLOAD_PATH.$thumbFile);
        $info['src']    = $oldFile;
        $info['width']  = intval($width);
        $info['height'] = intval($height);
        return $info;
    //缩图已存在并且 replace替换为false
    }
    elseif (file_exists(UPLOAD_PATH.$thumbFile) && !$replace)
    {
        $imageinfo      = getimagesize(UPLOAD_PATH.$thumbFile);
        $info['src']    = $thumbFile;
        $info['width']  = intval($imageinfo[0]);
        $info['height'] = intval($imageinfo[1]);
        return $info;
    //执行缩图操作
    }
    else
    {
        $oldimageinfo     = getimagesize(UPLOAD_PATH.$oldFile);
        $old_image_width  = intval($oldimageinfo[0]);
        $old_image_height = intval($oldimageinfo[1]);
        if ($old_image_width<=$width && $old_image_height<=$height)
        {
            @unlink(UPLOAD_PATH.$thumbFile);
            @copy(UPLOAD_PATH.$oldFile,UPLOAD_PATH.$thumbFile);
            $info['src']    = $thumbFile;
            $info['width']  = $old_image_width;
            $info['height'] = $old_image_height;
            return $info;
        }
        else
        {
            //生成缩略图
            // tsload( ADDON_PATH.'/library/Image.class.php' );
            // if ($cut){
            //     Image::cut(UPLOAD_PATH.$filename, UPLOAD_PATH.$thumbFile, $width, $height);
            // }else{
            //     Image::thumb(UPLOAD_PATH.$filename, UPLOAD_PATH.$thumbFile, '', $width, $height);   
            // }
            //生成缩略图 - 更好的方法
            if ($height=="auto") $height=0;
            tsload(ADDON_PATH.'/library/phpthumb/ThumbLib.inc.php');
            $thumb = PhpThumbFactory::create(UPLOAD_PATH.$filename);
            if ($cut)
            {
                $thumb->adaptiveResize($width, $height);
            }
            else
            {
                $thumb->resize($width, $height);
            }
            $res = $thumb->save(UPLOAD_PATH.$thumbFile);
            //缩图失败
            if (!$res)
            {
                $thumbFile = $oldFile;
            }
            $info['width']  = $width;
            $info['height'] = $height;
            $info['src']    = $thumbFile;
            return $info;
        }
    }
}

//获取图片信息 - 兼容云
function getImageInfo($file)
{
    $cloud = model('CloudImage');
    if ($cloud->isOpen())
    {
        $imageInfo = getimagesize($cloud->getImageUrl($file));
    }
    else
    {
        $imageInfo = getimagesize(UPLOAD_PATH.'/'.$file);
    }
    return $imageInfo;
}

//获取图片地址 - 兼容云
function getImageUrl($file,$width='0',$height='auto',$cut=false,$replace=false)
{
    $cloud = model('CloudImage');
    if ($cloud->isOpen())
    {
        $imageUrl = $cloud->getImageUrl($file,$width,$height,$cut);
    }
    else
    {
        if ($width>0)
        {
            $thumbInfo = getThumbImage($file,$width,$height,$cut,$replace);
            $imageUrl = UPLOAD_URL.'/'.ltrim($thumbInfo['src'],'/');
        }
        else
        {
            $imageUrl = UPLOAD_URL.'/'.ltrim($file,'/');
        }
    }
    return $imageUrl;
}

//保存远程图片
function saveImageToLocal($url)
{
	if (strncasecmp($url,'http',4)!=0)
	{
		return false;
  	} 
  	$opts = array(
    	'http'=>array(
      	'method' => "GET",
      	'timeout' => 30, //超时30秒
      	'user_agent'=>"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)"
    	)
  	);
  	$context = stream_context_create($opts);
  	$file_content = file_get_contents($url, false, $context);
  	$file_path = date('/Y/md/H/');
  	@mkdir(UPLOAD_PATH.$file_path,0777,true);
  	$i = pathinfo($url);
  	if (!in_array($i['extension'],array('jpg','jpeg','gif','png')))
  	{
  		$i['extension'] = 'jpg';
  	}
  	$file_name = uniqid().'.'.$i['extension'];

  	//又拍云存储
  	$cloud = model('CloudImage');
  	if ($cloud->isOpen())
  	{
    	$res = $cloud->writeFile($file_path.$file_name,$file_content);
  	}
  	else
  	{
    	//本地存储
    	$res = file_put_contents(UPLOAD_PATH.$file_path.$file_name, $file_content);
  	}
  
  	if ($res)
  	{
    	return $file_path.$file_name;
  	}
  	else
  	{
    	return false;
  	}
}

function getImageUrlByAttachId($attachid,$width,$height)
{
    if ($attachInfo = model('Attach')->getAttachById($attachid))
    {
    	if ( $width )
    	{
        	return getImageUrl($attachInfo['save_path'].$attachInfo['save_name'],$width,$height,true);
    	} 
    	else 
    	{
    		return getImageUrl($attachInfo['save_path'].$attachInfo['save_name']);
    	}
    }
    else
    {
        return false;
    }
}
//获取附件地址 - 兼容云
function getAttachUrl($filename)
{
	//云端
	$cloud = model('CloudAttach');
	if ($cloud->isOpen())
	{
		return  $cloud->getFileUrl($filename);
	}
	//本地
	if (file_exists ( UPLOAD_PATH . '/' . $filename )) 
	{
		return UPLOAD_URL . '/' . $filename;
	} 
	else 
	{
		return '';
	}
}

function getAttachUrlByAttachId($attachid)
{
	if ($attachInfo = model('Attach')->getAttachById($attachid))
	{
		return getAttachUrl($attachInfo['save_path'].$attachInfo['save_name']);
	}
	else
	{
		return false;
	}
}

function getSiteLogo($logoid = '')
{
    if (empty($logoid))
    {
        $logoid = $GLOBALS['ts']['site']['site_logo'];
    }
    if ($logoInfo = model('Attach')->getAttachById($logoid))
    {
        $logo = getImageUrl($logoInfo['save_path'].$logoInfo['save_name']);
    }
    else
    {
        $logo = THEME_PUBLIC_URL.'/'.C('site_logo');
    }
    return $logo;
}

//获取当前访问者的客户端类型
function getVisitorClient()
{
    //客户端类型，0：网站；1：手机版；2：Android；3：iPhone；3：iPad；3：win.Phone
    return '0';
}

//获取一条活动的来源信息
function getFromClient($type=0, $app='public', $app_name)
{
    if ( $app != 'public' )
    {
    	$appUpper = strtoupper($app);
    	$appName = L('PUBLIC_APPNAME_'.$appUpper);
    	if (empty($app_name) && $appUpper!=$appName)
    	{
    		$app_name = $appName;
    	}
        return '来自<a href="'.U($app).'" target="_blank">'.$app_name."</a>";
    }
    $type = intval($type);
    $client_type = array(
        0 => '来自网站',
        1 => '来自手机',
        2 => '来自Android客户端',
        3 => '来自iPhone客户端',
        4 => '来自iPad客户端',
        5 => '来自Windows客户端',
    );

    //在列表中的
    if (in_array($type, array_keys( $client_type )))
    {
        return $client_type[$type];
    }
    else
    {
        return $client_type[0];
    }
}

/**
 * DES加密函数
 *
 * @param string $input
 * @param string $key
 */
function desencrypt($input,$key) 
{
    //使用新版的加密方式
    tsload(ADDON_PATH.'/library/DES_MOBILE.php');
    $desc = new DES_MOBILE();
    return $desc->setKey($key)->encrypt($input);
}

/**
 * DES解密函数
 *
 * @param string $input
 * @param string $key
 */
function desdecrypt($encrypted,$key) 
{
    //使用新版的加密方式
    tsload(ADDON_PATH.'/library/DES_MOBILE.php');
    $desc = new DES_MOBILE();
    return $desc->setKey($key)->decrypt($encrypted);
}


function getOAuthToken($uid)
{
    return md5( $uid . uniqid() );
}

function getOAuthTokenSecret()
{
    return md5( time() . uniqid() );
}

// 获取字串首字母
function getFirstLetter($s0) 
{
    $firstchar_ord = ord(strtoupper($s0{0}));
    if ($firstchar_ord >= 65 and $firstchar_ord <= 91) return strtoupper($s0{0});
    if ($firstchar_ord >= 48 and $firstchar_ord <= 57) return '#';
    $s = iconv("UTF-8", "gb2312", $s0);
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc>=-20319 and $asc<=-20284) return "A";
    if ($asc>=-20283 and $asc<=-19776) return "B";
    if ($asc>=-19775 and $asc<=-19219) return "C";
    if ($asc>=-19218 and $asc<=-18711) return "D";
    if ($asc>=-18710 and $asc<=-18527) return "E";
    if ($asc>=-18526 and $asc<=-18240) return "F";
    if ($asc>=-18239 and $asc<=-17923) return "G";
    if ($asc>=-17922 and $asc<=-17418) return "H";
    if ($asc>=-17417 and $asc<=-16475) return "J";
    if ($asc>=-16474 and $asc<=-16213) return "K";
    if ($asc>=-16212 and $asc<=-15641) return "L";
    if ($asc>=-15640 and $asc<=-15166) return "M";
    if ($asc>=-15165 and $asc<=-14923) return "N";
    if ($asc>=-14922 and $asc<=-14915) return "O";
    if ($asc>=-14914 and $asc<=-14631) return "P";
    if ($asc>=-14630 and $asc<=-14150) return "Q";
    if ($asc>=-14149 and $asc<=-14091) return "R";
    if ($asc>=-14090 and $asc<=-13319) return "S";
    if ($asc>=-13318 and $asc<=-12839) return "T";
    if ($asc>=-12838 and $asc<=-12557) return "W";
    if ($asc>=-12556 and $asc<=-11848) return "X";
    if ($asc>=-11847 and $asc<=-11056) return "Y";
    if ($asc>=-11055 and $asc<=-10247) return "Z";
    return '#';
}

// 区间调试开始
function debug_start($label='')
{
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

// 区间调试结束，显示指定标记到当前位置的调试
function debug_end($label='')
{   
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    $log =  'Process '.$label.': Times '.number_format($GLOBALS[$label]['_endTime']-$GLOBALS[$label]['_beginTime'],6).'s ';
    $GLOBALS[$label]['_endMem'] = memory_get_usage();
    $log .= ' Memories '.number_format(($GLOBALS[$label]['_endMem']-$GLOBALS[$label]['_beginMem'])/1024).' k';
    $GLOBALS['logs'][$label] = $log;
} 

// 全站语言设置 - PHP
function setLang() 
{
    // 获取当前系统的语言
    $lang = getLang();
    // 设置全站语言变量
    if (!isset($GLOBALS['_lang'])) 
    {
        $GLOBALS['_lang'] = array();
        $_lang = array();
        if (file_exists(LANG_PATH.'/public_'.$lang.'.php')) 
        {
            $_lang = include(LANG_PATH.'/public_'.$lang.'.php');
            $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
        }
        $removeApps = array('api', 'widget', 'public');
        if (!in_array(TRUE_APPNAME, $removeApps)) 
        {
            if (file_exists(LANG_PATH.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.php')) 
            {
                $_lang = include(LANG_PATH.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.php');
                $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
            }
        }
    }
}

//主动添加语言包
function addLang($appname)
{
    static $langHash = array();
    if (isset($langHash[$appname]))
    {
        return true;
    }
    $langHash[$appname] = 1;
    $lang = getLang();
    if (file_exists(LANG_PATH.'/'.$appname.'_'.$lang.'.php'))
    {
        $_lang = include(LANG_PATH.'/'.$appname.'_'.$lang.'.php');
        empty($_lang) && $_lang = array();
        $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
        return true;
    }
    return false;
}

// 全站语言设置 - JavaScript
function setLangJavsScript() 
{
    // 获取当前系统的语言
    $lang = getLang();
    // 获取相应要载入的JavaScript语言包路径
    $langJsList = array();
    if (file_exists(LANG_PATH.'/public_'.$lang.'.js')) 
    {
        $langJsList[] = LANG_URL.'/public_'.$lang.'.js';
    }
    $removeApps = array('api', 'widget', 'public');
    if (!in_array(TRUE_APPNAME, $removeApps)) 
    {
        if (file_exists(LANG_PATH.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.js')) 
        {
            $langJsList[] = LANG_URL.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.js';
        }
    }

    return $langJsList;
}

// 获取站点所使用的语言
function getLang() 
{
    $defaultLang = 'zh-cn';
    $cLang = cookie('lang');
    $lang = '';
    // 判断是否已经登录
    if (isset($_SESSION['mid']) && $_SESSION['mid']>0)
    {
        $userInfo = model('User')->getUserInfo($_SESSION['mid']);
        if (isset($userInfo['lang']))
        {
            return $userInfo['lang'];
        }else
        {
            return '';
        }
    }
    // 是否存在cookie值，如果存在显示默认的cookie语言值
    if (is_null($cLang)) 
    {
        // 手机端直接返回默认语言
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) 
        {
            return $defaultLang;
        }
        // 判断操作系统的语言状态
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $accept_language = strtolower($accept_language);
        $accept_language_array = explode(',', $accept_language);
        $lang = array_shift($accept_language_array);
        // 获取默认语言
        $fields = model('Lang')->getLangType();
        $lang = in_array($lang, $fields) ? $lang : $defaultLang;
        cookie('lang', $lang);
    } 
    else 
    {
        $lang = $cLang;
    }

    return $lang;
}

function ShowNavMenu($apps)
{
    $html = '';
    foreach($apps as $app)
    {
        $child_menu = unserialize($app['child_menu']);
        if (empty($child_menu))
        {
            continue;
        }
        foreach($child_menu as $k=>$cm)
        {
            if ($k == $app['app_name'])
            {
                //我的XXX
                $title = L('PUBLIC_MY').L('PUBLIC_APPNAME_'.strtoupper($k));
                $url = U($cm['url']);
            }
            else
            {
                //其他导航 一般不会有其他导航
                $title = L($k);
                //地址直接是cm值
                $url = U($cm);
            }
            
            $html .="<dd><a href='{$url}'>{$title}</a></dd>";    
        }
    }
    return $html;
}

function showNavProfile($apps)
{
    $html = '';
    foreach($apps as $app)
    {
        $child_menu = unserialize($app['child_menu']);

        if (empty($child_menu))
        {
            continue;
        }
        foreach($child_menu as $k=>$cm)
        {
            if ($k == $app['app_name'] && $cm['public'] == 1)
            {
                //我的XXX 只会显示这类数据
                $title = "<img width='16' src='{$app['icon_url']}'> ".L('PUBLIC_APPNAME_'.strtoupper($k));
                $url = U('public/Profile/appprofile',array('appname'=>$k));    
                $html .="<dd class='profile_{$app['app_name']}'><a href='{$url}'>{$title}</a></dd>";    
            }
        }
    }
    return $html;   
}

/**
 * 是否能进行邀请
 * @param integer $uid 用户ID
 */
function isInvite()
{
    $config = model('Xdata')->get('admin_Config:register');
    $result = false;
    if (in_array($config['register_type'], array('open', 'invite'))) 
    {
        $result = true;
    }
    return $result;
}

/**
 * 传统形式显示无限极分类树
 * @param array $data 树形结构数据
 * @param string $stable 所操作的数据表
 * @param integer $left 样式偏移
 * @param array $delParam 删除关联信息参数，app、module、method
 * @param integer $level 添加子分类层级，默认为0，则可以添加无限子分类
 * @param integer $times 用于记录递归层级的次数，默认为1，调用函数时，不需要传入值。
 * @param integer $limit 分类限制字数。
 * @return string 树形结构的HTML数据
 */
function showTreeCategory($data, $stable, $left, $delParam, $level = 0, $ext = '', $times = 1, $limit = 0) 
{
    $html = '<ul class="sort">';
    foreach($data as $val) 
    {
        // 判断是否有符号
        $isFold = empty($val['child']) ? false : true;
        $html .= '<li id="'.$stable.'_'.$val['id'].'" class="underline" style="padding-left:'.$left.'px;"><div class="c1">';
        if ($isFold) 
        {
            $html .= '<a href="javascript:;" onclick="admin.foldCategory('.$val['id'].')"><img id="img_'.$val['id'].'" src="'.__THEME__.'/admin/image/on.png" /></a>';
        }
        $html .= '<span>'.$val['title'].'</span></div><div class="c2">';
        if ($level == 0 || $times < $level) 
        {
            $html .= '<a href="javascript:;" onclick="admin.addTreeCategory('.$val['id'].', \''.$stable.'\', '.$limit.');">添加子分类</a>&nbsp;-&nbsp;';
        }
        $html .= '<a href="javascript:;" onclick="admin.upTreeCategory('.$val['id'].', \''.$stable.'\', '.$limit.');">编辑</a>&nbsp;-&nbsp;';
        if (empty($delParam)) 
        {
            $html .= '<a href="javascript:;" onclick="admin.rmTreeCategory('.$val['id'].', \''.$stable.'\');">删除</a>';
        } 
        else 
        {
            $html .= '<a href="javascript:;" onclick="admin.rmTreeCategory('.$val['id'].', \''.$stable.'\', \''.$delParam['app'].'\', \''.$delParam['module'].'\', \''.$delParam['method'].'\');">删除</a>';
        }
        $ext !== '' && $html .= '&nbsp;-&nbsp;<a href="'.U('admin/Public/setCategoryConf', array('cid'=>$val['id'], 'stable'=>$stable)).'&'.$ext.'">分类配置</a>';
        $html .= '</div><div class="c3">';
        $html .= '<a href="javascript:;" onclick="admin.moveTreeCategory('.$val['id'].', \'up\', \''.$stable.'\')" class="ico_top mr5"></a>';
        $html .= '<a href="javascript:;" onclick="admin.moveTreeCategory('.$val['id'].', \'down\', \''.$stable.'\')" class="ico_btm"></a>';
        $html .= '</div></li>';
        if (!empty($val['child'])) 
        {
            $html .= '<li id="sub_'.$val['id'].'" style="display:none;">';
            $html .= showTreeCategory($val['child'], $stable, $left + 15, $delParam, $level, $ext, $times + 1, $limit);
            $html .= '</li>';
        } 
    }
    $html .= '</ul>';

    return $html;
}

/**
 * 格式化分类配置页面参数为字符串
 * @param array $ext 配置页面相关参数
 * @param array $defExt 默认值HASH数组
 * @return string 格式化后的字符串
 */
function encodeCategoryExtra($ext, $defExt)
{
    $data = array();
    $i = 1;
    foreach ($ext as $key => $val) 
    {
        if (is_array($val)) 
        {
            $data['ext_'.$i] = $key;
            $data['arg_'.$i] = implode('-', $val);
            $data['def_'.$i] = $defExt[$key];
        } 
        else 
        {
            $data['ext_'.$i] = $val;
        }
        $i++;
    }
    // 处理数据
    $result = array();
    foreach ($data as $k => $v) 
    {
        $result[] = $k.'='.urlencode($v);
    }

    return implode('&', $result);
}

/**
 * 返回解析空间地址
 * @param integer $uid 用户ID
 * @param string $class 样式类
 * @param string $target 是否进行跳转
 * @param string $text 标签内的相关内容
 * @param boolen $icon 是否显示用户组图标，默认为true
 * @return string 解析空间地址HTML
 */
function getUserSpace($uid, $class, $target, $text, $icon = true)
{
    // 2.8转移
    // 静态变量
    static $_userinfo = array();
    // 判断是否有缓存
    if (!isset($_userinfo[$uid])) 
    {
        $_userinfo[$uid] = model('User')->getUserInfo($uid);
    }
    // 配置相关参数
    empty($target) && $target = '_self';
    empty($text) && $text = $_userinfo[$uid]['uname'];
    // 判断是否存在替换信息
    preg_match('|{(.*?)}|isU', $text, $match);
    if ($match) 
    {
        if ($match[1] == 'uname') 
        {
            $text = str_replace('{uname}', $_userinfo[$uid]['uname'], $text);
            //empty($class) && $class = 'username';  //2013/2/28  wanghaiquan
            empty($class) && $class = 'name';
        } 
        else 
        {
            preg_match("/{uavatar}|{uavatar\\=(.*?)}/e", $text, $face_type);
            switch ($face_type[1]) 
            {
		        case 'b':
		            $userface = 'big';
		            break;
		        case 'm':
		            $userface = 'middle';
		            break;
		        default:
		            $userface = 'small';
		            break;
    		}
            $face = $_userinfo[$uid]['avatar_'.$userface];
            $text = '<img src="'.$face.'" />';
            empty($class) && $class = 'userface';
            $icon = false;
        }
    }
    // 组装返回信息
    $user_space_info = '<a event-node="face_card" uid="'.$uid.'" href="'.$_userinfo[$uid]['space_url'].'" class="'.$class.'" target="'.$target.'">'.$text.'</a>';
    // 用户认证图标信息
    if ($icon) 
    {
        $group_icon = array();
        $user_group = static_cache( 'usergrouplink_'.$uid );
        if ( !$user_group )
        {
            $user_group = model('UserGroupLink')->getUserGroupData($uid);
            static_cache( 'usergrouplink_'.$uid , $user_group );
        }
        if (!empty($user_group)) 
        {
            foreach($user_group[$uid] as $value) 
            {
                $group_icon[] = '<img title="'.$value['user_group_name'].'" src="'.$value['user_group_icon_url'].'" class="space-group-icon" />';
            }
            $user_space_info .= '&nbsp;'.implode('&nbsp;', $group_icon);
        }
    }

    return $user_space_info;
}

/**
* 检查是否是以手机浏览器进入(IN_MOBILE)
*/
function isMobile() 
{
    $mobile = array();
    static $mobilebrowser_list ='Mobile|iPhone|Android|WAP|NetFront|JAVA|OperasMini|UCWEB|WindowssCE|Symbian|Series|webOS|SonyEricsson|Sony|BlackBerry|Cellphone|dopod|Nokia|samsung|PalmSource|Xphone|Xda|Smartphone|PIEPlus|MEIZU|MIDP|CLDC';
    //note 获取手机浏览器
    if (preg_match("/$mobilebrowser_list/i", $_SERVER['HTTP_USER_AGENT'], $mobile)) 
    {
        return true;
    }
    else
    {
        if (preg_match('/(mozilla|chrome|safari|opera|m3gate|winwap|openwave)/i', $_SERVER['HTTP_USER_AGENT'])) 
        {
            return false;
        }
        else
        {
            if ($_GET['mobile'] === 'yes') 
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
}

function isiPhone()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false;
}

function isiPad()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false;
}

function isiOS()
{
    return isiPhone() || isiPad();
}

function isAndroid()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false;
}

/**
 * 获取用户浏览器型号。新加浏览器，修改代码，增加特征字符串.把IE加到12.0 可以使用5-10年了.
 */
function getBrowser()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Maxthon')) 
    {
        $browser = 'Maxthon';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 12.0')) 
    {
        $browser = 'IE12.0';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 11.0')) 
    {
        $browser = 'IE11.0';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 10.0')) 
    {
        $browser = 'IE10.0';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.0')) 
    {
        $browser = 'IE9.0';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')) 
    {
        $browser = 'IE8.0';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) 
    {
        $browser = 'IE7.0';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) 
    {
        $browser = 'IE6.0';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'NetCaptor')) 
    {
        $browser = 'NetCaptor';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Netscape')) 
    {
        $browser = 'Netscape';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Lynx')) 
    {
        $browser = 'Lynx';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera')) 
    {
        $browser = 'Opera';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) 
    {
        $browser = 'Google';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) 
    {
        $browser = 'Firefox';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) 
    {
        $browser = 'Safari';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'iphone') || strpos($_SERVER['HTTP_USER_AGENT'], 'ipod')) 
    {
        $browser = 'iphone';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'ipad')) 
    {
        $browser = 'iphone';
    } 
    elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'android')) 
    {
        $browser = 'android';
    } 
    else 
    {
        $browser = 'other';
    }
    return $browser;
}


/* TS2.X的兼容方法 */
function safe($text)
{
    return h($text);
}

function text($text)
{
    return t($text);
}

function real_strip_tags($str, $allowable_tags="") 
{
    $str = html_entity_decode($str,ENT_QUOTES,'UTF-8');
    return strip_tags($str, $allowable_tags);
}

function getUserFace($uid,$size)
{
    $userinfo = model('User')->getUserInfo($uid);
    switch ($size) 
    {
        case 'b':
            $userface = $userinfo['avatar_big'];
            break;
        case 'm':
            $userface = $userinfo['avatar_middle'];
            break;
        default:
            $userface = $userinfo['avatar_small'];
            break;
    }
    return $userface;
}

function getUserName($uid)
{
    $userinfo = model('User')->getUserInfo($uid);
    return $userinfo['uname'];
}

function keyWordFilter($text)
{
    return filter_keyword($text);
}

function getFollowState($uid,$fid,$type=0) 
{
    if ($uid <= 0 || $fid <= 0)
    {
    	return 'unfollow';
    }
    if ($uid==$fid)
    {
    	return 'unfollow';
    }
    if (M('user_follow')->where("(uid=$uid AND fid=$fid) OR (uid=$fid AND fid=$uid)")->count() == 2) 
    {
        return 'eachfollow';
    }
    else if ( M('user_follow')->where("uid=$uid AND fid=$fid")->count()) 
    {
        return 'havefollow';
    }
    else 
    {
        return 'unfollow';
    }
}

function matchImages($content = '') 
{
    $src = array ();
    preg_match_all ( '/<img.*src=(.*)[>|\\s]/iU', $content, $src );
    if (count ( $src [1] ) > 0) 
    {
        foreach ( $src [1] as $v ) 
        {
            $images [] = trim ( $v, "\"'" ); //删除首尾的引号 ' "
        }
        return $images;
    } 
    else 
    {
        return false;
    }
}

function matchReplaceImages($content = '')
{
    $image = preg_replace_callback('/<img.*src=(.*)[>|\\s]/iU',"matchReplaceImagesOnce",$content);
    return $image;
}

function matchReplaceImagesOnce($matches)
{
    $matches[1] = str_replace('"','',$matches[1]);
    return sprintf("<a class='thickbox'  href='%s'>%s</a>",$matches[1],$matches[0]);
}

//加密函数
function jiami($txt, $key = null) 
{
    if (empty ( $key ))
    {
    	$key = C ( 'SECURE_CODE' );
    }
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
    $nh = rand ( 0, 64 );
    $ch = $chars [$nh];
    $mdKey = md5 ( $key . $ch );
    $mdKey = substr ( $mdKey, $nh % 8, $nh % 8 + 7 );
    $txt = base64_encode ( $txt );
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for($i = 0; $i < strlen ( $txt ); $i ++) 
    {
        $k = $k == strlen ( $mdKey ) ? 0 : $k;
        $j = ($nh + strpos ( $chars, $txt [$i] ) + ord ( $mdKey [$k ++] )) % 64;
        $tmp .= $chars [$j];
    }
    return $ch . $tmp;
}

//解密函数
function jiemi($txt, $key = null) 
{
    if (empty ( $key ))
    {
    	$key = C ( 'SECURE_CODE' );
    }
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
    $ch = $txt [0];
    $nh = strpos ( $chars, $ch );
    $mdKey = md5 ( $key . $ch );
    $mdKey = substr ( $mdKey, $nh % 8, $nh % 8 + 7 );
    $txt = substr ( $txt, 1 );
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for($i = 0; $i < strlen ( $txt ); $i ++) 
    {
        $k = $k == strlen ( $mdKey ) ? 0 : $k;
        $j = strpos ( $chars, $txt [$i] ) - $nh - ord ( $mdKey [$k ++] );
        while ( $j < 0 )
        {
        	$j += 64;
        }
        $tmp .= $chars [$j];
    }
    return base64_decode ( $tmp );
}


//******************************************************************************
// 转移应用添加函数
/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其它编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function mStr($str, $length, $charset="utf-8", $suffix=true)
{
    return msubstr($str, 0, $length, $charset, $suffix);
}
/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其它编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) 
{
    if (function_exists("mb_substr"))
    {
    	$slice = mb_substr($str, $start, $length, $charset);
    }
    elseif (function_exists('iconv_substr')) 
    {
        $slice = iconv_substr($str,$start,$length,$charset);
    }
    else
    {
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    if ($suffix && $str != $slice) 
    {
    	return $slice."...";
    }
    return $slice;
}
// // 获取给定用户的用户组图标
// function getUserGroupIcon($uid)
// {
//     static $_var = array();
//     if (!isset($_var[$uid]))
//     {
//         $_var[$uid] = model('UserGroup')->getUserGroupIcon($uid);
//     }
//     return $_var[$uid];
// }

/**
 * 检查Email地址是否合法
 *
 * @return boolean
 */
function isValidEmail($email) 
{
    return preg_match("/^[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/i", $email) !== 0;
}
// 发送常用http header信息
function send_http_header($type='utf8')
{
	//utf8,html,wml,xml,图片、文档类型 等常用header
	switch($type)
	{
		case 'utf8':
			header("Content-type: text/html; charset=utf-8");
			break;
		case 'xml':
			header("Content-type: text/xml; charset=utf-8");
			break;
	}
}
/**
 * 判断作者
 * @param unknown_type $dao
 * @param unknown_type $field
 * @param unknown_type $id
 * @param unknown_type $user
 * @return boolean
 */
function CheckAuthorPermission( $dao , $id , $field='id' , $getfield='uid')
{
	$map[$field] = $id;
	$value = $dao->where($map)->getField($getfield);
	return $value == $GLOBALS['ts']['mid'];
}
/**
 * 锁定表单
 *
 * @param int $life_time 表单锁的有效时间(秒). 如果有效时间内未解锁, 表单锁自动失效.
 * @return boolean 成功锁定时返回true, 表单锁已存在时返回false
 */
function lockSubmit($life_time = null) 
{
	if ( isset($_SESSION['LOCK_SUBMIT_TIME']) && intval($_SESSION['LOCK_SUBMIT_TIME']) > time() ) 
	{
		return false;
	}
	else 
	{
		$life_time = $life_time ? $life_time : 10;
		$_SESSION['LOCK_SUBMIT_TIME'] = time() + intval($life_time);
		return true;
	}
}

/**
 * 检查表单是否已锁定
 *
 * @return boolean 表单已锁定时返回true, 否则返回false
 */
function isSubmitLocked() 
{
	return isset($_SESSION['LOCK_SUBMIT_TIME']) && intval($_SESSION['LOCK_SUBMIT_TIME']) > time();
}

/**
 * 表单解锁
 *
 * @return void
 */
function unlockSubmit() 
{
	unset($_SESSION['LOCK_SUBMIT_TIME']);
}

/**
 * 获取给定IP的物理地址
 *
 * @param string $ip
 * @return string
 */
function convert_ip($ip) 
{
    $return = '';
    if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) 
    {
        $iparray = explode('.', $ip);
        if ($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) 
        {
            $return = '- LAN';
        } elseif ($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) 
        {
            $return = '- Invalid IP Address';
        } 
        else 
        {
            $tinyipfile = ADDON_PATH . '/libs/misc/tinyipdata.dat';
            $fullipfile = ADDON_PATH . '/libs/misc/wry.dat';
            if (@file_exists($tinyipfile)) 
            {
                $return = convert_ip_tiny($ip, $tinyipfile);
            } 
            elseif (@file_exists($fullipfile)) 
            {
                $return = convert_ip_full($ip, $fullipfile);
            }
        }
    }
    $return = iconv('GBK', 'UTF-8', $return);
    return $return;
}

/**
 * 格式化活动内容中url内容的长度
 * @param string $match 匹配后的字符串
 * @return string 格式化后的字符串
 */
function _format_feed_content_url_length ($match) 
{
    static $i = 97;
    $result = '{tsurl=='.chr($i).'}';
    $i++;
    $GLOBALS['replaceHash'][$result] = $match[0];
    return $result;
}

function format_array_intval ($str) 
{
    if (!is_string($str)) 
    {
        die('Parameter is not string ');
    }
    $arr = explode(',', $str);
    $arr = array_filter($arr);
    $arr = array_unique($arr);
    $arr = array_map('intval', $arr);

    return $arr;
}