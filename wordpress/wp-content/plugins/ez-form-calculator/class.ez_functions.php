<?php

abstract class Ez_Functions {
	public static function array_merge_recursive_distinct() {
	    $arrays = func_get_args();
	    $base = array_shift($arrays);

	    foreach ($arrays as $array) {
	        reset($base); //important
	        while (list($key, $value) = @each($array)) {
	            if (is_array($value) && @is_array($base[$key])) {
	                $base[$key] = self::array_merge_recursive_distinct($base[$key], $value);
	            } else {
	                $base[$key] = $value;
	            }
	        }
	    }

	    return $base;
	}

	public static function check_valid_date($date_format, $date, $convert_jqueryui_format=false) {
		$date_format = $convert_jqueryui_format ? self::date_jqueryui_to_php($date_format) : $date_format;

		return (DateTime::createFromFormat($date_format, $date) !== false);
	}

	public static function count_days_format($format, $from, $to) {
		if (!self::check_valid_date($format, $from, true) || !self::check_valid_date($format, $to, true)) return 0;
		
		$datepicker_format = self::date_jqueryui_to_php($format);

		$date_from = DateTime::createFromFormat($datepicker_format, $from);
		$date_to   = DateTime::createFromFormat($datepicker_format, $to);
		$days      = $date_to->diff($date_from)->format("%a");

		return $days;
	}

	public static function date_jqueryui_to_php($format) {
	    $format_array = array(
	        //   Day
	        'dd' => 'd',
	        'DD' => 'l',
	        'd'  => 'j',
	        'o'  => 'z',
	        //   Month
	        'MM' => 'F',
	        'mm' => 'm',
	        'M'  => 'M',
	        'm'  => 'n',
	        //   Year
	        'yy' => 'Y',
	        'y'  => 'y',
	    );

	    $format_ui     = array_keys($format_array);
	    $format_php    = array_values($format_array);
	    $output_format = "";

	    $i = 0;
	    while (isset($format[$i])) {
	    	$char   = $format[$i];
	    	$chars  = $format[$i];
	    	$chars .= isset($format[$i+1]) ? $format[$i+1] : "";

	    	// multiple chars
	    	if (isset($format_array[$chars])) {
	    		$output_format .= str_replace($chars, $format_array[$chars], $chars);
	    		$format         = substr_replace($format, "", 0, 2);
	    	}
	    	// single char
	    	else {
	    		if (isset($format_array[$char])) {
	    			$output_format .= str_replace($char, $format_array[$char], $char);
		    	}
		    	// other
		    	else {
		    		$output_format .= $char;
		    	}

		    	$format = substr_replace($format, "", 0, 1);
		    }
	    }

	    return $output_format;
	}

	public static function array_empty($array = null) {
		if (empty($array)) return true;
		if (!is_array($array)) return true;
	 
		foreach (array_values($array) as $value) {
			$value = trim($value);
			if (!empty($value)) return false;
		}
	 
		return true;	 
	}
}