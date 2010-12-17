<?
/** CPDSQL Config: CPD Library SQL Module Configuration File
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
 * Contains defaults and notes possibile values of configuration settings for the CPDSQL module. The
 * functions that use these values depend on them being set here; to return a previously set setting
 * to the default, you must manually restore its value to such.    
 */


/** cpdsql.engine: 'mysql'|'odbc'; default: 'mysql';
 *
 * What SQL engine the module should be powered by. Currently supported engines are listed above. If
 * the SQL engine you would like to use isn't listed here, please contact the project author to talk
 * about having it added. 
 */
$cpdconf['cpdsql']['engine'] = 'mysql';




/** cpdsql.engine_error.log: <bool>; default: true;
 *
 * Whether to log errors reported by the SQL engine. It is usually a good idea to do this, then have
 * that file reviewed on a regular basis to spot bugs in your PHP code or databases. Logging is done
 * via the CPDLog module, so that is required if this is turned on.   
 */
$cpdconf['cpdsql']['engine_error']['log'] = true;

/** cpdsql.engine_error.log_file: <string>; default: 'sql_error';
 *
 * File to log engine errors to. Logging is done via the CPDLog module's cpdlog_compact function, so
 * the directory, prefix, and suffix options from cpdlog.conf will be used. 
 */
$cpdconf['cpdsql']['engine_error']['log_file'] = 'sql_error';

/** cpdsql.engine_error.exit: <bool>; default: false;
 *
 * Whether to exit the current script in the event of an SQL engine error. 
 */
$cpdconf['cpdsql']['engine_error']['exit'] = false;



/** cpdsql.cpdsql_error.log: <bool>; default: true;
 *
 * Whether to log errors reported by CPDSQL itself. These include required function parameters being
 * left off, data of the wrong type, etc. They are infrequent, but can be a sign of bugs in PHP. All
 * the actual logging is done via the CPDLog module, so that is required if this is turned on. A few
 * special types of these errors can be enabled and disabled using options from the next section.    
 */
$cpdconf['cpdsql']['cpdsql_error']['log'] = true;

/** cpdsql.cpdsql_error.log_file: <string>; default: 'sql_error';
 *
 * File to log CPDSQL errors to. Logging is done via the CPDLog module's cpdlog_compact function, so
 * the directory, prefix, and suffix options from cpdlog.conf will be used. 
 */
$cpdconf['cpdsql']['cpdsql_error']['log_file'] = 'sql_error';

/** cpdsql.cpdsql_error.exit: <bool>; default: false;
 *
 * Whether to exit the current script in the event of an error reported by the CPDSQL module itself. 
 */
$cpdconf['cpdsql']['cpdsql_error']['exit'] = true;



/** cpdsql.query.log: <bool>; default: false;
 *
 * Whether to log every SQL query that goes through the module. This is a good idea for development,
 * general debugging, and "setting a trap" for infrequent bugs that can't seem to be reproduced in a
 * test environment, but will result in very large log files very quickly if turned on for a site in
 * production.
 */
$cpdconf['cpdsql']['query']['log'] = true;

/** cpdsql.query.log_file: <string>; default: 'sql_query';
 *
 * File to log all SQL queries to. Logging is done with the CPDLog module's cpdlog_compact function,
 * so the directory, prefix, and suffix options from cpdlog.conf will be used. 
 */
$cpdconf['cpdsql']['query']['log_file'] = 'sql_query';



/** cpdsql.enable_no_data_error: <bool>; default: true;
 *
 * Whether to treat no_data as an error. This is raised by the query functions if they expected data
 * parameters and none were provided, and is designed to catch situations in which the caller forgot
 * to use data parameters and constructed the query string directly (and thus might have created SQL
 * injection vulnerabilities). The no_data error can be supressed by passing in null as a last param
 * to the query function; doing so is the recommended way to perform constant queries (i.e., queries
 * that don't require any data parameters). Leaving this check turned on is strongly recommended, as
 * cpdsql's injection prevention features don't do any good if people don't use them.   
 */
$cpdconf['cpdsql']['enable_no_data_error'] = true;


/** cpdsql.enable_bare_string_error: <bool>; default: true;
 *
 * Whether to treat bare_string as an error. This is raised when the query parameter contains a '%s'
 * (string data parameter) that isn't itself inclosed in quotation marks. This currently only checks
 * for a situation involving "=%s" found in the query string, so it is vulnerable to the possibility
 * of false positives, and includes a huge number of false negatives (because of the four hundred or
 * so other ways you could include a bare string in your SQL code). Future versions of this might be
 * a lot smarter, but even as it is it's fairly useful. Testing it might be a bit slow in some cases
 * where long queries are executed extremely frequently; leaving this check on during development is
 * highly recommended, even if you disable it for production servers.
 */
$cpdconf['cpdsql']['enable_bare_string_error'] = true;
?>