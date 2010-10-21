<?php

//
// Open Web Analytics - An Open Source Web Analytics Framework
//
// Copyright 2006 Peter Adams. All rights reserved.
//
// Licensed under GPL v2.0 http://www.gnu.org/copyleft/gpl.html
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
// $Id$
//

//require_once 'owa_env.php';

//require_once(OWA_BASE_CLASS_DIR.'settings.php');

/**
 * Utility Functions
 * 
 * @author      Peter Adams <peter@openwebanalytics.com>
 * @copyright   Copyright &copy; 2006 Peter Adams <peter@openwebanalytics.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GPL v2.0
 * @category    owa
 * @package     owa
 * @version		$Revision$	      
 * @since		owa 1.0.0
 */
class owa_lib {

	/**
	 * Convert Associative Array to String
	 *
	 * @param string $inner_glue
	 * @param string $outer_glue
	 * @param array $array
	 * @return string 
	 */
	public static function implode_assoc($inner_glue, $outer_glue, $array) {
	   $output = array();
	   foreach( $array as $key => $item ) {
			  $output[] = $key . $inner_glue . $item;
		}
		
		return implode($outer_glue, $output);
	}
			
	/**
	 * Deconstruct Associative Array 
	 *
	 * For example this takes array([1] => array(a => dog, b => cat), [2] => array(a => sheep, b => goat))
	 * and tunrs it into array([a] => array(dog, sheep), [b] => array(cat, goat)) 
	 * 
	 * @param array $a_array
	 * @return array $data_arrays
	 * @access public
	 */
	public static function deconstruct_assoc($a_array) {
		if (!empty($a_array)):
		
			$data_arrays = array();
		
			if(!empty($a_array[1])) :
			
				foreach ($a_array as $key => $value) {
					foreach ($value as $k => $v) {
						$data_arrays[$k][] = $v;
				
					}
				}
			else:
				//print_r($a_array[0]);
				foreach ($a_array[0] as $key => $value) {
					$data_arrays[$key][] = $value;
				}
			endif;
			
			return $data_arrays;
		else:
			return array();
		endif;
	}
	
	
	public static function decon_assoc($a_array) {
		
		$data_arrays = array();
	
		foreach ($a_array as $key => $value) {
			//foreach ($value as $k => $v) {
				$data_arrays[$key][] = $value;
		
			//}
		}
		
		return $data_arrays;
	}

	// php 4 compatible function
	public static function array_intersect_key() {
	
        $arrs = func_get_args();
        $result = array_shift($arrs);
        foreach ($arrs as $array) {
            foreach ($result as $key => $v) {
                if (!array_key_exists($key, $array)) {
                    unset($result[$key]);
                }
            }
        }
        return $result;
     }
	
	// php4 compatible function
	public static function array_walk_recursive(&$input, $funcname, $userdata = "")
    {
        if (!is_callable($funcname))
        {
            return false;
        }
        
        if (!is_array($input))
        {
            return false;
        }
        
        if (is_array($funcname))
        {
            $funcname = $funcname[0].'::'.$funcname[1];
        }
        
        
        foreach ($input AS $key => $value)
        {
            if (is_array($input[$key]))
            {
                array_walk_recursive($input[$key], $funcname, $userdata);
            }
            else
            {
                $saved_value = $value;
                if (!empty($userdata))
                {
                    $funcname($value, $key, $userdata);
                }
                else
                {
                    $funcname($value, $key);
                }
                
                if ($value != $saved_value)
                {
                    $input[$key] = $value;
                }
            }
        }
        return true;
    }

	/**
	 * Array of Current Time
	 *
	 * @return array
	 * @access public
	 */
	public static function time_now() {
		
		$timestamp = time();
		
		return array(
			
				'year' 				=> date("Y", $timestamp),
				'month' 			=> date("n", $timestamp),
				'day' 				=> date("d", $timestamp),
				'dayofweek' 		=> date("w", $timestamp),
				'dayofyear' 		=> date("z", $timestamp),
				'weekofyear'		=> date("W", $timestamp),
				'hour'				=> date("G", $timestamp),
				'minute' 			=> date("i", $timestamp),
				'second' 			=> date("s", $timestamp),
				'timestamp'			=> $timestamp
			);
	}
		
	/**
	 * Error Handler
	 *
	 * @param string $msg
	 * @access public
	 * @depricated
	 */
	function errorHandler($msg) {
		require_once(OWA_PEARLOG_DIR . '/Log.php');
		$conf = array('mode' => 0755, 'timeFormat' => '%X %x');
		$error_logger = &Log::singleton('file', $this->config['error_log_file'], 'ident', $conf);
		$this->error_logger->_lineFormat = '[%3$s]';
		
		return;
	}
	
	/**
	 * Information array for Months in the year.
	 *
	 * @return array
	 */
	public static function months() {
		
		return array(
					
					1 => array('label' => 'January'),
					2 => array('label' => 'February'),
					3 => array('label' => 'March'),
					4 => array('label' => 'April'),
					5 => array('label' => 'May'),
					6 => array('label' => 'June'),
					7 => array('label' => 'July'),
					8 => array('label' => 'August'),				
					9 => array('label' => 'September'),
					10 => array('label' => 'October'),
					11 => array('label' => 'November'),
					12 => array('label' => 'December')
		);
		
	}
	
	public static function days() {
		
		return array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 
					15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31);
	}
	
	public static function years() {
		
		static $years;
		
		if (empty($years)):
			
			$start_year = 2005;
			
			$years = array($start_year);
			
			$num_years =  date("Y", time()) - $start_year;
			
			for($i=1; $i<=$num_years; $i++) {
		 	
				$years[] = $start_year + $i;
			}
			
			$years = array_reverse($years);
		
		endif;
		
		return $years;
	}
	
	
	/**
	 * Returns a label from an array of months
	 *
	 * @param int $month
	 * @return string
	 */
	public static function get_month_label($month) {
		
		static $months;
		
		if (empty($months)):

			$months = owa_lib::months();
		
		endif;  
		
		return $months[$month]['label'];
		
	}
	
	
	/**
	 * Sets the suffix for Days used in Date labels
	 * @depricated
	 * @param string $day
	 * @return string
	 */
	public static function setDaySuffix($day) {
		
		switch ($day) {
			
			case "1":
				$day_suffix = 'st';
				break;
			case "2":
				$day_suffix = 'nd';
				break;
			case "3":
				$day_suffix = 'rd';
				break;
			default:
				$day_suffix = 'th';
		}
		
		return $day_suffix;
		
	}
	
	/**
	 * Generates the label for a date
	 * @depricated
	 * @param array $params
	 * @return string
	 */
	public static function getDatelabel($params) {
		
		switch ($params['period']) {
		
			case "day":
				return sprintf("%s, %d%s %s",
							owa_lib::get_month_label($params['month']),
							$params['day'],
							owa_lib::setDaySuffix($params['day']),
							$params['year']				
						);
				break;
			
			case "month":
				return sprintf("%s %s",
							owa_lib::get_month_label($params['month']),
							$params['year']				
						);
				break;
			
			case "year":	
				return sprintf("%s",
							$params['year']				
						);
				break;
			case "date_range":
				return sprintf("%s, %d%s %s - %s, %d%s %s",
							owa_lib::get_month_label($params['month']),
							$params['day'],
							owa_lib::setDaySuffix($params['day']),
							$params['year'],
							owa_lib::get_month_label($params['month2']),
							$params['day2'],
							owa_lib::setDaySuffix($params['day2']),
							$params['year2']					
						);
				break;
		}
		
		return false;
		
	}
	
	/**
	 * Array of Reporting Periods
	 * @depricated
	 * @return array
	 */
	public static function reporting_periods() {
		
		return array(
					
					'today' => array('label' => 'Today'),
					'yesterday' => array('label' => 'Yesterday'),
					'this_week' => array('label' => 'This Week'),
					'this_month' => array('label' => 'This Month'),
					'this_year' => array('label' => 'This Year'),
					'last_week'  => array('label' => 'Last Week'),
					'last_month' => array('label' => 'Last Month'),
					'last_year' => array('label' => 'Last Year'),
					'last_half_hour' => array('label' => 'The Last 30 Minutes'),				
					'last_hour' => array('label' => 'Last Hour'),
					'last_24_hours' => array('label' => 'The Last 24 Hours'),
					'last_seven_days' => array('label' => 'The Last Seven Days'),
					'last_thirty_days' => array('label' => 'The Last Thirty Days'),
					'same_day_last_week' => array('label' => 'Same Day last Week'),
					'same_week_last_year' => array('label' => 'Same Week Last Year'),
					'same_month_last_year' => array('label' => 'Same Month Last Year'),
					'date_range' => array('label' => 'Date Range')
		);
		
	}
	
	/**
	 * Array of Date specific Reporting Periods
	 * @depricated
	 * @return array
	 */
	public static function date_reporting_periods() {
		
		return array(
					
					'day' => array('label' => 'Day'),
					'month' => array('label' => 'Month'),
					'year' => array('label' => 'Year'),
					'date_range' => array('label' => 'Date Range')
		);
		
	}
	
	/**
	 * Gets label for a particular reporting period
	 *
	 * @param unknown_type $period
	 * @return unknown
	 */
	public static function get_period_label($period) {
	
		$periods = owa_lib::reporting_periods();
		
		return $periods[$period]['label'];
	}
	
	/**
	 * Assembles the current URL from request params
	 *
	 * @return string
	 */
	public static function get_current_url() {
		
		$url = 'http';	
		
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'):
			$url.= 's';
		endif;
		
		$url .= '://'.$_SERVER['SERVER_NAME'];
		
		if($_SERVER['SERVER_PORT'] != 80):
			$url .= ':'.$_SERVER['SERVER_PORT'];
		endif;
		
		$url .= $_SERVER['REQUEST_URI'];
		
		return $url;
	}
	
	public static function inputFilter($array) {
		
		static $f;
		
		if (empty($f)) {
			require_once(OWA_INCLUDE_DIR.'/class.inputfilter.php');
			$f = new InputFilter;
		}
		
		return $f->process($array);
		
	}
	
	public static function fileInclusionFilter($str) {
		
		$str = str_replace("http://", "", $str);
		$str = str_replace("/", "", $str);
		$str = str_replace("\\", "", $str);
		$str = str_replace("../", "", $str);
		$str = str_replace("..", "", $str);
		$str = str_replace("?", "", $str);
		$str = str_replace("%00", "", $str);
		
		if (strpos($str, '%00')) {
			$str = '';
		}
		
		if (strpos($str, null)) {
			$str = '';
		}
		
		return $str;
	}
	
	/**
	 * Generic Factory method
	 *
	 * @param string $class_dir
	 * @param string $class_prefix
	 * @param string $class_name
	 * @param array $conf
	 * @return object
	 */
	public static function &factory($class_dir, $class_prefix, $class_name, $conf = array(), $class_suffix = '') {
		
        //$class_dir = strtolower($class_dir).DIRECTORY_SEPARATOR;
        $class_dir = $class_dir.DIRECTORY_SEPARATOR;
        $classfile = $class_dir . $class_name . '.php';
		$class = $class_prefix . $class_name . $class_suffix;
		
        /*
         * Attempt to include a version of the named class, but don't treat
         * a failure as fatal.  The caller may have already included their own
         * version of the named class.
         */
        if (!class_exists($class)) {
        	
        	if (file_exists($classfile)) {
        		require_once ($classfile);
        	}
            
        }

        /* If the class exists, return a new instance of it. */
        if (class_exists($class)) {
            $obj = &new $class($conf);
            return $obj;
        }

        $null = null;
        return $null;
    }
	
    /**
     * Generic Object Singleton
     *
     * @param string $class_dir
     * @param string $class_prefix
     * @param string $class_name
     * @param array $conf
     * @return object
     */
    public static function &singleton($class_dir, $class_prefix, $class_name, $conf = array()) {
    	
        static $instance;
        
        if (!isset($instance)) {
        	// below missing a reference becasue the static vriable can not handle a reference 
        	$instance = owa_lib::factory($class_dir, $class_prefix, $class_name, $conf);
        }
        
        return $instance;
    }
    
    /**
     * 302 HTTP redirect the user to a new url
     *
     * @param string $url
     */
    public static function redirectBrowser($url) {
    	
    	//ob_clean();
	    // 302 redirect to URL 
		header ('Location: '.$url, true);
		header ('HTTP/1.0 302 Found', true);
		return;
    }
	
	public static function makeLinkQueryString($query_params) {
		
		$new_query_params = array();
		
		//Load params passed by caller
		if (!empty($this->caller_params)):
			foreach ($this->caller_params as $name => $value) {
				if (!empty($value)):
					$new_query_params[$name] = $value;	
				endif;
			}
		endif;

		// Load overrides
		if (!empty($query_params)):
			foreach ($query_params as $name => $value) {
				if (!empty($value)):
					$new_query_params[$name] = $value;	
				endif;
			}
		endif;
		
		// Construct GET request
		if (!empty($new_query_params)):
			foreach ($new_query_params as $name => $value) {
				if (!empty($value)):
					$get .= $name . "=" . $value . "&";	
				endif;
			}
		endif;
		
		return $get;
		
	}
	
	public static function getRequestParams() {
		
		$params = array();
		
		if (!empty($_POST)) {
			$params = $_POST;
		} else {
			$params = $_GET;
		}
		
		if (!empty($_COOKIE)) {
			
			$params = array_merge($params, $_COOKIE);
		}
				
		return $params;
	}
	
	public static function rekeyArray($array, $new_keys) {
	
		$new_keys = $new_keys;
		$new_array = array();
		foreach ($array as $k => $v) {
		
			if (array_key_exists($k, $new_keys)) {
				$k = $new_keys[$k];
			}
			
			$new_array[$k] = $v;
		}
		
		return $new_array;
	}
	
	
	public static function stripParams($params, $ns = '') {
		
		$striped_params = array();
		
		if (!empty($ns)) {
		
			$len = strlen($ns);
			
			foreach ($params as $n => $v) {
				
				// if namespace is present in param
				if (strstr($n, $ns)) {
					// strip the namespace value
					$striped_n = substr($n, $len);  
					//add to striped array
					$striped_params[$striped_n] = $v;
					
				}
				
			}
			
			return $striped_params;

		} else {
		
			return $params;
		}
			
	}
	
	/**
	 * module specific require method
	 *
	 * @param unknown_type $module
	 * @param unknown_type $file
	 * @return unknown
	 * @deprecated 
	 */
	public static function moduleRequireOnce($module, $file) {
		
		return require_once(OWA_BASE_DIR.'/modules/'.$module.'/'.$file.'.php');
	}
	
	/**
	 * module specific factory
	 *
	 * @param unknown_type $modulefile
	 * @param unknown_type $class_suffix
	 * @param unknown_type $params
	 * @return unknown
	 * @deprecated 
	 */
	public static function moduleFactory($modulefile, $class_suffix = null, $params = '') {
		
		list($module, $file) = explode(".", $modulefile);
		$class = 'owa_'.$file.$class_suffix;
		
		// Require class file if class does not already exist
		if(!class_exists($class)):	
			owa_lib::moduleRequireOnce($module, $file);
		endif;
			
		$obj = owa_lib::factory(OWA_BASE_DIR.'/modules/'.$module, '', $class, $params);
		$obj->module = $module;
		
		return $obj;
	}
    
	/**
	 * redirects borwser to a particular view
	 *
	 * @param unknown_type $data
	 */
	public static function redirectToView($data) {
		//print_r($data);
		$c = &owa_coreAPI::configSingleton();
		$config = $c->fetch('base');
		
		$control_params = array('view_method', 'auth_status');
		
		
		$get = '';
		
		foreach ($data as $n => $v) {
			
			if (!in_array($n, $control_params)): 			
			
				$get .= $config['ns'].$n.'='.$v.'&';
			
			endif;
		}
		$new_url = sprintf($config['link_template'], $config['main_url'], $get);
		
		owa_lib::redirectBrowser($new_url);
		
		return;
	}
	
	/**
	 * Displays a View without user authentication. Takes array of data as input
	 *
	 * @param array $data
	 * @deprecated 
	 */
	public static function displayView($data, $params = array()) {
		
		$view =  owa_lib::moduleFactory($data['view'], 'View', $params);
		
		return $view->assembleView($data);
		
	}
	
	/**
	 * Create guid from string
	 *
	 * @param 	string $string
	 * @return 	integer
	 * @access 	private
	 */
	public static function setStringGuid($string) {
		if (!empty($string)):
			return crc32(strtolower($string));
		else:
			return;
		endif;
	}
	
	/**
	 * Add constraints into SQL where clause
	 *
	 * @param 	array $constraints
	 * @return 	string $where
	 * @access 	public
	 * @depricated
	 * @todo remove
	 */
	function addConstraints($constraints) {
	
		if (!empty($constraints)):
		
			$count = count($constraints);
			
			$i = 0;
			
			$where = '';
			
			foreach ($constraints as $key => $value) {
					
				if (empty($value)):
					$i++;
				else:
				
					if (!is_array($value)):
						$where .= $key . ' = ' . "'$value'";
					else:
					
						switch ($value['operator']) {
							case 'BETWEEN':
								$where .= sprintf("%s BETWEEN '%s' AND '%s'", $key, $value['start'], $value['end']);
								break;
							default:
								$where .= sprintf("%s %s '%s'", $key, $value['operator'], $value['value']);		
								break;
						}
					
						
					endif;
					
					if ($i < $count - 1):
						
						$where .= " AND ";
						
					endif;
	
					$i++;	
				
				endif;
					
			}
			// needed in case all values in the array are empty
			if (!empty($where)):
				return $where;
			else: 
				return;
			endif;
				
		else:
			
			return;
					
		endif;
		
		
		
	}
	
	public static function assocFromString($string_state, $inner = '=>', $outer = '|||') {
	
		if (!empty($string_state)):
		
			if (strpos($string_state, $outer) === false):
	
				return $string_state;
				
			else:
				
				$array = explode($outer, $string_state);
				
				$state = array();
				
				foreach ($array as $key => $value) {
		
					list($realkey, $realvalue) = explode($inner, $value);
					$state[$realkey] = $realvalue;
		
				}
				
			endif;
	
		endif;
		
		return $state;

	
	}
	
	/**
 	 * Simple function to replicate PHP 5 behaviour
 	 */
	
	public static function microtime_float() {
	    list($usec, $sec) = explode(" ", microtime());
    	return ((float)$usec + (float)$sec);
	}
	
	/**
	 * Lists all files in a Directory
	 *
	 */
	public static function listDir($start_dir='.', $recursive = true) {

		$files = array();
		
		if (is_dir($start_dir)):
		
			$fh = opendir($start_dir);
			
			while (($file = readdir($fh)) !== false) {
			
				// loop through the files, skipping . and .., and recursing if necessary
				if (strcmp($file, '.')==0 || strcmp($file, '..')==0) continue;
				$filepath = $start_dir . DIRECTORY_SEPARATOR . $file;
				
				
				if (is_dir($filepath)):
					if ($recursive === true):
						$files = array_merge($files, owa_lib::listDir($filepath));
					endif;
				else:
					array_push($files, array('name' => $file, 'path' => $filepath));
				endif;
			}
			
			closedir($fh);
	  
		else:
			// false if the function was called with an invalid non-directory argument
			$files = false;
		endif;
	
	  return $files;
	
	}

	public static function makeDateArray($result, $format) {
		
		if (!empty($result)) {
		
			$timestamps = array();
			
			foreach ($result as $row) {
				
				$timestamps[]= mktime(0,0,0,$row['month'],$row['day'],$row['year']);
			}
		
			return owa_lib::makeDates($timestamps, $format);
		
		} else {
		
			return array();
		}
		
	}
	
	public static function makeDates($timestamps, $format) { 
		
		sort($timestamps);
			
			$new_dates = array();
			
			foreach ($timestamps as $timestamp) {
				
				$new_dates[] = date($format, $timestamp);
				
			}
			
		return $new_dates;
		
	}
	
	public static function html2txt($document){
		$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
		               '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
		               '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
		               '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
		);
		$text = preg_replace($search, '', $document);
		return $text;	
	}
	
	public static function escapeNonAsciiChars($string) {
	
		return preg_replace('/[^(\x20-\x7F)]*/','', $string);
	}

	/**
	 * Truncate string
	 *
	 * @param string $str
	 * @param integer $length
	 * @param string $trailing
	 * @return string
	 */
	public static function truncate ($str, $length=10, $trailing='...')  {
	 
    	// take off chars for the trailing 
    	$length-=strlen($trailing); 
    	if (strlen($str) > $length):
        	// string exceeded length, truncate and add trailing dots 
         	return substr($str,0,$length).$trailing; 
		else:  
        	// string was already short enough, return the string 
        	$res = $str;  
      	endif;
   
      return $res; 
	}
	
	/**
	 * Simple Password Encryption
	 *
	 * @param string $password
	 * @return string
	 */
	public static function encryptPassword($password) {
		
		return md5(strtolower($password).strlen($password));
	}
	
	public static function timestampToYyyymmdd($timestamp = '') {
		
		if(empty($timestamp)) {
			$timestamp = time();
		}
		//print "before date";
		$yyyymmdd = date("Ymd", $timestamp);
		///print "after date";
		return $yyyymmdd;
	}
	
	public static function setContentTypeHeader($type = 'html') {
		
		if (!$type) {	
			$type = 'html';
		}
		
		$content_types = array('html' => 'text/html', 
							   'xml' => 'text/xml', 
							   'json' => 'application/json', 
							   'csv' => 'text/csv');
		
		if (array_key_exists($type, $content_types)) {
			$mime = $content_types[$type];
			header('Content-type: '.$mime);
		}
	}
	
	public static function array_values_assoc($assoc) {
		
		$values = array();
		
		foreach ($assoc as $k => $v) {
			
			if (!empty($v)) {
				$values[] = $v;
			}
		}
		
		return $values;
	}
	
	public static function prepareCurrencyValue($string) {
		
		return $string * 100;
	}
	
	public static function utf8Encode($string) {
		
		if ( owa_lib::checkForUtf8( $string ) ) {
			return $string; 
		} else { 
    		if (function_exists('iconv')) {
				return iconv('UTF-8','UTF-8//TRANSLIT', $string);
			} else {
				// at least worth a try
				return utf8_encode($string);
			}
		}
	}
	
	public static function checkForUtf8($str) {
	
		if ( function_exists( 'mb_detect_encoding' ) ) {
			$cur_encoding = mb_detect_encoding( $str ) ; 
			if ( $cur_encoding == "UTF-8" && mb_check_encoding( $str,"UTF-8" ) ) {
				return true;
			}
		} else {
		 
		    $len = strlen( $str ); 
		    for( $i = 0; $i < $len; $i++ ) { 
		        
		        $c = ord( $str[$i] ); 
		        if ($c > 128) { 
		            
		            if ( ( $c > 247 ) ) {
		            	return false; 
		            } elseif ( $c > 239 ) {
		            	$bytes = 4; 
		            } elseif ( $c > 223 ) {
		            	$bytes = 3; 
		            } elseif ( $c > 191 ) {
		            	$bytes = 2; 
		            } else {
		            	return false; 
		            }
		            
		            if ( ( $i + $bytes ) > $len ) {
		            	return false; 
		            }
		            
		            while ( $bytes > 1 ) { 
		                $i++; 
		                $b = ord( $str[$i] ); 
		                if ( $b < 128 || $b > 191 ) {
		                	return false;
		                }
		                $bytes--; 
		            } 
		        } 
		    } 
		    return true; 
		}
	}
	
	public static function formatCurrency($value, $local) {
		
		setlocale( LC_MONETARY, $local );
		$value = $value /100;
		return money_format( '%.2n',$value );
	}
}

?>