# activerecord
simple activerecord in PHP  

##API Reference
[API Reference](http://lloydzhou.github.io/activerecord/)

## Demo
### Include base class ActiveRecord
```php
include "ActiveRecord.php";
```
### Define Class
```php
class User extends ActiveRecord{
	public $table = 'user';
	public $primaryKey = 'id';
	public $relations = array(
		'contacts' => array(self::HAS_MANY, 'Contact', 'user_id'),
		'contact' => array(self::HAS_ONE, 'Contact', 'user_id'),
	);
}
class Contact extends ActiveRecord{
	public $table = 'contact';
	public $primaryKey = 'id';
	public $relations = array(
		'user' => array(self::BELONGS_TO, 'User', 'user_id'),
	);
}
```
### Init data
```php
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
```
### Insert one User into database.
```php
$user = new User();
$user->name = 'demo';
$user->password = md5('demo');
var_dump($user->insert());
```
### Insert one Contact belongs the current user.
```php
$contact = new Contact();
$contact->address = 'test';
$contact->email = 'test1234456@domain.com';
$contact->user_id = $user->id;
var_dump($contact->insert());
```
### Example to using relations 
```php
$user = new User();
// find one user
var_dump($user->notnull('id')->orderby('id desc')->find());
echo "\nContact of User # {$user->id}\n";
// get contacts by using relation:
//   'contacts' => array(self::HAS_MANY, 'Contact', 'user_id'),
var_dump($user->contacts);

$contact = new Contact();
// find one contact
var_dump($contact->find());
// get user by using relation:
//    'user' => array(self::BELONGS_TO, 'User', 'user_id'),
var_dump($contact->users);
```

