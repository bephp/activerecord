<?php 
include "ActiveRecord.php";
Class User extends ActiveRecord
{
	public $table = 'tb_user';
}


//User::setDb(new PDO('sqlite:memory:'));

User::setDb(new PDO('mysql:host=localhost;dbname=b2core', 'root', '123'));
$user = new User();

var_dump($user->findAll());

$user->ne('id', 1)->wrap()->in('id', array(1,2,3,4))->gt('id', 1)->le('id', 10)->wrap('or')
	->notnull('id')->orderby('id desc')
	->groupby('id')
	->find();

var_dump($user);