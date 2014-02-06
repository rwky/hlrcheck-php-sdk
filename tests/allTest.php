<?php
require('../src/API.php');

class allTest extends \PHPUnit_Framework_TestCase {
    
    protected static $_api;
    protected static $_batch;
    
    public static function setUpBeforeClass(){
        self::$_api=new \hlrcheck\API('http://127.0.0.1:8080','7d0d3bfa-8f52-11e3-9eac-047d7b7c5e89','c9a4c3fa237daa3ab2f94cc3acdd87c73f2cbec3');
        $arr = self::$_api->bulk(array(111111111111,111111111112),'basic',30,30,1,false);
        self::$_batch=$arr['batch'];
    }
    
    public function testInstantCheck(){
        $arr = self::$_api->check(array(111111111111),'full',30,30);
        $this->assertEquals('OK',$arr['status']);
        $this->assertEquals('DELIVRD',$arr['data']['111111111111']['status']);
    }
    
    public function testCallbackCheck(){
        $arr = self::$_api->check(array(111111111111,111111111112),'basic',30,30,1,'http://127.0.0.1:8080/dummy');
        $this->assertEquals('OK',$arr['status']);
    }
    
    public function testBulk(){
        $arr = self::$_api->bulk(array(111111111111,111111111112),'basic',30,30);
        $this->assertEquals('OK',$arr['status']);
        $this->assertRegexp('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/',$arr['batch']);
        $this->assertEquals('',$arr['warning']);
    }
    
    public function testStatus(){
        $arr = self::$_api->status(self::$_batch);
        $this->assertEquals(2,$arr['remaining']);
        $this->assertEquals('holding',$arr['status']);
        $this->assertEquals(false,$arr['csv']);
    }
    
    public function testBalance(){
        $arr = self::$_api->balance();
        $this->assertGreaterThan(0,$arr['basiccredits']);
        $this->assertGreaterThan(0,$arr['fullcredits']);
    }
    
    public function testProcess(){
        $arr = self::$_api->process(self::$_batch);
        $this->assertEquals('OK',$arr['status']);
    }
    
    public function testCSVDownload(){
        $arr = self::$_api->status(self::$_batch);
        while(!$arr['csv']){
            sleep(1);
            $arr = self::$_api->status(self::$_batch);
        }
        $this->assertEquals("MSISDN,Status\n111111111112,LIVE\n111111111111,LIVE\n",self::$_api->csv_download(self::$_batch));
    }
    
}
