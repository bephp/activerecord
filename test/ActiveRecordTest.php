<?php 

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

class ActiveRecordTest extends \PHPUnit_Framework_TestCase {
    public function testInit(){
        @unlink('test.db');
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
    }
    /**
     * @depends testInit
     */
    public function testInsertUser(){
        $user = new User();
        $user->name = 'demo';
        $user->password = md5('demo');
        $user->insert();
        $this->assertGreaterThan(0, $user->id);
        return $user;
    }
    /**
     * @depends testInsertUser
     */
    public function testInsertContact($user){
        $contact = new Contact();
        $contact->address = 'test';
        $contact->email = 'test@demo.com';
        $contact->user_id = $user->id;
        $contact->insert();
        $this->assertGreaterThan(0, $contact->id);
        return $contact;
    }
    /**
     * @depends testInsertContact
     */
    public function testRelations($contact){
        $this->assertEquals($contact->user->id, $contact->user_id);
        $this->assertEquals($contact->user->contact->id, $contact->id);
        $this->assertEquals($contact->user->contacts[0]->id, $contact->id);
        return $contact;
    }
    /**
     * @depends testRelations
     */
    public function testDelete($contact){
        $cid = $contact->id;
        $uid = $contact->user_id; 
        $this->assertEquals($cid, (new Contact())->find($cid)->id);
        $this->assertEquals($uid, (new User())->eq('id', $uid)->find()->id);
        $this->assertTrue($contact->user->delete());
        $this->assertTrue($contact->delete());
        $this->assertNull((new Contact())->eq('id', $cid)->find()->id);
        $this->assertNull((new User())->find($uid)->id);
    }
}
