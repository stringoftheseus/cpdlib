CPDLib 2.x requires PHP 5.3.0 or higher.

/*
 * Note: It
 * is the responsibility of the host script to actually connect to the listed database system and to
 * select the desired database or perform any other necessary initialization functions so that PHP's
 * APIs into that database will work. This is by design; having cpdsql perform the database connects
 * would mean that every page that wished to use the database would need to include cpdsql (which is
 * against the project's philosophy of being a "take it or leave it" library) or require all connect
 * credentials be included in each project twice (once in cpdsql and once elsewhere) which generally
 * is considered to be a bad idea.
 */  