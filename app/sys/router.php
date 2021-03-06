<?php
namespace Da;

class Sys_Router
{
    use \singleton_with_get_instance;

    const ERRNO_OK = 200;
    const ERRNO_FORBIDDEN = 403;
    const ERRNO_NOT_FOUND = 404;
    const ERRNO_SERVER_ERR = 500;

    protected $_router = [__CLASS__, '_default_router'];

    /**
     * @return array The return value should include 'errno' and 'data'
     */
    function parse_url()
	{
        $request_uri = trim($_SERVER['REQUEST_URI']);
        $request_uri = trim($request_uri, '/');
		/* parse request uri start
		 * -----------------------------------
		 */
		$p           = strpos($request_uri, '?');
		$request_uri = $p !== false ? substr($request_uri, 0, $p) : $request_uri;
		// security request uri filter
		if (preg_match('/(\.\.|\"|\'|<|>)/', $request_uri)) {
			return array('errno' => self::ERRNO_FORBIDDEN, 'data' => "permission denied.");
		}

		// get display format
		if (($p = strrpos($request_uri, '.')) !== false) {
			$tail = substr($request_uri, $p + 1);
			if(preg_match('/^[a-zA-Z0-9]+$/', $tail)) {
				$display  = $tail; //'json'
				$request_uri = substr($request_uri, 0, $p);
			}
		}
        $url_piece = array_pad(explode('/', $request_uri, 5), 5, null);
        return ['errno' => self::ERRNO_OK, 'data' => $url_piece];
	}

    /**
     * @param array
     * @return array
     */
    function register_router($router)
    {
        if (is_callable($router)) {
            $this->_router = $router;
        }
        return $this;
    }

    /**
     * @return array The return value should include 'errno' and 'data'
     */
    function dispatch()
    {
        $parse_url = $this->parse_url();
        if ($parse_url['errno'] != self::ERRNO_OK) {
            return $parse_url;
        }
        $url_piece = $parse_url['data'];
        $router = call_user_func($this->_router, $url_piece);
        if (isset($router['errno']) && $router['errno'] === self::ERRNO_OK) {
            return ['errno' => self::ERRNO_OK, 'data' => $router];
        } elseif (isset($router['errno'])){
            switch ($router['errno']) {
                case self::ERRNO_FORBIDDEN:
                    \Lib_Log::info('forbidden page! url:[%s] case:[%s]', [json_encode($parse_url), json_encode($router)]);
                    return ['errno' => self::ERRNO_FORBIDDEN, 'data' => 'You cannot visit this page!'];
                case self::ERRNO_SERVER_ERR:
                    \Lib_Log::error('page err! url:[%s] case:[%s]', [json_encode($parse_url), json_encode($router)]);
                    return ['errno' => self::ERRNO_SERVER_ERR, 'data' => 'Something wrong!'];
                case self::ERRNO_NOT_FOUND:
                default:
                    \Lib_Log::notice('page not found! url:[%s] case:[%s]', [json_encode($parse_url), json_encode($router)]);
                    return ['errno' => self::ERRNO_NOT_FOUND, 'data' => 'PAGE NOT FOUND!'];
            }
        } else {
            \Lib_Log::notice('page not found! url:[%s] case:[%s]', [json_encode($parse_url), json_encode($router)]);
            return ['errno' => self::ERRNO_FORBIDDEN, 'data' => 'PAGE NOT FOUND!'];
        }
    }

    /**
     * @param array
     * @return bool
     */
    private function _default_router($url_piece)
    {
        $data = '<!DOCTYPE html><head></head><body>';
        $data .= '<pre>';
        $data .= '--------------------------------'.PHP_EOL;
        $data .= ' '.implode('/',$url_piece).PHP_EOL;
        $data .= '--------------------------------'.PHP_EOL;
        $data .= ' Welcome here! '.PHP_EOL;
        $data .= ' Please set you own route.'.PHP_EOL;
        $data .= ' Version: '.DA_VERSION.PHP_EOL;
        $data .= '--------------------------------'.PHP_EOL;
        $data .= '</pre>';
        $data .= '</body></html>';
        echo $data;
        return true;
    }
}