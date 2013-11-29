<?php 
//include "ActiveRecord.php";
include "ActiveRecord.min.php";
class User extends ActiveRecord{
	public $table = 'user';
	public $primaryKey = 'id';
	public $relations = array(
		'contacts' => array(self::HAS_MANY, 'Contact', 'user_id'),
		'contact' => array(self::HAS_ONE, 'Contact', 'user_id', 'where' => '1', 'order' => 'id desc'),
	);
}
class Contact extends ActiveRecord{
	public $table = 'contact';
	public $primaryKey = 'id';
	public $relations = array(
		'user' => array(self::BELONGS_TO, 'User', 'user_id'),
	);
}

ActiveRecord::setDb(new PDO('sqlite:test.db'));

ActiveRecord::execute("CREATE TABLE IF NOT EXISTS user (
				id INTEGER PRIMARY KEY, 
				name TEXT, 
				password TEXT 
			);");
ActiveRecord::execute("CREATE TABLE IF NOT EXISTS contact (
				id INTEGER PRIMARY KEY, 
				user_id INTEGER, 
				email TEXT,
				address TEXT
			);");
/*
$user = new User();
$user->name = 'demo';
$user->password = md5('demo');
var_dump($user->insert());

$contact = new Contact();
$contact->address = 'test';
$contact->email = 'test1234456@domain.com';
$contact->user_id = $user->id;
var_dump($contact->insert());
*/
/*
$contact = new Contact();
$contact->address = 'test';
$contact->email = 'test1234456@domain.com';
$contact->user_id = 2;
var_dump($contact->insert());
*/
$user = new User();
var_dump($user->notnull('id')->orderby('id desc')->find());
echo "\nContact of User # {$user->id}\n";
var_dump($user->contacts);
$contact = new Contact();
var_dump($contact->find());
var_dump($contact->users);
