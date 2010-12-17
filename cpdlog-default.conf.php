<?
/** CPDLog Config: CPD Library Logging Module Configuration File
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
 * Contains defaults and notes possibile values of configuration settings for the CPDLog module. The
 * functions that use these values depend on them being set here; to return a previously set setting
 * to the default, you must manually restore its value to such.    
 */


/** cpdlog.output_dir: <string>; default: __DIR__.'/log';
 *
 * The system directory in which the various log files should be created. This should normally be an
 * absolute path (from the root of the filesystem), something based on $_SERVER['document_root'], or
 * use the __DIR__ macro to reference a folder relative to the location of the cpd library files. If
 * a relative path is used here it will be relative to the location of the original PHP script being
 * executed, which would result in separate log files created for PHP files in different directories
 * (not what you want in most cases, though useful occasionally). If this directory doesn't exist it
 * will be automatically created. Do not use a trailing slash.
 */
$cpdconf['cpdlog']['output_dir'] = __DIR__.'/log';


/** cpdlog.filename_prefix: <string>; default: 'cpdlog_';
 *
 * Prefix to attach to the log file names given as function parameters. This allows you to have some
 * common prefix on all your log file names without having to type it again and again in your source
 * code. Can be set to the empty string to not use a filename prefix.
 */    
$cpdconf['cpdlog']['filename_prefix'] = 'cpdlog_';


/** cpdlog.filename_suffix: <string>; default: '.txt';
 *
 * Suffix to attach to the log file names given as function parameters. This should include the file
 * extension that you want to use (commonly .txt, .log, or .dat). To have a different file extension
 * on different log files, set this to the empty string and include the desired extension in each of
 * your calls to the cpdlog functions.
 */ 
$cpdconf['cpdlog']['filename_suffix'] = '.txt';



/** cpdlog.time_format: <string>; default: '[Y-m-d H:i:s] ';
 *
 * The format for printing timestamps in log entires. For complete details regarding the codes used,
 * see the documentation for PHP's date() function. This will be placed on the beginning of the line
 * in single line log entries, and as a line of its own in block log entires.
 */
$cpdconf['cpdlog']['time_format'] = '[Y-m-d H:i:s] ';


/** cpdlog.endl: <string>; default: PHP_EOL;
 *
 * Line break character to use in log files. This defaults to PHP_EOL, which is the correct platform
 * dependent character to use depending on what OS PHP is running on. Override this to force all log
 * functions to use '\n' or '\r\n' (or some other crazy thing) regardless of the underlying OS.
 */    
$cpdconf['cpdlog']['endl'] = PHP_EOL;


/** cpdlog.new_block: <string>; default: str_repeat("-", 80);
 *
 * A line of text to output between blocks in block log files. This is used to make it easier to see
 * where one block of logged text starts and another begins. This could be set to a few new lines to
 * make a visible area of whitespace between blocks, or a sequence of some character to build a long
 * "header line" in the output file.
 */    
$cpdconf['cpdlog']['new_block'] = str_repeat("-", 80).PHP_EOL;

?>