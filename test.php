<?php 
include "ActiveRecord.php";
class Contact extends ActiveRecord{
	public $table = 'contact';
	public $primaryKey = 'id';
}
Contact::setDb(new PDO('sqlite:test.db'));
/*
Contact::execute("CREATE TABLE IF NOT EXISTS contact (
				id INTEGER PRIMARY KEY, 
				name TEXT, 
				email TEXT 
			);");
Contact::execute("INSERT INTO contact (name, email) VALUES ('testname1', 'testemail1@domain.com')");
Contact::execute("INSERT INTO contact (name, email) VALUES ('testname2', 'testemail2@domain.com')");
Contact::execute("INSERT INTO contact (name, email) VALUES ('testname3', 'testemail3@domain.com')");
*/
$contact = new Contact();
$contact->name = 'test1234456';
$contact->email = 'test1234456@domain.com';
var_dump($contact->insert());
$contact->name = 'test123';
var_dump($contact->update());
/*
var_dump($contact->limit(1,2)->findAll());  // many Contact object in an array.
//var_dump($contact);				// one Contact with no data.
var_dump($contact->select('name,email')->gt('id', 4)->find());	// one Contact from database by id = 1.

//$contact->reset();				// reset the sql. no need to call this function auto call it when exec sql.
//var_dump($contact->notin('id', array(1,2,3,4,5,6))->isnotnull('id')->order('id desc', 'name asc')->find());
// build sql: SELECT * FROM contact  WHERE id NOT IN (1,2,3,4,5,6) AND id IS NOT NULL     ORDER BY id desc, name asc   limit 1 
//var_dump($contact->delete());
// DELETE   FROM contact  WHERE id NOT IN (1,2,3,4,5,6) AND id IS NOT NULL  AND id = 66

*/