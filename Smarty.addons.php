<?

/*
 * Project:		Smarty: the PHP compiled template engine
 * File:		Smarty.functions.php
 * Author:		Monte Ohrt <monte@ispi.net>
 *				Andrei Zmievski <andrei@ispi.net>
 *
 */

/*============================================*\
  Modifiers
\*============================================*/

function _smarty_mod_handler()
{
	$args = func_get_args();
	list($func_name, $map_array) = array_splice($args, 0, 2);
	$var = $args[0];

	if ($map_array && is_array($var)) {
		foreach ($var as $key => $val) {
			$args[0] = $val;
			$var[$key] = call_user_func_array($func_name, $args);
		}
		return $var;
	} else {
		return call_user_func_array($func_name, $args);
	}
}


/*======================================================================*\
	Function: smarty_mod_escape
	Purpose:  Escape the string according to escapement type
\*======================================================================*/
function smarty_mod_escape($string, $esc_type = 'html')
{
	switch ($esc_type) {
		case 'html':
			return htmlspecialchars($string);

		case 'url':
			return urlencode($string);

		default:
			return $string;
	}
}


/*======================================================================*\
	Function: smarty_mod_truncate
	Purpose:  Truncate a string to a certain length if necessary,
			  optionally splitting in the middle of a word, and
			  appending the $etc string.
\*======================================================================*/
function smarty_mod_truncate($string, $length = 80, $etc = '...', $break_words = false)
{
	if (strlen($string) > $length) {
		$length -= strlen($etc);
		$fragment = substr($string, 0, $length+1);
		if ($break_words)
			$fragment = substr($fragment, 0, -1);
		else
			$fragment = preg_replace('/\s+(\S+)?$/', '', $fragment);
		return $fragment.$etc;
	} else
		return $string;
}


function smarty_mod_spacify($string, $spacify_char = ' ')
{
	return implode($spacify_char, preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY));
}


function smarty_mod_date_format($string, $format)
{
	return strftime($format, $string);
}


function smarty_mod_string_format($string, $format)
{
	return sprintf($format, $string);
}

function smarty_mod_replace($string, $search, $replace)
{
	return str_replace($search, $replace, $string);
}

function smarty_mod_strip_tags($string, $replace_with_space = false)
{
	if ($replace_with_space)
		return preg_replace('!<[^>]*?>!', ' ', $string);
	else
		return strip_tags($string);
}

/*============================================*\
  Custom tag functions
\*============================================*/

/*======================================================================*\
	Function: smarty_func_html_options
	Purpose:  Prints the list of <option> tags generated from
			  the passed parameters
\*======================================================================*/
function smarty_func_html_options()
{
	$print_result = true;

	extract(func_get_arg(0));

	settype($output, 'array');
	settype($values, 'array');
	settype($selected, 'array');

	$html_result = "";

	for ($i = 0; $i < count($output); $i++) {
		/* By default, check value against $selected */
		$sel_check = $values[$i];
		$html_result .= "<option";
		if ($i < count($values))
			$html_result .= " value=\"".$values[$i]."\"";
		else
			$sel_check = $output[$i];   	/* if more outputs than values, then
											   check output against $selected */		
		if (in_array($sel_check, $selected))
			$html_result .= " selected";
		$html_result .= ">".$output[$i]."</option>\n";
	}

	if ($print_result)
		print $html_result;
	else
		return $html_result;
}


/*======================================================================*\
	Function: smarty_func_html_select_date
	Purpose:  Prints the dropdowns for date selection.
\*======================================================================*/
function smarty_func_html_select_date()
{
	/* Default values. */
	$prefix 		= "Date_";
	$time 			= time();
	$start_year		= strftime("%Y");
	$end_year		= $start_year;
	$display_days	= true;
	$display_months	= true;
	$display_years	= true;
	$month_format	= "%B";
	$day_format		= "%02d";
	$year_as_text	= false;
	
	extract(func_get_arg(0));

	$html_result = "";

	if ($display_months) {
		$month_names = array();

		for ($i = 1; $i <= 12; $i++)
			$month_names[] = strftime($month_format, mktime(0, 0, 0, $i, 1, 2000));

		$html_result .= '<select name="'.$prefix.'Month">'."\n";
		$html_result .= smarty_func_html_options(array('output'		=> $month_names,
													   'values'		=> range(1, 12),
													   'selected'	=> strftime("%m", $time),
													   'print_result' => false));
		$html_result .= "</select>\n";
	}

	if ($display_days) {
		$days = range(1, 31);
		array_walk($days, create_function('&$x', '$x = sprintf("'.$day_format.'", $x);'));

		$html_result .= '<select name="'.$prefix.'Day">'."\n";
		$html_result .= smarty_func_html_options(array('output'		=> $days,
													   'values'		=> range(1, 31),
													   'selected'	=> strftime("%d", $time),
													   'print_result' => false));
		$html_result .= "</select>\n";
	}

	if ($display_years) {
		if ($year_as_text) {
			$html_result .= '<input type="text" name="'.$prefix.'Year" value="'.strftime('%Y', $time).'" size="4" maxlength="4">';
		} else {
			$years = range($start_year, $end_year);

			$html_result .= '<select name="'.$prefix.'Year">'."\n";
			$html_result .= smarty_func_html_options(array('output'	=> $years,
														   'values'	=> $years,
														   'selected'	=> strftime("%Y", $time),
														   'print_result' => false));
			$html_result .= "</select>\n";
		}
	}

	print $html_result;
}

/*============================================*\
  Insert tag functions
\*============================================*/

function insert_paginate()
{
	echo "test paginate\n";
}
?>
