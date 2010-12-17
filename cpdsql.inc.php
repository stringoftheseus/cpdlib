<?
/** CPDSQL: CPD Library SQL Module
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
 * CPDSQL is designed to simplify the common PHP development task of reading and writing to MySQL or
 * other SQL database systems. It does so in two primary ways. First, the cpdsql functions can often
 * turn what would be a complicated piece of PHP code into a single call by encapsulating common SQL
 * usage patterns into a predictable, easy to use API. Secondly, all cpdsql functions are "injection
 * aware," meaning that their design is such as to help you write safe SQL queries while helping you
 * avoid accidentally introducing SQL injection vulnerabilities. While CPDSQL cannot prevent or stop
 * all possible security issues (see disclaimer of warranty above ;)), security conscious developers
 * will find it significantly reduces the work required to prevent SQL injection vulnerabilities. In
 * addition, CPDSQL aims to improve the readability of PHP code that uses it by reducing SQL clutter
 * and copy/paste blocks.
 * 
 * Like all modules in the CPD Library, CPDSQL is a "take it or leave it" library, meaning that each
 * function returns either an SQL result object, a native PHP type, or an array of PHP natives. Thus
 * using CPDSQL in one part of your code does not require that you do so in any other. Processing of
 * the data returned by SQL is the same as it always was; CPDSQL merely provides an easier method of
 * obtaining that data from the database.
 * 
 * The main function in the CPDSQL library is cpdsql, which runs any arbitrary SQL query and returns
 * the result. To this function base may be added one of several Type Specifiers which control which
 * type or subset of data is returned, followed by one of several optional Conditional Specifiers to
 * create an SQL WHERE clause or other conditional statement. The following lists the Type Specifier
 * and Conditional Specifier options currently available:
 * 
 * Type Specifiers 
 *    TYPE     RETURN VALUE 
 *    (none)   an SQL result object obtained by executing the given query against the database
 *    make     the resulting SQL query string (which, if executed, would result in the (none) type)
 *    clause   the WHERE or LIKE clause for the resulting query; only works for WHERE, LIKE, and ID  
 *    array    the entire SQL result for the given query as a PHP array of associative arrays 
 *    row      an associative array containing the first row of the SQL result for the given query
 *    value    the value of the first column in the first row of the SQL result for the given query
 *    count    the number of rows in the SQL result for the given query
 *    exists   a boolean value indicating whether the SQL result for the given query is nonempty 
 *    
 *    update   updates matching rows using the given data; works for WHERE, LIKE, and ID
 *    delete   deletes matching rows and returns the results; no (none) condition version
 *      
 * 
 * Conditional Specifiers
 *    COND    PARAMETERS                  RESULTING QUERY
 *    (none)  an SQL query, values        Arbitrary SQL interpolated in the style of printf
 *    all     a table;                    "SELECT * FROM `table`"
 *    where   a table, column, and value  "SELECT * FROM `table` WHERE `column` = 'value'"
 *    like    a table, column, and value  "SELECT * FROM `table` WHERE `column` LIKE 'value'" 
 *    id      a table and number;         "SELECT * FROM `table` WHERE `id` = number"     
 *
 * Type and Conditional specifiers are combined in that order using the underscore character to make
 * the names of the query functions in the CPDSQL modules. All 35 possible combinations exist and do
 * what they should, even when they are slightly redundant or completely useless. The parameter type
 * and count of each function is determined by the Conditional Specifier used, with the return types
 * and determined by the Type Specifiers. Thus, one need only understand the specifiers to use every
 * function in the library, without having to read the docs for them all individually. Full info for
 * each specifier is available below as documentation to the function containing only that specifier
 * (i.e., cpdsql() and cpdsql_<specifier>() where <specifier> is a Type or Condition specifier). The
 * sort parameter, used with the conditional specifiers, is explained in detail in the documentation
 * for the cpdsql_clause_order() function near the end of this file; reading that is recommended.  
 * 
 * The last few paragraphs might make this all sounds really complicated, but it really isn't. After
 * "getting the feel of it" CPDSQL is very intuitive, easy to use, and quite a bit of fun. Of course
 * all of the above is just the SQL query functions. In addition, we also have a few other functions
 * for inserts and updates, the details of which can be found in the docs for each such function.
 */

require_once 'cpdsql.conf.php';
require_once 'cpdsql-'._cpdsql_conf('engine').'.inc.php';

if(_cpdsql_conf('engine_error', 'log') || _cpdsql_conf('cpdsql_error', 'log'))
{
	require_once 'cpdlog.inc.php';
}



/** Perform a safe query on the database
 *
 * This is the equivalent of calling mysql_query using sprinf and mysql_real_escape_string like this
 * mysql_query(sprintf($query, mysql_real_escape_string($var1), ...)) (and so on) and is pretty much
 * just that code and some checks. One annoying quirk of this function is that you MUST call it with
 * at least one $var parameter. If you don't need any, add a NULL param to the end of the call. This
 * is a bit of syntactic salt designed to make it a bit more difficult for people to call this wrong
 * (with the full, unquoted values in the query string) and open up SQL injection errors. That won't
 * prevent them completely, of course, but might get someone to look in here before writing queries,
 * where he'll hopefully read these docs and learn what not to do.
 * 
 * In fact, this is as good a place as any for a crash course in SQL injection errors. If you do SQL
 * queries like this: mysql_query("SELECT * FROM `user` WHERE `name` = '$username'"); your page will
 * be hacked. I repeat, if you do that you WILL BE HACKED. The reason is that in order to break it a
 * malicious user (of which there are plenty) only has to load your page with a username value some-
 * thing like this: index.php?username=a' OR 1=1; DROP DATABASE main; -- and your safe user query is
 * now: SELECT * FROM `user` WHERE `name` = 'a' OR 1=1; DROP DATABASE main; --' or whatever else the
 * hacker wants. Essentially, you've just given anyone on the internet complete and total permission
 * to do whatever he wants with your database. This is not a theoretical hack. Big organizations and
 * important companies have been burned by it, mostly because it's so stinking easy to forget to use
 * the correct method (and even easier to never have known about this in the first place). There are
 * several ways to prevent this, but the easiest is to use sprintf with mysql_real_escape_string, as
 * shown in the php.net docs for mysql_query. This function merely encapsulates that into one step. 
 * 
 * Note that as of the 2.x series, this function doesn't actually use sprintf internally. Rather, it
 * has an internal sprintf-like string formatting function that correctly escapes string, float, and
 * integer values using mysql_real_escape_string, intval, and floatval respectively. The downside of
 * this is that we don't support the full and complete printf syntax. Specifically, only %s, %d, and
 * %f (strings, ints, and floats) are supported, along with the argument swapping abilities. Padding
 * is not currently supported, nor are precision specifiers and other printf capabilities that don't
 * commonly come up in SQL queries. %% is converted to a literal '%' character, though doing this is
 * only needed if the character after the % is s, d, or f.
 * 
 * For more info on all of this, see the php.net docs for mysql_query, mysql_real_escape_string, and
 * sprintf. Also, google "SQL injection" and read up on it a bit.
 * 
 * This function can also take a single parameter, it being an array of parameters in the same order
 * as shown here, which is used internally to make it nicer for other functions that take a variable
 * number of parameters to call this one. This might also occasionally be useful for external code.
 *  
 * 
 * @param {string} $query The SQL query to make, formatted as an sprintf string.
 * @param {string} $var   Data for the $query, which will be automatically escaped for security.
 *
 * @return {SQL result} The sql result object, to be used with mysql_fetch_assoc etc
 */
function cpdsql($query/*,$var1, $var2, $var3, ...*/)
{
	return cpdsql_query(cpdsql_make(is_array($query) ? $query : func_get_args()));
}


/** Create and return a safe SQL query using sprintf-style syntax with type-specific escaping
 *
 * Performs the variable data replacement and smart escaping of cpdsql(), but returns the query made
 * instead of executing it. This is useful if you want to use CPDSQL's query creation syntax without
 * executing the actual query via cpdsql (or at all). This uses cpdsql_escape_string, which for some
 * SQL engines (including MySQL) requires that there by an active SQL connection for it to work.   
 * 
 * The cpdsql() function is (of course) implemented via a call to here.
 * 
 * This function can also take a single parameter, it being an array of parameters in the same order
 * as shown here, which is used internally to make it nicer for other functions that take a variable
 * number of parameters to call this one. This might also occasionally be useful for external code.
 *
 * @param {string} $query The SQL query to make, formatted as an sprintf string.
 * @param {string} $var   Data for the $query, which will be automatically escaped for security.
 *
 * @return {string} The resulting SQL query string, to be used with cpdsql(), mysql_query() etc
 */ 
function cpdsql_make($query/*,$var1, $var2, $var3, ...*/)
{
	// Detect and handle a "one big array" type call or a classic call
	$args = is_array($query) ? $query : func_get_args();

	$input = $args[0];
	$values = array_slice($args, 1);

	
	// Check for various errors in the query string
	if(_cpdsql_conf('enable_no_data_error') && count($values) == 0)
	{
		_cpdsql_internal_error("No data! Query: $input");
	}
	
	if(_cpdsql_conf('enable_bare_string_error') && preg_match('/=\s*%(\d+\$)?s/', $input))
	{
		_cpdsql_internal_error("Bare string! Query: $input");
	}
	
	
	// Do the actual sprintf-style replacement
	$sql = preg_replace_callback('/%(?:(\d+)\$)?([sdf])/' , function($matches) use ($input, $values) {
		static $i = 0;	
		$n = $matches[1] ? $matches[1]-1 : $i++;

		if($n >= count($values))
		{
			_cpdsql_internal_error("SQL data replacement out of range: $input");
		}
		else
		{		
			switch($matches[2])
			{
				case 's': return cpdsql_escape_string($values[$n]);
				case 'd': return intval($values[$n]);
				case 'f': return floatval($values[$n]);
				_cpdsql_internal_error("SQL data replacement error: unknown type");
			}
		}
	}, $input);
	
	$sql = str_replace('%%', '%', $sql);
	
	
	return $sql;
}


/** Get the result set corresponding to the full (SELECT *) query on a table
 *
 * This is just a slightly shorter way of writing the "SELECT * FROM `tableName`" query. These types
 * of queries are performed quite frequently in some cases, so this was written as a handy shortcut
 * 
 * Remember, NEVER ever EVER send in a column name taken in any way from user input without checking
 * to make absolutely sure it comes from a predefined list of valid columns. These functions have no
 * protection against that kind of injection attacks, only against data value injections. To check a
 * table or column name to make it sure is valid in the current database or given table, you can use
 * the cpdsql_verify_table() and cpdsql_verify_column() functions which check the given name against
 * the database-engine-supplied list of tables or columns to make sure that it is valid.    
 *
 * @param {string} $table The name of the table whose data to return
 * @param {mixed}  $sort  The column to sort by; see the docs for cpdsql_clause_order for more info.
ÿ   
 * @return {SQL result} The sql result object corresponding to the full query on the selected table
 */
function cpdsql_all($table, $sort = null)
{
	return cpdsql_query(cpdsql_make_all($table, $sort));
}


/** Get the SQL string for a full (SELECT *) query on a table
 *
 * Performs the query creation function of cpdsql_all, but returns the created SQL string instead of
 * executing it. The cpdsql_all function is implemented with a call to this one. 
 *
 * @param {string} $table The name of the table whose data to return
 * @param {mixed}  $sort  The column to sort by; see the docs for cpdsql_clause_order for more info.
 * 
 * @return {string} The "SELECT *" SQL query string, to be used with cpdsql(), mysql_query() etc 
 */  
function cpdsql_make_all($table, $sort = null)
{
	return "SELECT * FROM `$table` ".cpdsql_clause_order($sort);
}


/** Perform a database query with a simple where clause
 *
 * A very common task in SQL is the "SELECT * FROM `table` WHERE `column` = 'value'" query. This was
 * designed as a simple shortcut for doing that, since you only need to provide the table from which
 * data should come, the column to search on, and the value to match against.
 * 
 * Two versions of an advanced syntax are now available for creating more complex WHERE clauses. The
 * first allows the $column and $value variables to both be arrays of column names and values, which
 * results in an AND-based WHERE using columns from the column array and corresponding values in the
 * value array. The second allows the $column parameter to be an associative array with column names
 * used as keys and the associated values as values, with the $value array equal to null or omitted.
 * Both versions allow for the same level of power in the resulting query, but one may be easier for
 * some scripts to use than the other, so both are provided. Scripts requiring WHERE logic even more
 * complex than this should use cpdsql() to create the needed query directly.
 * 
 * See the docs for cpdsql_all for information on how to handle table and column names supplied from
 * outside data.
 *
 * @param {string|array} $table  The table whose data to return
 * @param {string|array} $column The name of the SQL column whose value match against
 * @param {string}       $value  The value to match for
 * @param {mixed}        $sort   The sort to use; see the docs for cpdsql_clause_order for more info
 * 
 * @return {SQL result} The SQL result object for the resulting query
 */
function cpdsql_where($table, $column, $value = null, $sort = null)
{
	return cpdsql_query(cpdsql_make_where($table, $column, $value, $sort));
}


/** Get the SQL string for a query with a simple where clause
 *
 * Performs the query generation function of cpdsql_where(), but returns the resulting query instead
 * of executing it. cpdsql_where() is implemented via a call to this function. See the documentation
 * for cpdsql_where for full details on how this works and what each of the parameters are. 
 *
 * @param {string|array} $table  The table whose data to return
 * @param {string|array} $column The name of the SQL column whose value match against
 * @param {string}       $value  The value to match for
 * @param {mixed}        $sort   The sort to use; see the docs for cpdsql_clause_order for more info
 * 
 * @return {string} The SQL string for the resulting query 
 */ 
function cpdsql_make_where($table, $column, $value = null, $sort = null)
{	
	$sql_where = cpdsql_clause_where($table, $column, $value, $sort);
	$sql_sort = cpdsql_clause_order($sort);
	
	return "SELECT * FROM `$table` $sql_where $sql_sort";
}


/** Get a simple WHERE clause for an SQL query
 *
 * Performs the query generation function of cpdsql_where(), but returns the WHERE clause portion of
 * the resulting SQL instead of executing it. cpdsql_make_where() (and all the _where functions that
 * call it), cpdsql_update_where(), and cpdsql_delete_where() all use this function to create their
 * respective queries.
 * 
 * See the documentation for cpdsql_where for full details on the different ways of calling this and
 * what each of the parameters are. 
 *
 * @param {string|array} $table  The table whose data to return
 * @param {string|array} $column The name of the SQL column whose value match against
 * @param {string}       $value  The value to match for
 * @param {mixed}        $sort   The sort to use; see the docs for cpdsql_clause_order for more info
 * 
 * @return {string} The SQL string for the resulting query 
 */ 
function cpdsql_clause_where($table, $column, $value = null, $sort = null)
{
	if(is_string($column) && is_scalar($value))
	{
		$cols = array($column => $value);
	}
	else if(is_array($column) && is_array($value))
	{
		$cols = array_combine($column, $value);
	}
	else if(is_array($column) && $value == null)
	{
		$cols = $column;
	}
	else
	{
		_cpdsql_internal_error("Invalid input to cpdsql_make_where");
	}
	
	$wheres = array();
	foreach($cols as $col => $value)
	{
		$wheres[] = "`$col` = '".cpdsql_escape_string($value)."'";
	}
	
	$sql_where = implode(" AND ", $wheres);
	
	return "WHERE $sql_where";
}


/** Perform a database query with a WHERE LIKE clause
 *
 * Operates identical to cpdsql_where() except that the resulting query uses LIKE matches instead of
 * testing for equality (i.e., builds "SELECT * FROM `table` WHERE `column` LIKE `value`" instead of
 * the "SELECT * FROM `table` WHERE `column` = `value`" queries that cpdsql_where builds).
 *
 * See the docs for cpdsql_where() for a full description of the different options available for the
 * $column and $value parameters, including how those can be arrays of strings to create advanced or
 * complex WHERE LIKE queries.    
 * 
 * See the docs for cpdsql_all for information on how to handle table and column names supplied from
 * outside data.
 *
 * @param {string|array} $table  The table whose data to return
 * @param {string|array} $column The name of the SQL column whose value match against
 * @param {string}       $value  The value to match for
 * @param {mixed}        $sort   The sort to use; see the docs for cpdsql_clause_order for more info
 * 
 * @return {SQL result} The SQL result object for the resulting query
 */
function cpdsql_like($table, $column, $value = null, $sort = null)
{
	return cpdsql_query(cpdsql_make_like($table, $column, $value, $sort));
}


/** Get the SQL string for a query with a simple WHERE LIKE clause
 *
 * Performs the query generation function of cpdsql_like, but returns the resulting query instead of
 * executing it. cpdsql_where() is implemented via a call to this function. See the documentation on
 * cpdsql_like and cpdsql_where for details on how this works and what each of the parameters are. 
 *
 * @param {string|array} $table  The table whose data to return
 * @param {string|array} $column The name of the SQL column whose value match against
 * @param {string}       $value  The value to match against
 * @param {mixed}        $sort   The sort to use; see the docs for cpdsql_clause_order for more info
 * 
 * @return {string} The SQL string for the resulting query 
 */ 
function cpdsql_make_like($table, $column, $value = null, $sort = null)
{
	$sql_where = cpdsql_clause_like($table, $column);
	$sql_sort =  cpdsql_clause_order($sort);
	
	return "SELECT * FROM `$table` $sql_where $sql_sort";
}


/** Get a simple LIKE clause for an SQL query
 *
 * Performs the query generation function of cpdsql_like, but returns the LIKE clause portion of the
 * resulting query instead of executing it. cpdsql_like() is implemented via a call to this function
 * via cpdsql_make_like(). See the docs for cpdsql_like and cpdsql_where for details on how all this
 * works and what each of the parameters are. 
 *
 * @param {string|array} $table  The table whose data to return
 * @param {string|array} $column The name of the SQL column whose value match against
 * @param {string}       $value  The value to match against
 * @param {mixed}        $sort   The sort to use; see the docs for cpdsql_clause_order for more info
 * 
 * @return {string} The SQL string for the resulting query 
 */ 
function cpdsql_clause_like($table, $column, $value = null)
{
	if(is_string($column) && is_string($value))
	{
		$cols = array($column => $value);
	}
	else if(is_array($column) && is_array($value))
	{
		$cols = array_combine($column, $value);
	}
	else if(is_array($column) && $value == null)
	{
		$cols = $column;
	}
	else
	{
		_cpdsql_internal_error("Invalid input to cpdsql_make_like");
	}
	
	$wheres = array();
	foreach($cols as $col => $value)
	{
		$wheres[] = "`$col` LIKE '".cpdsql_escape_string($value)."'";
	}
	
	$sql_where = implode(" AND ", $wheres);
	
	return "WHERE $sql_where";
}


/** Get a database result based on an `id` column
 *
 * Since 90% of the primary key columns are called `id`, we end up doing a ton of queries with WHERE
 * clauses on that column. This function makes that a little bit faster by allowing you to just give
 * the table name and id value and get the corresponding result.
 * 
 * The vast majority of the time you probably want to use cpdsql_row_id instead, since that function
 * returns the first (usually only) row for you automatically. This function is useful only if an id
 * column isn't unique (which is rare), or if you explicitly want an SQL result object instead of an
 * associative array.   
 * 
 * @param {string} $table The table whose data to return
 * @param {string} $id    The integer value to match against the `id` column
 * @param {mixed}  $sort  The column to sort by; see the docs for cpdsql_clause_order for more info
 * 
 * @param {SQL result} The SQL result created from finding the given `id` value in the given table
 */
function cpdsql_id($table, $id, $sort = null)
{
	return cpdsql_query(cpdsql_make_id($table, $id, $sort));
}


/** Get the SQL string for a query based on searching an `id` column
 *
 * Works like cpdsql_id() except returns the resulting SQL query string instead of executing it. For
 * details of how this works and why you'd use it, see the docs for cpdsql_id(). 
 *
 */ 
function cpdsql_make_id($table, $id, $sort = null)
{
	return "SELECT * FROM `$table`".cpdsql_clause_id($table, $id);
}

/** Get the SQL WHERE clause for a query based on searching an `id` column
 *
 * Works like cpdsql_make_id() except returns just the resulting WHERE clause instead of all the SQL
 * query. For details of how this works and why you'd use it, see the docs for cpdsql_id(). 
 */ 
function cpdsql_clause_id($table, $id)
{
	return "WHERE `id` = ".intval($id);
}


/** Perform an arbitrary database query and return the result as an array of associative arrays
 *
 * Alias for cpdsql_result_array(cpdsql(...));
 * See the docs for those function for more information about the parameters and return value.  
 *
 * @param {string} $query The SQL query to make, formatted as an sprintf string.
 * @param {string} $var   Data for the $query, which will be automatically escaped for security.
 * 
 * @return {Array<Array<string, mixed>>} An array of PHP associative arrays with all result rows   
 */ 
function cpdsql_array($query/*, $var1, $var2, $var3, ...*/)
{
	return cpdsql_result_array(cpdsql(func_get_args()));
}


/** Perform a "SELECT * FROM `table`" query and return the result as an array of associative arrays
 *
 * Alias for cpdsql_result_array(cpdsql_all(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * This basically dumps an entire table into a two-dimensional array, which can then be iterated and
 * accessed with standard PHP functions and coding techniques. This is extremely useful occasionally
 * when something requires this type of data structure, but dangerous if overused. See the paragraph
 * about this in the docs for cpdsql_result_array for more information about the latter.
 */ 
function cpdsql_array_all($table, $sort = null)
{
	return cpdsql_result_array(cpdsql_all($table, $sort));
}


/** Perform a simple WHERE query and return the result as an array of associative arrays
 *
 * Alias for cpdsql_result_array(cpdsql_where(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file. 
 */ 
function cpdsql_array_where($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_array(cpdsql_where($table, $column, $value, $sort));
}


/** Perform a simple WHERE LIKE query and return the result as an array of associative arrays
 *
 * Alias for cpdsql_result_array(cpdsql_like(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file. 
 */ 
function cpdsql_array_like($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_array(cpdsql_like($table, $column, $value, $sort));
}


/** Get database rows with the given id and return the result as an array of associative arrays
 *
 * Alias for cpdsql_result_array(cpdsql_id(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * Essentially useless unless your id column isn't unique (which is rare) or you need the particular
 * data format cpdsql_result_array provides. Usually cpdsql_row_id is a better choice, since matches
 * against an id column usually only return one row by design.
 */ 
function cpdsql_array_id($table, $id, $sort = null)
{
	return cpdsql_result_array(cpdsql_id($table, $id, $sort));
}


/** Perform an arbitrary database query and get the first result row as an associative array
 *
 * Alias for cpdsql_result_row(cpdsql(...));
 * See the docs for those function for more information about the parameters and return value.
 * 
 * Most commonly used for queries in which only one row will be returned anyway; this then saves the
 * step of having to pull off the first row of the (single row) result set before accessing the data
 * contained in it.
 */ 
function cpdsql_row($query, $var1/*, $var2, $var3, ...*/)
{
	return cpdsql_result_row(cpdsql(func_get_args()));
}


/** Perform a "SELECT * FROM `table`" query and get the first result row as an associative array
 *
 * Alias for cpdsql_result_row(cpdsql_all(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * Usually not very useful unless the table contains only one row, or all rows are equivalent. Sorts
 * can also be used to obtain the row with the lowest or highest value in some column.
 */ 
function cpdsql_row_all($table, $sort = null)
{
	return cpdsql_result_row(cpdsql_all($table, $sort));
}


/** Perform a simple WHERE query and get the first result row as an associative array
 *
 * Alias for cpdsql_result_row(cpdsql_where(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * Most commonly used for queries in which only one row will be returned anyway, often because a key
 * (primary or unique) column is used as the second parameter; this then saves the step of obtaining
 * the first row of the (single row) result set before accessing the data contained in it.  
 */ 
function cpdsql_row_where($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_row(cpdsql_where($table, $column, $value, $sort));
}


/** Perform a simple WHERE LIKE query and get the first result row as an associative array
 *
 * Alias for cpdsql_result_row(cpdsql_like(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * Most commonly used for queries in which only one row will be returned anyway, often because a key
 * (primary or unique) column is used as the second parameter; this then saves the step of obtaining
 * the first row of the (single row) result set before accessing the data contained in it.  
 */ 
function cpdsql_row_like($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_row(cpdsql_like($table, $column, $value, $sort));
}


/** Get the first database row with the given id as an associative array
 *
 * Alias for cpdsql_result_row(cpdsql_id(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * The most useful of the _id family; automatically performs the very common task of getting one row
 * from the database based on the value of a column called id. Sorts are mostly useless unless there
 * is more than one row with the given id (which is rare, since id is usually a unique key), but are
 * available in order to maintain parameter consistency with the other functions.   
 */ 
function cpdsql_row_id($table, $id, $sort = null)
{
	return cpdsql_result_row(cpdsql_id($table, $id, $sort));
}


/** Perform an arbitrary database query and get the first column of the first result row
 *
 * Alias for cpdsql_result_value(cpdsql(...));
 * See the docs for those function for more information about the parameters and return value.
 */ 
function cpdsql_value($query /*,$var1, $var2, $var3, ...*/)
{
	return cpdsql_result_value(cpdsql(func_get_args()));
}


/** Perform a "SELECT * FROM `table`" query and get the first column of the first result row
 *
 * Alias for cpdsql_result_value(cpdsql_all(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * Not particularly useful for very many real-world situations.  
 */ 
function cpdsql_value_all($table, $sort = null)
{
	return cpdsql_result_value(cpdsql_all($table, $sort));
}


/** Perform a simple WHERE query and get the first column of the first result row
 *
 * Alias for cpdsql_result_value(cpdsql_where(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * This can be used to get the primary key (usually the id column) of the first row that matches the
 * generated where part, since the primary key is usually the first column in the table.
 */ 
function cpdsql_value_where($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_value(cpdsql_where($table, $column, $value, $sort));
}


/** Perform a simple WHERE LIKE query and get the first column of the first result row
 *
 * Alias for cpdsql_result_value(cpdsql_like(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * This can be used to get the primary key (usually the id column) of the first row that matches the
 * generated where part, since the primary key is usually the first column in the table.
 */ 
function cpdsql_value_like($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_value(cpdsql_like($table, $column, $value, $sort));
}


/** Match rows by id value get the first column of the first result row
 *
 * Alias for cpdsql_result_value(cpdsql_id(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * Not very useful unless the id column isn't the first column in the table (which it usually is).
 */
function cpdsql_value_id($table, $id, $sort = null)
{
	return cpdsql_result_value(cpdsql_id($table, $id, $sort));
}


/** Perform an arbitrary query and get the number of rows returned
 *
 * Alias for cpdsql_result_count(cpdsql(...));
 * See the docs for those function for more information about the parameters and return value.
 * 
 * Useful for situations in which actual contents of the matching rows doesn't actually matter, just
 * the fact that at least (or at most, or exactly) some number of such rows exist.
 */
function cpdsql_count($query /*, $var1, $var2, $var3, ...*/)
{
	return cpdsql_result_count(cpdsql(func_get_args()));
}


/** Perform a "SELECT * FROM `table`" query and get the number of rows returned
 *
 * Alias for cpdsql_result_count(cpdsql_all(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * Returns the number of rows in the given table, which is often fairly useful.  
 */
function cpdsql_count_all($table, $sort = null)
{
	return cpdsql_result_count(cpdsql_all($table, $sort));
}


/** Perform a simple WHERE query and get the number of rows returned
 *
 * Alias for cpdsql_result_count(cpdsql_where(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 *
 * Useful for situations in which actual contents of the matching rows doesn't actually matter, just
 * the fact that at least (or at most, or exactly) some number of such rows exist. Answers questions
 * like "how many government orders do we have?" or "are there are least two midsize vehicles left?"
 * and other "count the rows with the given data in the given column" problems.
 */
function cpdsql_count_where($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_count(cpdsql_where($table, $column, $value, $sort));
}


/** Perform a simple WHERE LIKE query and get the number of rows returned
 *
 * Alias for cpdsql_result_count(cpdsql_like(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 *
 * Useful for situations in which actual contents of the matching rows doesn't actually matter, just
 * the fact that at least (or at most, or exactly) some number of such rows exist. Answers questions
 * like "how many government orders do we have?" or "are there are least two midsize vehicles left?"
 * and other "count the rows with the given data in the given column" problems.
 */
function cpdsql_count_like($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_count(cpdsql_like($table, $column, $value, $sort));
}


/** Match rows by id and get the number of rows returned
 *
 * Alias for cpdsql_result_count(cpdsql_id(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * If id is a primary or unique key, this will either be 0 or 1 (in which case cpdsql_exists_id() is
 * often a better choice).   
 */
function cpdsql_count_id($table, $id, $sort = null)
{
	return cpdsql_result_count(cpdsql_id($table, $id, $sort));
}


/** Perform an arbitrary query and determine whether the result is nonempty
 *
 * Alias for cpdsql_result_exists(cpdsql(...));
 * See the docs for those function for more information about the parameters and return value.
 */
function cpdsql_exists($query/*, $var1, $var2, $var3, ...*/)
{
	return cpdsql_result_exists(cpdsql(func_get_args()));
}


/** Perform a "SELECT * FROM `table`" query and determine whether the result is nonempty
 *
 * Alias for cpdsql_result_exists(cpdsql_all(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 * 
 * This is basically an is_table_nonempty() function with a slight unintuitive name, at least coming
 * from the idea of what it does (coming from cpdsql as a whole it makes perfect sence).    
 */
function cpdsql_exists_all($table, $sort = null)
{
	return cpdsql_result_exists(cpdsql_all($table, $sort));
}


/** Perform a simple WHERE query and determine whether the result is nonempty
 *
 * Alias for cpdsql_result_exists(cpdsql_where(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 */
function cpdsql_exists_where($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_exists(cpdsql_where($table, $column, $value, $sort)); 
}


/** Perform a simple WHERE LIKE query and determine whether the result is nonempty
 *
 * Alias for cpdsql_result_exists(cpdsql_like(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 */
function cpdsql_exists_like($table, $column, $value = null, $sort = null)
{
	return cpdsql_result_exists(cpdsql_like($table, $column, $value, $sort));
}


/** Match rows by id and determine whether the result is nonempty
 *
 * Alias for cpdsql_result_exists(cpdsql_id(...));
 * See the docs for those function for more information about the parameters and return value. Sorts
 * are documented fully in the cpdsql_clause_order function near the bottom of this file.
 */
function cpdsql_exists_id($table, $id, $sort = null)
{
	return cpdsql_result_exists(cpdsql_id($table, $id, $sort));
}





/** Delete all the rows from an SQL table
 *
 * This is just like cpdsql_all(), only we delete the resulting rows instead of returning them. It's
 * best not to confuse the two. ;) 
 * 
 * Remember, NEVER ever EVER send in a column name taken in any way from user input without checking
 * to make absolutely sure it comes from a predefined list of valid columns. These functions have no
 * protection against that kind of injection attacks, only against data value injections. To check a
 * table or column name to make it sure is valid in the current database or given table, you can use
 * the cpdsql_verify_table() and cpdsql_verify_column() functions which check the given name against
 * the database-engine-supplied list of tables or columns to make sure that it is valid.    
 * 
 * In reality, this just does a TRUNCATE on the table you give it.  
 */
function cpdsql_delete_all($table)
{
	cpdsql_query("TRUNCATE `$table`");
}


/** Perform a simple WHERE query and delete the resulting rows
 *
 * This is just like cpdsql_where, only it deletes the resulting rows instead of returning them. See
 * the docs for cpdsql_delete and cpdsql_where for more information.   
 */
function cpdsql_delete_where($table, $column, $value = null)
{
	$sql_where = cpdsql_clause_where($table, $column, $value);
	
	return cpdsql_query("DELETE FROM `$table` $sql_where");
}


/** Perform a simple WHERE LIKE query and delete the resulting rows
 *
 * This is just like cpdsql_like() only it deletes the resulting rows instead of returning them. See
 * the docs for cpdsql_delete and cpdsql_like for more information.   
 */
function cpdsql_delete_like($table, $column, $value = null, $sort = null)
{
	$sql_where = cpdsql_clause_like($table, $column, $value);
	
	return cpdsql_query("DELETE FROM `$table` $sql_where");
}


/** Match rows by id and delete them
 *
 * This is just like cpdsql_id(), only it deletes the resulting row instead of returning it. See the 
 * docs for cpdsql_delete and cpdsql_like for more information.   
 */
function cpdsql_delete_id($table, $id)
{
	$sql_where = cpdsql_clause_id($table, $id);
	
	return cpdsql_query("DELETE FROM `$table` $sql_where");
}



/** Format SQL sort code based on the sort column or array provided
 *
 * Takes a sort column name or array and returns a valid SQL ORDER BY clause (including "ORDER BY")
 * 
 * There are four options for the sort parameter, providing for a few different levels of complexity
 * in the resulting sort. First, if $sort is omitted or set to null, no ORDER BY clause will be made
 * so the data will come back in whatever order the SQL engine feels like. This is the fastest query
 * to create and execute, and is ideal for situations where you don't actually care what order stuff
 * comes back in. The second option to specify a single column name in the $sort parameter, in which
 * case the data will be sorted by that column using the default (ascending) order. To sort the data
 * by two or more columns, a simple array of column names may be sent, and the sort will be by those
 * columns in the array order. Finally, to explicitly specify sort order for each column in the sort
 * the function accepts an associative array with keys set column names and all values set to either
 * 'asc' or 'desc'; the sort will be by column in the array order, either ascending or descending to
 * match the value of each key. This format is used by every cpdsql query function that uses a $sort
 * input parameter, though isn't described in detail in the docs for each one.
 */ 
function cpdsql_clause_order($sort = null)
{
	if($sort == false)
	{
		return "";
	}
	else if(is_string($sort))
	{
		return "ORDER BY `$sort`";
	}
	else if(is_array($sort))
	{
		$colsorts = array();
		
		foreach($sort as $key => $value)
		{
			if(is_int($key))
			{
				$colsorts[] = "`$value`";
			}
			else if(is_string($key))
			{
				$colsorts[] = "`$key` ".strtoupper($value);
			}
			else
			{
				_cpdsql_internal_error("Invalid sort column: ".print_r($sort, true));
			}
		}
		
		return 'ORDER BY '.implode(", ", $colsorts);
	}
	else
	{
		_cpdsql_internal_error("Invalid sort type: $sort");
	
		return "";
	}
}






/** Insert a new row into a table based on an associative array
 *
 * The $data parameter should be a PHP associative array containing column names as keys and data to
 * insert in each column as the corresponding values. That data will be used with the given table to
 * create an INSERT INTO `table` (...) VALUES (...) query, which will then be executed. These arrays
 * (i.e., the ones sent as input here) are identical in format to the ones returned by cpdsql_row(),
 * mysql_fetch_assoc(), and related query functions, which means that one can extract some data from
 * a table, modify it in place, then send the resulting array directly back here to be inserted into
 * the original or another table as a new row.     
 * 
 * This is an extremely intuitive and easy way to create a new database row, and it even escapes all
 * the data values automatically (just like the query functions) to prevent injection attacks. (Note
 * that the table and column names are not escaped, so those should either come from a constant list
 * or be verified by cpdsql_verify_table and cpdsql_verify_column before using them here).
 *
 * @param {string}               $table Name of the table to insert into
 * @param {array<string, mixed>} $data  The data for the new row, as an associative array  
 */  
function cpdsql_insert($table, $data)
{
	$columns = array_keys($data);
	$escapedValues = array_map('cpdsql_escape_string', array_values($data));
	
	$columnSQL = '(`'.implode('`, `', $columns).'`)';
	$valuesSQL = '("'.implode('", "', $escapedValues).'")';
	
	return cpdsql_query("INSERT INTO `$table` $columnSQL VALUES $valuesSQL;");
}


/** Update an existing row in a database table
 *
 * Takes the same input format as cpdsql_insert, with the addition of the $key parameter, which sets
 * the name of the column to use to identify the row to update (usually this is the primary key). It
 * constructs an UPDATE `table` SET `column` = 'value' WHERE `key` = 'value' query using the data in
 * the $data array. For that to work, the $data array must contain a key corresponding to the column
 * specified by the $key parameter, whose data is the key value to match against. All other cols are
 * optional, at least to the extent that the resulting query will work as an SQL statement.
 * 
 * As before, the table and column names are not automatically escaped, so be sure that they are not
 * derived from outside input or have all been tested for validity using the cpdsql_verify_table and
 * cpdsql_verify_column functions.  
 *
 * @param {string}               $table Name of the table to update
 * @param {string}               $key   Name of the key column
 * @param {array<string, mixed>} $data  Data for the new row, as an associative array
 *
 * @return {object} TRUE on success or FALSE on error
 */  
function cpdsql_update($table, $key, $data)
{
	$index = $data[$key];
	unset($data[$key]);
	
	$updateSQL = cpdsql_clause_set($data);
	$indexEscaped = cpdsql_escape_string($index);
	
	return cpdsql_query("UPDATE `$table` $updateSQL WHERE `$key` = '$indexEscaped'"); 
}


/** Update rows in the database using a simple WHERE clause
 *
 * Performs the function of cpdsql_update, but uses a standard simple WHERE clause to select the row
 * or rows to update. See the docs for cpdsql_update and cpdsql_where for more details on the result
 * and parameters respectively.
 * 
 * @param {string}               $table Name of the table to update
 * @param {string|array}         $column The name of the SQL column whose value match against
 * @param {string}               $value  The value to match for 
 * @param {array<string, mixed>} $data  Data for the new row, as an associative array
 * 
 * @return {object} TRUE on success or FALSE on error 
 */ 
function cpdsql_update_where($table, $column, $value = null, $data)
{
	$updateSQL = cpdsql_clause_set($data);
	$whereSQL = cpdsql_clause_where($table, $column, $value);
	
	return cpdsql_query("UPDATE `$table` $updateSQL $whereSQL"); 
}


/** Update rows in the database using a simple LIKE clause
 *
 * Performs the function of cpdsql_update, but uses a standard simple WHERE LIKE clause to determine
 * the row or rows to update. See the docs for cpdsql_update and cpdsql_like for more details on the
 * result and parameters respectively.
 * 
 * @param {string}               $table Name of the table to update
 * @param {string|array}         $column The name of the SQL column whose value match against
 * @param {string}               $value  The value to match for 
 * @param {array<string, mixed>} $data  Data for the new row, as an associative array
 * 
 * @return {object} TRUE on success or FALSE on error 
 */ 
function cpdsql_update_like($table, $column, $value = null, $data)
{
	$updateSQL = cpdsql_clause_set($data);
	$whereSQL = cpdsql_clause_like($table, $column, $value);
	
	return cpdsql_query("UPDATE `$table` $updateSQL $whereSQL"); 
}


/** Update rows in the database with the given id value
 *
 * Performs the function of cpdsql_update, but uses a standard simple WHERE `id` clause to determine
 * the row to update. See the docs for cpdsql_update and cpdsql_id for details on the result and the 
 * parameters respectively.
 * 
 * @param {string}               $table Name of the table to update
 * @param {string}               $id    The id to match for 
 * @param {array<string, mixed>} $data  Data for the new row, as an associative array
 * 
 * @return {object} TRUE on success or FALSE on error 
 */ 
function cpdsql_update_id($table, $id, $data)
{
	$updateSQL = cpdsql_clause_set($data);
	$whereSQL = cpdsql_clause_id($table, $id);
	
	return cpdsql_query("UPDATE `$table` $updateSQL $whereSQL"); 
}


/** Convert an associative array of data into an SQL SET clause
 *
 * This function powers cpdsql_update and all the cpdsql_update_* functions. For more information on
 * what the $data array should look like, see the docs fro cpdsql_update().
 * 
 * @return {string} An SQL set clause string (including the leading "SET" word)   
 */ 
function cpdsql_clause_set($data)
{
	foreach($data as $column => $value)
	{
		$updates[] = "`$column` = '".cpdsql_escape_string($value)."'"; 
	}
	
	$updateSQL = "SET ".implode(', ', $updates);
	
	return $updateSQL;
}

/** Save a record to the database, either by adding a new row or updating an existing one
 *
 * This function allows us to use the same processing scripts for adding a new record that we use to
 * update an existing record. Basically, you give it a table name, a key column, and some data to be
 * inserted or updated. The function checks the value of the key column in the data you gave it, and
 * if that value is 0 or empty (or anything that == FALSE), it inserts a new row to the database for
 * the data you sent. On the other hand, if the value in the key column isn't false, is updates with
 * the data you gave it and the key column you sent (pulling the value out of the data).
 * 
 * Note that this function does not check the actual table for the presence or absence of a row with
 * a certain key. Rather, it just checks to see whether you provided a value for the key in the data
 * parameter; if so, it assumes that such a row already exists, and attempts an update; if not, then
 * it does an insert. To update or insert based on the contents of the table, use cpdsql_replace().
 *
 * See the docs for cpdsql_insert and cpdsql_update for more information on how those work.
 *   
 * @param {string}               $table Name of the table to insert into or update
 * @param {string}               $key   Name of the key column
 * @param {array<string, mixed>} $data  Data for the new row, as an associative array
 *
 * @return {object} TRUE on success or FALSE on error
 */  
function cpdsql_save($table, $key, $data)
{
	if(isset($data[$key]) && $data[$key])
	{
		return cpdsql_update($table, $key, $data);
	}
	else
	{
		unset($data[$key]);
		
		return cpdsql_insert($table, $data);
	}
}


/** Insert or replace a row in the given table
 *
 * Checks the given table to see if it contains a row whose value in the column specified by the key
 * parameter matches the corresponding value specified in the given data object. If so, then the row
 * in question is deleted. Then (in either case), a new row is added using the data provided. Notice
 * that this does a DELETE then INSERT, not an UPDATE. This means that any columns that aren't given
 * values in the $data array will be set to their column defaults, not to the values they had in the
 * old row.    
 *
 * See the docs for cpdsql_insert and cpdsql_update for more information on how those work.
 *   
 * @param {string}               $table Name of the table to insert into
 * @param {string}               $key   Name of the key column
 * @param {array<string, mixed>} $data  Data for the new row, as an associative array
 *
 * @return {object} TRUE on success or FALSE on error
 */
function cpdsql_replace($table, $key, $data)
{		
	cpdsql_delete_where($table, $key, $data[$key]);
	
	return cpdsql_insert($table, $data);
}





/* Date/Time Utility Functions */

/** Obtain a date formatted for insertion and comparison in NySQL.
 *
 * This is a shortcut to calling date('Y-m-d', strtotime($timestamp)) and is implemented with a call
 * identical to that one. This came about from me forgetting the 'Y-m-d' code a lot, so I thought we
 * could outsource that to another function and not have to worry about it anymore.
 * 
 * If the timestamp parameter is omitted (i.e., the function is called with zero arguments) then the
 * current date is used to create the timestamp. If the $timestamp == null, then the resulting value
 * is the zero date, '0000-00-00'.     
 * 
 * @param {timestamp} $timestamp The timestamp to format; if omitted, defaults to today's date 
 *
 * @return {string} A date formatted as 'yyyy-mm-dd', the standard SQL date format
 */
function cpdsql_date($timestamp = 'today')
{
	if($timestamp == '0000-00-00' || $timestamp == null)
	{
		return '0000-00-00';
	} 
	else if($timestamp == 'today')
	{
		return date('Y-m-d');
	}
	else
	{
		return date('Y-m-d', strtotime($timestamp));
	}
}


/** Obtain a date/time formatted for insertion and comparison in NySQL.
 *
 * This is a shortcut to calling date('Y-m-d H:i:s', strtotime($timestamp)) and is implemented using
 * a call identical to that one. This is the equivalent of the cpdsql_date function for fields using
 * a full date/time (i.e., a datetime or timestamp column) instead of just the date.
 * 
 * Like cpdsql_date, you can omit the timestamp argument to get a string corresponding to the NOW(),
 * the exact current call, or send set it to null to get a zero timestamp, '0000-00-00 00:00:00'.
 * 
 * @param {timestamp} $timestamp The timestamp to format; if omitted, defaults the current timestamp
 *
 * @return {string} A date formatted as 'yyyy-mm-dd hh:mm:ss', the standard SQL date/time format 
 */ 
function cpdsql_time($timestamp = 'now')
{
	if($timestamp == '0000-00-00' || $timestamp == null)
	{
		return '0000-00-00';
	}
	else if($timestamp == 'now')
	{
		return date('Y-m-d H:i:s');
	} 
	else
	{
		return date('Y-m-d H:i:s', strtotime($timestamp));
	}
}




/** Get the name of the active sql engine
 *
 * The just returns the 'engine' config parameter, so it'll either be what is set in cpdsql.conf.php
 * or the more recent call to cpdsql_engine_set.
 * 
 * @return {string} The name of the active sql engine  
 */
function cpdsql_engine_get()
{
	return _cpdsql_conf('engine');
}


/** Set the database engine to use for subsequent queries
 *
 * The default engine can (and indeed must) be set in cpdsql.conf.php. This function is usually used
 * when a script needs to temporarily switch to a different engine (often, but not always, switching
 * back to the original engine afterward). After setting the engine here, things will act exactly as
 * if that engine has been set in the config file to begin with.      
 *
 * @param {string} $engine The new sql engine to use; see cpdsql.conf.php for supported engines
 */  
function cpdsql_engine_set($engine)
{
	$GLOBALS['cpdconf']['cpdsql']['engine'] = $engine;

	require_once "cpdsql-$engine.inc.php";
}


/** Get the active cpdsql database connection, if such has been set
 *
 * The active connection depends on which database engine is currently selected (either by using the
 * cpdsql_engine_set function or setting one with cpdsql.conf.php) and is saved even when you switch
 * to another engine. See the docs for the cpdsql_engine_set and cpdsql_connection_set functions for
 * more information about the engine connection system works. 
 *
 * @return {resource} The db connection set with cpdsql_connection_set, or FALSE if none has ben set
 */
function cpdsql_connection_get()
{
	$engine = _cpdsql_conf('engine');

	if(isset($GLOBALS['cpdconf']['cpdsql']['connection'][$engine]))
	{
		return $GLOBALS['cpdconf']['cpdsql']['connection'][$engine];
	}
	else
	{
		return false;
	}
}


/** Set the cpdsql database connection for the active engine
 *
 * By default, cpdsql doesn't explicitly specify any particular database connection when it executes
 * the actual sql query resulting from a cpdsql call. However, there are several cases where using a
 * specific connection makes sense, such as when multiple connections are open simultaneously and it
 * is desirable to run specific queries through specific ones. Also, some SQL engines (including the
 * somewhat common odbc engine) do not support making queries without explicitly supplying some sort
 * of connection string, necessitating the use of this function every time cpdsql is used with those
 * drivers.
 * 
 * Scripts using a single MySQL connection probably shouldn't ever have to do this.  
 * 
 * In order to facilitate switching back and forth between multiple database engines within the same
 * script, the saved connection is associated with the current engine. If you switch engines after a
 * call to this function (via a call to cpdsql_engine_set), the connection will be saved and will be
 * reused should you switch back to the original database engine at some future point.
 * 
 * To disable the use of the explicit connection, send in null or false as the connection parameter. 
 *
 * @param {resource} $connection The database connection to use, or null to use the php default 
 */ 
function cpdsql_connection_set($connection)
{
	$engine = _cpdsql_conf('engine');
	
	if($connection)
	{
		$GLOBALS['cpdconf']['cpdsql']['connection'][$engine] = $connection;
	}
	else
	{
		unset($GLOBALS['cpdconf']['cpdsql']['connection'][$engine]);
	}
}






/* The following functions are all just wrappers for the engine-specific driver file functions. Each
 * of these forwards the call to the appropriate driver function based on the currently selected SQL
 * engine (see cpdsql_engine_set, pdsql_engine_get, and the default comment at the top of the driver
 * source files for more information on how all that works).
 * 
 * Although primarily used internally to implement the main cpdsql functions, each function here can
 * also be called directly and is useful in its own right. Calling them through these wrappers gives
 * a common API to perform identical functions in multiple databases. 
 */

function cpdsql_query($query)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_query', $query);
}

function cpdsql_escape_string($data)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_escape_string', $data);
}

function cpdsql_result_array($result)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_result_array', $result);
}

function cpdsql_result_row($result)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_result_row', $result);
}

function cpdsql_result_value($result)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_result_value', $result);
}

function cpdsql_result_count($result)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_result_count', $result);
}

function cpdsql_result_exists($result)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_result_exists', $result);
}
 
function cpdsql_default_row($table)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_default_row', $table);
}

function cpdsql_verify_table($table)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_verify_table', $table);
}

function cpdsql_verify_column($table, $column)
{
	return call_user_func('cpdsql_'.cpdsql_engine_get().'_verify_column', $table, $column);
}






/* Returns a configuration parameter from the global $cpdconf['cpdsql'] array. This is just so I can
avoid having to type $GLOBALS['cpdconf']['cpdsql'] over and over again, and to make code shorter. */
function _cpdsql_conf($param)
{
	if(func_num_args() == 1)
	{
		return $GLOBALS['cpdconf']['cpdsql'][$param];
	}
	else
	{
		return $GLOBALS['cpdconf']['cpdsql'][func_get_arg(0)][func_get_arg(1)];	
	}
}

function _cpdsql_engine_error($error_message)
{
	_cpdsql_handle_error('engine_error', $error_message);
}

function _cpdsql_internal_error($error_message)
{
	_cpdsql_handle_error('cpdsql_error', $error_message);
}

function _cpdsql_handle_error($type, $error_message)
{
	$debug = " "; 
	$backtrace = debug_backtrace();
	
	foreach($backtrace as $call)
	{
		if($call['function'] != '_cpdsql_handle_error' && isset($call['file']))
		{
			$debug .= "[{$call['file']}#{$call['line']}: {$call['function']}]";
		}
	}

	if(_cpdsql_conf($type, 'log'))
	{
		 _cpdsql_log(_cpdsql_conf($type, 'log_file'), $error_message.$debug);
	}
	
	if(_cpdsql_conf($type, 'exit'))
	{
		die();
	}
}

function _cpdsql_log_query($query)
{
	if(_cpdsql_conf('query', 'log'))
	{
		 _cpdsql_log(_cpdsql_conf('query', 'log_file'), $query);
	}
}

function _cpdsql_log($file, $content)
{
	cpdlog_compact($file, $content);
}

?>