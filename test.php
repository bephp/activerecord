<?php 
include "ActiveRecord.php";
class Contact extends ActiveRecord{
	public $table = 'contact';
}
Contact::setDb(new PDO('sqlite:memory:'));

Contact::exec("CREATE TABLE IF NOT EXISTS contact (
				id INTEGER PRIMARY KEY, 
				name TEXT, 
				email TEXT 
			);");
Contact::exec("INSERT INTO contact (name, email) VALUES ('testname1', 'testemail1@domain.com')");
Contact::exec("INSERT INTO contact (name, email) VALUES ('testname2', 'testemail2@domain.com')");
Contact::exec("INSERT INTO contact (name, email) VALUES ('testname3', 'testemail3@domain.com')");

$contact = new Contact();
var_dump($contact->findAll());  // many Contact object in an array.
var_dump($contact);				// one Contact with no data.
var_dump($contact->find(1));	// one Contact from database by id = 1.
$contact->reset();				// reset the sql.
var_dump($contact->notin('id', array(1,2,3,4,5,6))->isnotnull('id')->order('id desc', 'name asc')->find());
// build sql: SELECT * FROM contact  WHERE id NOT IN (1,2,3,4,5,6) AND id IS NOT NULL     ORDER BY id desc, name asc   limit 1 
var_dump($contact->delete());
// DELETE   FROM contact  WHERE id NOT IN (1,2,3,4,5,6) AND id IS NOT NULL  AND id = 66

