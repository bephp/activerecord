<?php 

class User extends ActiveRecord{
    public $table = 'user';
    public $primaryKey = 'id';
    public $relations = array(
        'contacts' => array(self::HAS_MANY, 'Contact', 'user_id'),
        'contact' => array(self::HAS_ONE, 'Contact', 'user_id', array('where' => '1', 'order' => 'id desc')),
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
    public function testError(){
        try{
            ActiveRecord::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            ActiveRecord::execute('CREATE TABLE IF NOT EXISTS');
        }catch(Exception $e){
            $this->assertInstanceOf('PDOException', $e);
            $this->assertEquals('HY000', $e->getCode());
            $this->assertEquals('SQLSTATE[HY000]: General error: 1 near "EXISTS": syntax error', $e->getMessage());
        }
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
    public function testEdittUser($user){
        $user->name = 'demo1';
        $user->password = md5('demo1');
        $user->update();
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
    public function testEditContact($contact){
        $contact->address = 'test1';
        $contact->email = 'test1@demo.com';
        $contact->update();
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
        $this->assertGreaterThan(0, count($contact->user->contacts));
        return $contact;
    }
    /**
     * @depends testInsertContact
     */
    public function testJoin($contact){
        $user = new User();
        $user->select('*, c.email, c.address')->join('contact as c', 'c.user_id = user.id')->find();
        // email and address will stored in user data array.
        $this->assertEquals($user->id, $contact->user_id);
        $this->assertEquals($user->email, $contact->email);
        $this->assertEquals($user->address, $contact->address);
    }
    /**
     * @depends testInsertContact
     */
    public function testQuery($contact){
        $user = new User();
        $user->isnotnull('id')->eq('id', 1)->lt('id', 2)->gt('id', 0)->find();
        $this->assertGreaterThan(0, $user->id);
        $this->assertSame(array(), $user->dirty);
        $user->name = 'testname';
        $this->assertSame(array('name'=>'testname'), $user->dirty);
        $name = $user->name;
        $this->assertEquals('testname', $name);
        unset($user->name);
        $this->assertSame(array(), $user->dirty);
        $user->reset()->isnotnull('id')->eq('id', 'aaa"')->wrap()->lt('id', 2)->gt('id', 0)->wrap('OR')->find();
        $this->assertGreaterThan(0, $user->id);
        $user->reset()->isnotnull('id')->between('id', array(0, 2))->find();
        $this->assertGreaterThan(0, $user->id);
    }
    /**
     * @depends testRelations
     */
    public function testDelete($contact){
        $cid = $contact->id;
        $uid = $contact->user_id; 
        $new_contact = new Contact();
        $new_user = new User();
        $this->assertEquals($cid, $new_contact->find($cid)->id);
        $this->assertEquals($uid, $new_user->eq('id', $uid)->find()->id);
        $this->assertTrue($contact->user->delete());
        $this->assertTrue($contact->delete());
        $new_contact = new Contact();
        $new_user = new User();
        $this->assertFalse($new_contact->eq('id', $cid)->find());
        $this->assertFalse($new_user->find($uid));
    }
}
