<?
/** CPDLog: CPD Library Logging Module
 *
 * The Common PHP Development Library
 * Copyright (C) 2010 Aaron Andersen
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License 2.1 as published by the Free Software Foundation.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * 
 * CPDLog is designed to assist the common PHP development task of logging data from a PHP script to
 * disk. This is useful for debugging during developement, as well as recording data from any error-
 * prone parts of an application to assist in locating the cause of an error after one has occurred.
 * This "preemptive debugging" is extremely useful is eliminating those bugs that only show up every
 * six weeks or so (and seemingly only on production sites) and are therefore extremely difficult if
 * not impossible to debug directly. In addition, CPDLog can be used for many common statistical and
 * archival purposes where consistent logging of runtime data is required.
 * 
 * The main function in the CPDLog module is cpdlog(file, text), which appends the given text to the
 * given file, with an optional timestamp controllable via an third boolean parameter (where true is
 * the default). The other primary logging functions all follow the same parameter pattern as cpdlog
 * and exist for logging other types of data, including blocks of text (cpdlog_block), SQL and other
 * text than can be "compacted" to a single line (cpdlog_compact), arrays (cpdlog_array) and desired
 * request and server status information for the current script (cpdlog_request).
 * 
 * Since each of these functions just appends the appropriate data to the given log file, such files
 * can be used or written to by other scripts; additionally, one could use multiple log functions to
 * write to the same log file. However, the module has been optimized for use with only one function
 * per file, and might not produce as neat or useful of output if different functions are used for a
 * single file. 
 * 
 * The prefered file extension (along with any desired prefix) can be set in the cpdlog.conf file so
 * that one need not specify it every time (e.g., a call to cpdlog('decoder', $data) will write to a
 * file called 'cpdlog_myapp_decoder.txt' when the filename_prefix and filename_suffix configuration
 * parameters are set to 'cpdlog_myapp_' and '.txt' respectively). The default values will append to
 * a file with a .txt extension and a 'cpdlog_' prefix. The location of the log files created can be
 * customized by setting the output_dir config value, which defaults to a directory called 'logs' in
 * the cpdsql folder.
 * 
 * All cpdlog functions return TRUE on success and FALSE on error, except the file ones which return
 * the filename or filepath for the given log file. 
 */

require_once 'cpdlog.conf.php';

if(!is_dir($cpdconf['cpdlog']['output_dir']))
{
	mkdir($cpdconf['cpdlog']['output_dir']);
}


/** Append a line of text to the given log file
 *
 * $data should be a single line of text to be logged. Behavior of this function is undefined if the
 * text in $data contains multiple lines (it'll probably work, but look ugly). For logging multiline
 * text blocks, use cpdlog_block instead.
 */ 
function cpdlog($file, $data, $autodate = true)
{
	if($autodate)
	{
		$data = date(_cpdlog_conf('time_format')).$data;
	}
	
	return _cpdlog_append($file, $data._cpdlog_endl());
}


/** Log a multiline string by first "compacting" it to a single line
 *
 * Converts all sequences of one or more newlines, spaces, tabs, or other whitespace characters to a
 * a single space each, then logs the resulting single-line string using cpdlog(). This is extremely
 * useful for logging HTML and SQL source code created in a PHP file, where formatting of the source
 * inside PHP causes what is really a single line of text to be stored as multiple lines. The cpdsql
 * module, for example, uses this function to log SQL queries (when SQL debugging mode is turned on)
 * since long SQL queries almost always wrap lines in PHP source files, often having newlines, tabs,
 * and spaces inserted into them in the process.
 */ 
function cpdlog_compact($file, $data, $autodate = true)
{
	return cpdlog($file, preg_replace('/\s+/', ' ', $data), $autodate);
}


/** Append a multiline block of text to the given log file
 *
 * $data should be a block of text which might contain more than one line. Does not attempt to split
 * or wrap the data to any reasonable line length; if you desire that, format your $data param using
 * a call to PHP's chunk_split() function (or something similar).
 */ 
function cpdlog_block($file, $data, $autodate = true)
{
	if($autodate)
	{
		$data = date(_cpdlog_conf('time_format'))._cpdlog_endl().$data;
	}
	
	return _cpdlog_append($file, $data._cpdlog_endl()._cpdlog_conf('new_block')._cpdlog_endl());
}


/** Log a PHP array as a block of text using print_r
 *
 * This is a shortcut to calling cpdlog_block with print_r($data, true) as the second parameter. The
 * implementation itself uses a wrapper around print_r that converts the \n characters to the system
 * appropriate newline character as set in cpdlog.config. For this reason, the data in the log files
 * might not match 100% the data from the given array. Please plan accordingly. 
 */   
function cpdlog_array($file, $data, $autodate = true)
{	
	return cpdlog_block($file, _cpdlog_print_r($data), $autodate);
}


/** Append one or more request/status arrays to the given log file.
 *
 * The $data parameter should be string containing one or more of SGPFRNEC in the desired order. The
 * meanings of those extend the idea behind the variables_order PHP ini directive, and are listed in
 * the table blow.
 * 
 * Character Meanings 
 *  S: $_SERVER
 *  G: $_GET
 *  P: $_POST
 *  F: $_FILES
 *  R: $_REQUEST
 *  N: $_SESSION
 *  E: $_ENV
 *  C: $_COOKIE
 *  
 * The referenced arrays will be expanded via print_r and added to a text block which is then logged
 * using cpdlog_block, and will appear in the log file in the order they are set in the $data param.
 *  
 * Any \n characters in the values and output (including those created by print_r) will be converted
 * to the system appropriate newline character as set in cpdlog.config. For this reason, the data in
 * the log files might not match 100% the data from the requested variables. 
 */  
function cpdlog_request($file, $data = 'RS', $autodate = true)
{
	$datavars = array('S' => '$_SERVER',  'P' => '$_POST',    'G' => '$_GET', 'F' => '$_FILES',
	                  'R' => '$_REQUEST', 'N' => '$_SESSION', 'E' => '$_ENV', 'C' => '$_COOKIE');
	
	$text = array();
	
	for($i=0; $i < strlen($data); $i++)
	{
		if(isset($datavars[$data[$i]]))
		{
			if(_cpdlog_get_superglobal($datavars[$data[$i]]) !== false)
			{
				$mytext = "{$datavars[$data[$i]]} = ";
				$mytext .= _cpdlog_print_r(_cpdlog_get_superglobal($datavars[$data[$i]]));
				
				$text[] = $mytext; 
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	return cpdlog_block($file, implode(_cpdlog_endl(), $text), $autodate);
}

/* Helper function to work around the fact that PHP superglobals don't work as variable variables.*/
function _cpdlog_get_superglobal($name)
{
	switch($name)
	{
		case '$_SERVER':  return $_SERVER;
		case '$_POST':    return $_POST;
		case '$_GET':     return $_GET;
		case '$_FILES':   return $_FILES;
		case '$_REQUEST': return $_REQUEST;
		case '$_SESSION': return isset($_SESSION) ? $_SESSION : "undefined\n";
		case '$_ENV':     return $_ENV;
		case '$_COOKIE':  return $_COOKIE;
		default: return false;
	}
}


/** Get the filename of the given log file
 *
 * Useful if you want to access a certain log file with script. Saves you the trouble of parsing the
 * relevant config parameters to construct the filename yourself. To get the full system path of the
 * given file, use cpdlog_filepath (i.e., this function just returns the file name portion). 
 */ 
function cpdlog_filename($file)
{
	return _cpdlog_conf('filename_prefix').$file._cpdlog_conf('filename_suffix');
}

/** Get the full path of the given log file
 *
 * Returns the system path of the given log file, as would be needed to open it using PHP.
 */ 
function cpdlog_filepath($file)
{
	return _cpdlog_conf('output_dir').'/'.cpdlog_filename($file);
} 


/** Delete the given log file
 *
 * Calls unlink to delete the given log file. Uses cpdlog config parameters to find the correct file
 * similar to other cpdlog functions.
 */ 
function cpdlog_delete($file)
{
	return unlink(cpdlog_filepath($file));
}




/* Private function used to do the actual file I/O in the cpdlog module. Calls to any of the primary
functions in the cpdlog module (i.e., calls to the logging functions) all eventually end up here. */
function _cpdlog_append($file, $data)
{
	return (bool)file_put_contents(cpdlog_filepath($file), $data, FILE_APPEND);
}

/* Returns a configuration parameter from the global $cpdconf['cpdlog'] array. This is just so I can
avoid having to type $GLOBALS['cpdconf']['cpdlog'] over and over again, and to make code shorter. */
function _cpdlog_conf($param)
{
	return $GLOBALS['cpdconf']['cpdlog'][$param];
}

/* A shortcut to calling _cpdlog_conf('endl') to make that really common one even easier to do. */
function _cpdlog_endl()
{
	return _cpdlog_conf('endl');
}

/* Returns print_r(true) output with \n converted to the config-set line ending code. */
function _cpdlog_print_r($data)
{
	return preg_replace('/\n/', _cpdlog_endl(), print_r($data, true));
}

?>