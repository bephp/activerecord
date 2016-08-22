<?php 
include "ActiveRecord.php";
//include "ActiveRecord.min.php";
class User extends ActiveRecord{
	public $table = 'user';
	public $primaryKey = 'id';
	public $relations = array(
		'contacts' => array(self::HAS_MANY, 'Contact', 'user_id'),
		'contact' => array(self::HAS_ONE, 'Contact', 'user_id', array('where' => '1', 'order' => 'id desc'), 'user'),
	);
}
class Contact extends ActiveRecord{
	public $table = 'contact';
	public $primaryKey = 'id';
	public $relations = array(
		'user' => array(self::BELONGS_TO, 'User', 'user_id'),
	);
}

ActiveRecord::setDb(new PDO('sqlite:test.db', null, null, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION)));
try {
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS user (");
    ActiveRecord::execute("select * from aaa");
} catch( Exception $e) {
    var_export($e);
}
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

$user = new User();
$user->name = 'demo';
$user->password = md5('demo');
var_dump($user->insert());

$contact = new Contact();
$contact->address = 'test';
$contact->email = 'test1234456@domain.com';
$contact->user_id = $user->id;
var_dump($contact->insert());

$contact = new Contact();
$contact->address = 'test';
$contact->email = 'test1234456@domain.com';
$contact->user_id = $user->id;
var_dump($contact->insert());

var_dump($user->contact);
echo "\n -----";
var_dump($user);
echo "\n join\n";
$contact = new Contact();
var_dump($contact->select('user.*, contact.*')->join('user', 'user.id = contact.user_id')->find());
/*
$contact = new Contact();
$contact->address = 'test';
$contact->email = 'test1234456@domain.com';
$contact->user_id = 2;
var_dump($contact->insert());
*/
$user = new User();
var_dump($user->select('user.*, c.email, c.address')->join('contact as c', 'c.user_id = user.id')->findAll());
var_dump($user->reset()->notnull('id')->orderby('id desc')->find());
echo "\nContact of User # {$user->id}\n";
var_dump($user->contacts);
$contact = new Contact();
var_dump($contact->find());
var_dump($contact->user);
