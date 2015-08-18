<?php
/**
 * @name:	模板管理
 * @brief:	用于管理模板
 * @author: vincent
 * @create:	2014-5-25
 * @update:	2014-5-25
 *
 * @type:	system
 */
class Module_View_Template
{
    const STATIC_FILE_VERSION = 20141014;

    /**
     * @param string $val
     * @param string $path
     * @param array $perm
     * @param array $property
     * @return string
     */
    static function link_to($val, $path, $perm = [], $property = [])
    {
        $link = '';
        if (is_array($perm) && Module_Account_User::has_perms($perm)) {
            $link .= '<a href = "'.Module_HttpRequest_Router::site_url($path).'"';
            is_array($property)
                && $link .= ' '.Lib_Helper::join_and_wrap_array_key_and_val($property, ' ', '=', '', '"');
            $link .= '>'.$val.'</a>';
        }
        return $link;
    }

    /**
     * @param string $val
     * @param array $perm
     * @param array $property
     * @return string
     */
    static function button($val, $perm = [], $property = [])
    {
        $btn = '';
        if (empty($perm) || Module_Account_User::has_perms($perm)) {
            $btn .= '<button type="button"';
            is_array($property)
                && $btn .= ' '.Lib_Helper::join_and_wrap_array_key_and_val($property, ' ', '=', '', '"');
            $btn .= '>'.$val.'</a>';
        }
        return $btn;
    }

    /**
     * @param string $filename
     * @return string
     */
    static function static_file($filename)
    {
        $host = ($_SERVER['HTTP_HOST']);
        $file = 'http://'.$host . '/' 
            . $filename.'?version='.self::STATIC_FILE_VERSION;
        return $file;
    }

    /**
     * @return int
     */
    static function static_file_version()
    {
        return self::STATIC_FILE_VERSION;
    }

    /**
     * @param string $file
     * @param string $path
     */
    static function require_js($file, $path = 'js')
    {
        if (is_array($file)) {
            foreach ($file as $_f) {
                $_f = self::static_file($path.'/'.$_f);
                echo '<script type="text/javascript" src="'.$_f.'"></script>'.PHP_EOL;
            }
        } else {
            $file = self::static_file($path.'/'.$file);
            echo '<script type="text/javascript" src="'.$file.'"></script>'.PHP_EOL;
        }
    }

    /**
     * @param string $file
     * @param string $path
     */
    static function require_css($file, $path = 'css')
    {
        if (is_array($file)) {
            foreach ($file as $_f) {
                $_f = self::static_file($path.'/'.$_f);
                echo '<link href="'.$_f.'" type="text/css" rel="stylesheet" />'.PHP_EOL;
            }
        } else {
            $file = self::static_file($path.'/'.$file);
            echo '<link href="'.$file.'" type="text/css" rel="stylesheet" />'.PHP_EOL;
        }
    }

    /**
     * @param array $nav_array
     * @param bool $check_show
     * @return array
     */
    static function check_nav_perm($nav_array, $check_show = true)
    {
        foreach ($nav_array as $_k => $_nav) {
            if (
                $check_show
                && isset($_nav['show'])
                && $_nav['show'] === false
            ) {
                unset($nav_array[$_k]);
                continue;
            }
            if (
                isset($_nav['perm'])
            ) {
                if (!Module_Account_User::has_perms($_nav['perm'])) {
                    unset($nav_array[$_k]);
                    continue;
                }
            }
            if (isset($_nav['children'])) {
                if (empty($_nav['children'])) {
                    unset($nav_array[$_k]['children']);
                    continue;
                }
                $c_nav = self::check_nav_perm($_nav['children']);
                $count = 0;
                foreach ($c_nav as $_n) {
                    $_n !== null && $count++;
                }
                if ($count == 0) {
                    unset($nav_array[$_k]);
                    continue;
                } elseif ($count == 1) {
                    $nav_array[$_k] = reset($c_nav);
                    $nav_array[$_k]['name'] = $_nav['name'];
                } else {
                    $nav_array[$_k]['children'] = $c_nav;
                }
            }
        }
        return $nav_array;
    }

    /**
     * @param array $nav_array
     * @return string
     */
    static function generate_nav_li($nav_array)
    {
        $nav_array = self::check_nav_perm($nav_array);
        $nav_html = '';
        foreach ($nav_array as $_nav) {
            if ($_nav === null) {
                $nav_html .= '<li class = "divider"></li>';
                continue;
            }
            $_name = $_nav['name'];
            $_path = $_nav['path'];            
            if (isset($_nav['children'])) {
                $nav_html_children = self::generate_nav_li($_nav['children']);
                if(empty($nav_html_children)) {
                    unset($_nav['children']);
                    $nav_html .= self::generate_nav_li([$_nav]);
                } else {
                    $p_nav_html = '<li class="dropdown';
                    $p_nav_html .= self::_in_nav_list_paths($_nav['children'])  ? ' active' : '';
                    $p_nav_html .= '">';
                    $p_nav_html .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">';
                    $p_nav_html .= $_name;
                    $p_nav_html .= '<b class="caret"></b></a>';
                    $p_nav_html .= '<span class="dropdown-arrow"></span><ul class="dropdown-menu">';
                    $p_nav_html .= $nav_html_children;
                    $p_nav_html .= '</ul></li>';
                    $nav_html .= $p_nav_html;
                }
            } else {
                $nav_html .= '<li ';
                $nav_html .= Module_HttpRequest_Router::is_current_url_path($_path) ? 'class = "active"' : '' ;
                $nav_html .= '>';
                $nav_html .= '<a href="'. Module_HttpRequest_Router::site_url($_path) .'">';
                $nav_html .= $_name;
                $nav_html .= '</a></li>';
            }
        }
        return $nav_html;
    }

    /**
     * @param array $navs
     * @return bool
     */
    static function _in_nav_list_paths($navs)
    {
        $paths = [];
        foreach($navs as $_nav) {
            if (is_null($_nav)) {
                continue;
            }
            $_path = $_nav['path'];
            if (is_array($_path)) {
                if (self::_in_nav_list_paths($_path)) {
                    return true;
                }
            } else {
                $paths[] = $_path;
            }
        }
        $result = Module_HttpRequest_Router::is_current_url_path($paths);
        return $result;
    }

    /**
     * @param array $nav_array
     * @return array
     */
    static function get_crumb($nav_array)
    {
        $crumb = [];
        foreach ($nav_array as $_nav) {
            $_path = $_nav['path'];
            if (isset($_nav['children'])) {
                $_children = $_nav['children'];
                unset($_nav['children']);
                $children_crumb = self::get_crumb($_children);
                if (isset($children_crumb[0])) {
                    $crumb = array_merge([$_nav], $children_crumb);
                    break;
                } else {
                    $crumb = self::get_crumb([$_nav]);
                }
            } else {
                if (Module_HttpRequest_Router::is_current_url_path($_path)) {
                    $crumb[] = $_nav;
                    break;
                }
            }
        }
        return $crumb;
    }

    /**
     * @param int $current_page
     * @param int $total_size
     * @param int $page_size
     * @param int $mode
     * @return string
     */
    public static function get_pages_html($current_page, $total_size, $page_size = 10, $mode = 1)
    {
        $get_vars = Lib_Request::get_vars();
        $querystr = '';
        if(isset($get_vars['page'])){
            unset($get_vars['page']);
        }
        if(!empty($get_vars)) {
            foreach($get_vars as $k=>$v) {
                if(empty($v)) {
                    continue;
                }
                $querystr .= $k."=".$v."&";
            }
        }
        $mode = $mode > 0? $mode:1;
        $num = $mode*2+1;
        $current_page = $current_page<1? 1:$current_page;
        $current_url = Module_HttpRequest_Router::current_url_path();
        $current_url .= "?".$querystr;
        $pages_html = '';
        $total_pages = ceil($total_size/$page_size);
        $current_page = $current_page>$total_pages? $total_pages:$current_page;

        $pages_html .= '<ul class="pagination"><li><span>总计'.$total_size.'条记录&nbsp; 共'.$total_pages.'页</span></li></ul>&nbsp; ';
        if($total_size < $page_size) {
            return $pages_html;
        }
        $pages_html .= '<ul class="pagination">';
        //
        if($current_page <= 1) {
            $pages_html .= '<li class="disabled"><span>上一页</span></li>';
        } else {
            $pages_html .= '<li><a href="'.$current_url.'page='.($current_page-1).'">上一页</a></li>';
        }
        //
        if($total_pages <= ($num+2)) {
            for($i = 1; $i <= $total_pages; $i++) {
                $pages_html .= $i == $current_page? '<li class="active"><span>'.$i.'</span></li>':'<li><a href="'.$current_url.'page='.$i.'">'.$i.'</a></li>';
            }

        } else {
            $page_start = $current_page-$mode;
            $page_start = $page_start < 2? 2:$page_start;
            $pages_html .= $current_page == 1? '<li class="active"><span>1</span></li>':'<li><a href="'.$current_url.'page=1">1</a></li>';
            if($page_start > 2 && $total_pages > $num) {
                $pages_html .= '<li><span>...</span></li>';
            }
            $num = $page_start+$num;
            for($i = $page_start; $i < $num; $i++) {
                if($i > $total_pages) {
                    break;
                }
                $pages_html .= $i == $current_page? '<li class="active"><span>'.$i.'</span></li>':'<li><a href="'.$current_url.'page='.$i.'">'.$i.'</a></li>';
            }
            if($num < $total_pages) {
                $pages_html .= '<li><span>...</span></li>';
            }
            if($num <= $total_pages) {
                $pages_html .= $current_page == $total_pages? '<li class="active"><span>'.$total_pages.'</span></li>':'<li><a href="'.$current_url.'page='.$total_pages.'">'.$total_pages.'</a></li>';
            }
        }
        //
        if($current_page >= $total_pages) {
            $pages_html .= '<li class="disabled"><span>下一页</span></li>';
        } else {
            $pages_html .= '<li><a href="'.$current_url.'page='.($current_page+1).'">下一页</a></li>';
        }
        $pages_html .= '</ul>';

        return $pages_html;
    }
}
