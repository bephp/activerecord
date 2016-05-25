# activerecord
[![Build Status](https://travis-ci.org/bephp/activerecord.svg?branch=master)](https://travis-ci.org/bephp/activerecord)
[![Coverage Status](https://coveralls.io/repos/github/bephp/activerecord/badge.svg?branch=master)](https://coveralls.io/github/bephp/activerecord?branch=master)
[![Latest Stable Version](https://poser.pugx.org/bephp/activerecord/v/stable)](https://packagist.org/packages/bephp/activerecord) [![Total Downloads](https://poser.pugx.org/bephp/activerecord/downloads)](https://packagist.org/packages/bephp/activerecord) [![Latest Unstable Version](https://poser.pugx.org/bephp/activerecord/v/unstable)](https://packagist.org/packages/bephp/activerecord) [![License](https://poser.pugx.org/bephp/activerecord/license)](https://packagist.org/packages/bephp/activerecord)

一个微型的ActiveRecord库（包含注释才400行，支持链式调用以及关联关系）

## 文档地址
[文档](https://bephp.github.io/activerecord/)

## API
### CRUD 函数
#### setDb(\PDO  $db) 
设置数据库连接

    ActiveRecord::setDb(new PDO('sqlite:test.db'));

#### insert() : boolean|\ActiveRecord
插入函数，会使用当前对象的值生成插入SQL语句，如果插入成功，返回当前对象，否则返回false

    $user = new User();
    $user->name = 'demo';
    $user->password = md5('demo');
    $user->insert();

#### find(integer  $id = null) : boolean|\ActiveRecord
从数据库查找记录，并将记录赋值给当前对象
如果使用$id参数，则使用这个id来进行查找
如果查找到记录，则赋值给当前对象，否则返回false

    $user->notnull('id')->orderby('id desc')->find();

#### findAll() : array
查找一个列表数据，返回的数组里面，每一个都是一个ActiveRecord对象

    $user->findAll();

#### update() : boolean|\ActiveRecord
更新当前对象对应的数据库记录，每次更新的时候，只会将改变的值更新到数据库。
更新成功返回当前对象，否则返回false

    $user->notnull('id')->orderby('id desc')->find();
    $user->email = 'test@example.com';
    $user->update();

#### delete() : boolean
删除当前对象在数据库中对应的记录

#### reset() : \ActiveRecord
将$params, $sqlExpressions数组重置

#### dirty(array  $dirty = array()) : \ActiveRecord
这个函数用来设置或者重置dirty数据

### SQL部分帮助函数
#### select()
设置需要查找的字段

    $user->select('id', 'name')->find();

#### from()
设置查找的表

    $user->select('id', 'name')->from('user')->find();

#### join()
使用join函数设置连接表查询

    $user->join('contact', 'contact.user_id = user.id')->find();

#### where()
设置where条件

    $user->where('id=1 AND name="demo"')->find();

#### group()/groupby()

    $user->select('count(1) as count')->groupby('name')->findAll();
    
#### order()/orderby()

    $user->orderby('name DESC')->find();
    
#### limit()

    $user->orderby('name DESC')->limit(0, 1)->find();

### WHERE 条件
#### equal()/eq()

    $user->eq('id', 1)->find();

#### notequal()/ne()

    $user->ne('id', 1)->find();
    
#### greaterthan()/gt()

    $user->gt('id', 1)->find();

#### lessthan()/lt()

    $user->lt('id', 1)->find();

#### greaterthanorequal()/ge()/gte()

    $user->ge('id', 1)->find();

#### lessthanorequal()/le()/lte()

    $user->le('id', 1)->find();

#### like()

    $user->like('name', 'de')->find();

#### in()

    $user->in('id', [1, 2])->find();

#### notin()

    $user->notin('id', [1,3])->find();

#### isnull()

    $user->isnull('id')->find();

#### isnotnull()/notnull()

    $user->isnotnull('id')->find();

## 安装

    composer require bephp/activerecord 


这里有一个[博客的例子](https://github.com/bephp/blog), 与[Router](https://github.com/bephp/router)以及[MicoTpl](https://github.com/bephp/microtpl)一起组织起来使用。

## 例子
### 包含class ActiveRecord
```php
include "ActiveRecord.php";
```
### 定义 Class
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
### 初始化数据
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
### 插入数据
```php
$user = new User();
$user->name = 'demo';
$user->password = md5('demo');
var_dump($user->insert());
```
### 插入一个属于当前用户的联系方式
```php
$contact = new Contact();
$contact->address = 'test';
$contact->email = 'test1234456@domain.com';
$contact->user_id = $user->id;
var_dump($contact->insert());
```
### 使用关联关系的例子
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
var_dump($contact->user);
```

