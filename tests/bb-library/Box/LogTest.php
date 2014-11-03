<?php
/**
 * @group Core
 */
class Box_LogTest extends PHPUnit_Framework_TestCase
{
    public function testLog()
    {
        $service_mock = $this->getMockBuilder('Box\\Mod\\Activity\\Service')->getMock();
        $service_mock->expects($this->atLeastOnce())
            ->method('logEvent')
            ->will($this->returnValue(true));

        $writer1 = new Box_LogDb($service_mock);
        $writer2 = new Box_LogStream('php://output');
        $writer3 = new Box_LogStream(BB_PATH_LOG . '/test.log');

        $log = new Box_Log();
        $log->addWriter($writer1);
        $log->addWriter($writer2);
        $log->addWriter($writer3);

        $log->err('Test', array('admin_id'=>1, 'client_id'=>2));
        $log->err('Test 2', array('admin_id'=>3, 'client_id'=>4));
    }
}