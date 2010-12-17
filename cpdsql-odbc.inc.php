<?
/** CPDSQL-ODBC: ODBC Driver for CPD Library SQL Module
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
 * ODBC driver for the CPDSQL function library. All of the functions in this file call into the ODBC
 * module in PHP (i.e., the odbc_* functions) to directly communicate with the SQL driver or server.
 * All of the main functions in the CPDSQL library eventually call one of these to do the final work
 * of running the SQL query, parsing the result object, and (when required) converting the data into
 * the requested format.
 * 
 * Since PHP's ODBC functions do not have a concept of a "default connection" (unlike some other SQL
 * engines, such as mysql), this driver requires that a connection be set with cpdsql_connection_set
 * before it can actually be used (the connection resource is returned by odbc_connect).  
 * 
 * To add a new SQL database engine to CPDSQL, just copy this file (changing the filename from -odbc
 * to whatever new engine is being supported) and reimplement each of these functions using PHP APIs
 * for that particular engine. In most cases the task should be as simple as changing a few function
 * names here any there, though thorough testing is encouraged. Contact the author of the module for
 * more information.
 */


/** Perform the given database query, and check for engine errors
 *
 * The implementation of this function is one line long, plus the error checking code. It just calls
 * the odbc_exec function, verifies that everything worked, and returns the odbc result object.
 * 
 * @param {string} $query The SQL query to execute
 *  
 * @return {ODBC result} The query result object; on error, FALSE (or exits, depending on config)
 */ 
function cpdsql_odbc_query($query)
{
	// Doesn't actually do anything unless the query.log config directive is enabled
	_cpdsql_log_query($query);
	
	// See if we're using an explicit connection...
	$link = cpdsql_connection_get();
	
	// This is where the magic happens...
	$result = odbc_exec($link, $query);
	
	// ...or doesn't ;)
	if(odbc_error($link))
	{
		_cpdsql_engine_error("ODBC: ".odbc_errormsg($link));
	}
	
	return $result;
}


/** Safely escape a string using the database prvided function
 * 
 * In this implementation, calls addslashes (there's doesn't seem to be an ODBC escape function)
 * 
 * @param  {string} $data The string to escape
 * @return {string}       The escaped string value  
 */  
function cpdsql_odbc_escape_string($data)
{
	return addslashes($data);
}


/** Returns data from the given ODBC result object as an array of associative arrays
 *
 * This isn't something you actually want to do on a regular basis, because pulling all the rows and
 * storing them all in memory like this is a very inefficient way of doing things. In fact, doing it
 * for extremely large result sets could potentially cause PHP to timeout or exceed available memory
 * (neither of which is a good thing). In most cases, the classic SQL design pattern of pulling data
 * and processing it one row at a time (using odbc_fetch_array or cpdsql_result_row) is the best way
 * to go about it. However, there is an occasional need to get an entire table or query's result all
 * together in a single array, and this function will do that for you.
 *
 * @param {ODBC result} $result A ODBC result object from a successful ODBC query
 * 
 * @return {Array<Array<string, mixed>>} An array of PHP associative arrays with all result rows
 */    
function cpdsql_odbc_result_array($result)
{
	$everything = array();
	
	while($row = odbc_fetch_array($result))
	{
		$everything[] = $row;
	}
	
	return $everything;
}


/** Get the next row of data from a ODBC result object as an associative array
 *
 * This function is just a wrapper for the ODBC-specifc odbc_fetch_array function, plus some code to
 * check for ODBC errors (maybe).  
 *
 * @param {ODBC result} $result A ODBC result object from a successful ODBC query 
 * 
 * @return {Array<string, mixed>} The next data row as an associative array; FALSE if no more rows
 */ 
function cpdsql_odbc_result_row($result)
{
	return odbc_fetch_array($result);
}


/** Get data from the first column next row of data in a ODBC result object
 *
 * Gets the next row from the given ODBC result object, checks for any errors, and finds the data in
 * the first column and that row and returns it.
 *
 * This is most useful when the result in question only contains one row with one column anyway. The
 * common use case for this is something like "SELECT `name` FROM `person` WHERE `id` = 345" where a
 * single person is going to match that query (and thus the result will only contain one row) and it
 * only has one piece of data in it (the name, which is all we need to get). This saves this step of
 * pulling off the row and grabbing the data every time something like this is done.
 * 
 * Don't forget to use a triple equals ("===") if you need to verify that there actually was another
 * row in the result that contained at least one column. If the column in question is set (with SQL)
 * to NULL or the empty string, that will be returned. On the other hand, if the result set is empty
 * or all the rows have been already been gotten, then FALSE is returned. Thus, a double equals sign
 * ("==") is insufficient to distinguish between an empty value and no value.         
 *
 * @param {ODBC result} $result A ODBC result object from a successful ODBC query 
 * 
 * @return {string} Returns data (including null or "") for success, or FALSE on error
 */
function cpdsql_odbc_result_value($result)
{
	$row = array();
	odbc_fetch_into($result, $row);
	
	if($row)
	{
		return $row[0];
	}
	else
	{
		return false;
	}
}


/** Get the number of rows in an ODBC result set
 *
 * Again, just a wrapper for odbc_num_rows(). Again, this is to give the main CPDSQL module a single
 * API that works on any database engine.
 *  
 * @param {ODBC result} $result A ODBC result object from a successful ODBC query 
 * 
 * @return {int} Returns the number of rows in the result, or FALSE on error
 */
function cpdsql_odbc_result_count($result)
{
	return odbc_num_rows($result);
}


/** Whether a ODBC result contains at least one row
 *
 * This is shorthand for (cpdsql_result_count($result) > 0), and is here just because that is such a
 * common thing to do that it's worth having an extra function for it. Note that the return value is
 * false both for an empty result set and for an invalid one (an error). 
 *
 * @param {ODBC result} $result A ODBC result object from a successful ODBC query 
 * 
 * @return {bool} Whether the given result set contains at least one row
 */ 
function cpdsql_odbc_result_exists($result)
{
	return (odbc_num_rows($result) > 0);
}



/** Get a "default object" array for the given table
 *
 * Creates and returns an associative array (of the type returned by odbc_fetch_array) and used as a
 * parameter in the saving functions in this module, containing the default values for each field in
 * the given table. This is useful, for example, to populate an form with edit widgets corresponding
 * to rows in the table, where the initial values come from the default column values in SQL.
 * 
 * @param {string} $table The name of the table whole default row to return
 * 
 * @return {array<string, mixed>} An associative array of default values for the given table
 */   
function cpdsql_odbc_default_row($table)
{
	$defaults = array();
	
	$columns = cpdsql_odbc_query("SHOW COLUMNS FROM `$table`");
	
	while($col = odbc_fetch_array($columns))
	{
	    $defaults[$col['Field']] = $col['Default'];
	}
	
	return $defaults;
}


/** Verify that a table with the given name actually exists in the database
 *
 * Queries the database for a list of table names, then checks to see if one of them is equal to the
 * value sent. If so, it returns that value. If not, it raises an error and returns FALSE.
 * 
 * @param {string} $table The table name to verify
 * 
 * @return {string} The table name given if it is a valid table in the current database, or FALSE 
 */     
function cpdsql_odbc_verify_table($table)
{
	$defaults = array();
	
	$real_tables = cpdsql_odbc_query("SHOW TABLES");
	
	$real_table = array();	
	while(odbc_fetch_into($real_tables))
	{
	    if($real_table[0] == $table)
	    {
	    	return $real_table[0];
	    }
	}
	
	cpdsql_internal_error("Table verification failed!: `$table`");
	return false;
}


/** Verify that a column with the given name actually exists in the given database
 *
 * Queries the database for a list of columns in the given table, then verified that one of them has
 * the same name as the given column name. If so, returns that value. If not, it raises an error and
 * returns FALSE. Note that this doesn't automatically verify the table name, so if both a table and
 * a column name come from outside input, that will have to be verified before verifying any columns
 * here, possibly with a call like cpdsql_verify_column(cpdsql_verify_table($table), $col); 
 * 
 * @param {string} $table The table to check
 * @param {string} $col   The column name to verify
 * 
 * @return {string} The table name given if it is a valid table in the current database, or FALSE
 */
function cpdsql_odbc_verify_column($table, $column)
{
	$defaults = array();
	
	$real_columns = cpdsql_odbc_query("SHOW COLUMNS FROM `$table`");
	
	while($real_column = odbc_fetch_array($real_columns))
	{
	    if($real_column['Field'] == $column)
	    {
	    	return $real_column['Field'];
	    }
	}
	
	cpdsql_internal_error("Column verification failed!: `$table`.`$col`");
	return false;
}

?>