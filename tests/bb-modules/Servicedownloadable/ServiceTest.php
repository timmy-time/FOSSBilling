<?php


namespace Box\Mod\Servicedownloadable;


class ServiceTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var \Box\Mod\Servicedownloadable\Service
     */
    protected $service = null;

    public function setup()
    {
        $this->service= new \Box\Mod\Servicedownloadable\Service();
    }

    public function testgetDi()
    {
        $di = new \Box_Di();
        $this->service->setDi($di);
        $getDi = $this->service->getDi();
        $this->assertEquals($di, $getDi);
    }

    public function testattachOrderConfigMissingFilename()
    {
        $productModel = new \Model_Product();
        $productModel->loadBean(new \RedBeanPHP\OODBBean());
        $productModel->config = '{}';

        $data = array();

        $this->setExpectedException('\Box_Exception', 'Product is not configured completely.');
        $this->service->attachOrderConfig($productModel, $data);
    }

    public function testattachOrderConfig()
    {
        $productModel = new \Model_Product();
        $productModel->loadBean(new \RedBeanPHP\OODBBean());
        $productModel->config = '{"filename" : "temp/asdcxTest.txt"}';

        $data = array();

        $expected = array_merge(json_decode($productModel->config, 1), $data);

        $result = $this->service->attachOrderConfig($productModel, $data);
        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testvalidateOrderData()
    {
        $data = array();

        $this->setExpectedException('\Box_Exception', 'Filename is missing in product config.');
        $this->service->validateOrderData($data);
    }

    public function testaction_create()
    {
        $clientOrderModel = new \Model_ClientOrder();
        $clientOrderModel->loadBean(new \RedBeanPHP\OODBBean());
        $clientOrderModel->config = '{"filename" : "temp/asdcxTest.txt"}';

        $model = new \Model_ServiceDownloadable();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('dispense')
            ->will($this->returnValue($model));

        $dbMock->expects($this->atLeastOnce())
            ->method('store')
            ->will($this->returnValue(1));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $this->service->setDi($di);
        $result = $this->service->action_create($clientOrderModel);
        $this->assertInstanceOf('\Model_ServiceDownloadable', $result);
    }

    public function testaction_delete()
    {
        $clientOrderModel = new \Model_ClientOrder();;

        $orderServiceMock = $this->getMockBuilder('\Box\Mod\Order\Service')->getMock();
        $orderServiceMock->expects($this->atLeastOnce())
            ->method('getOrderService')
            ->will($this->returnValue(new \Model_ServiceDownloadable()));

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('trash');

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(function() use ($orderServiceMock) {return $orderServiceMock;});

        $this->service->setDi($di);
        $this->service->action_delete($clientOrderModel);
    }

    public function testhitDownload()
    {
        $model = new \Model_ServiceDownloadable();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $this->service->setDi($di);

        $this->service->hitDownload($model);
    }

    public function testtoApiArray()
    {
        $model = new \Model_ServiceDownloadable();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $model->filename = 'config.cfg';
        $model->downloads = 1;

        $filePath = 'path/to/location'.md5($model->filename);

        $expected = array(
            'path' => $filePath,
            'filename' => $model->filename,
            'downloads' => 1,
        );

        $productServiceMock = $this->getMockBuilder('\Box\Mod\Product\Service')->getMock();
        $productServiceMock->expects($this->atLeastOnce())
            ->method('getSavePath')
            ->will($this->returnValue($filePath));

        $di = new \Box_Di();
        $di['mod_service'] = $di->protect(function() use ($productServiceMock) {return $productServiceMock;});

        $this->service->setDi($di);
        $result = $this->service->toApiArray($model, null, new \Model_Admin());
        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testuploadProductFileErrorUploadingFile()
    {
        $productModel = new  \Model_Product();
        $productModel->loadBean(new \RedBeanPHP\OODBBean());
        $successfullyUploadedFileCount = 0;
        $requestMock = $this->getMockBuilder('\Box_Request')->getMock();
        $requestMock->expects($this->atLeastOnce())
            ->method('hasFiles')
            ->will($this->returnValue($successfullyUploadedFileCount));
        $di = new \Box_Di();
        $di['request'] = $requestMock;
        $this->service->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Error uploading file');
        $this->service->uploadProductFile($productModel);

    }

    public function testuploadProductFile()
    {
        $productModel = new  \Model_Product();
        $productModel->loadBean(new \RedBeanPHP\OODBBean());
        $successfullyUploadedFileCount = 1;

        $file = array(
            'name' => 'test',
            'tmp_name' => '12345',
        );
        $fileMock = new \Box_RequestFile($file);

        $requestMock = $this->getMockBuilder('\Box_Request')->getMock();
        $requestMock->expects($this->atLeastOnce())
            ->method('hasFiles')
            ->will($this->returnValue($successfullyUploadedFileCount));
        $requestMock->expects($this->atLeastOnce())
            ->method('getUploadedFiles')
            ->will($this->returnValue(array($fileMock)));

        $productServiceMock = $this->getMockBuilder('\Box\Mod\Product\Service')->getMock();
        $productServiceMock->expects($this->atLeastOnce())
            ->method('getSavePath');
        $productServiceMock->expects($this->atLeastOnce())
            ->method('removeOldFile');

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di = new \Box_Di();
        $di['mod_service'] = $di->protect(function() use ($productServiceMock) {return $productServiceMock;});
        $di['db'] = $dbMock;
        $di['logger'] = new \Box_Log();
        $di['request'] = $requestMock;
        $this->service->setDi($di);

        $result = $this->service->uploadProductFile($productModel);
        $this->assertInternalType('bool', $result);
        $this->assertTrue($result);
    }

    public function testupdateProductFile()
    {
        $orderModel = new \Model_ClientOrder();
        $orderModel->loadBean(new \RedBeanPHP\OODBBean());

        $serviceDownloadableModel = new \Model_ServiceDownloadable();
        $serviceDownloadableModel->loadBean(new \RedBeanPHP\OODBBean());

        $successfullyUploadedFileCount = 1;

        $file = array(
            'name' => 'test',
            'tmp_name' => '12345',
        );
        $fileMock = new \Box_RequestFile($file);

        $requestMock = $this->getMockBuilder('\Box_Request')->getMock();
        $requestMock->expects($this->atLeastOnce())
            ->method('hasFiles')
            ->will($this->returnValue($successfullyUploadedFileCount));
        $requestMock->expects($this->atLeastOnce())
            ->method('getUploadedFiles')
            ->will($this->returnValue(array($fileMock)));

        $productServiceMock = $this->getMockBuilder('\Box\Mod\Product\Service')->getMock();
        $productServiceMock->expects($this->atLeastOnce())
            ->method('getSavePath');

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di = new \Box_Di();
        $di['mod_service'] = $di->protect(function() use ($productServiceMock) {return $productServiceMock;});
        $di['db'] = $dbMock;
        $di['logger'] = new \Box_Log();
        $di['request'] = $requestMock;

        $this->service->setDi($di);
        $result = $this->service->updateProductFile($serviceDownloadableModel, $orderModel);
        $this->assertInternalType('bool', $result);
        $this->assertTrue($result);
    }

    public function testupdateProductFileFileNotUploaded()
    {
        $orderModel = new \Model_ClientOrder();
        $orderModel->loadBean(new \RedBeanPHP\OODBBean());

        $serviceDownloadableModel = new \Model_ServiceDownloadable();
        $serviceDownloadableModel->loadBean(new \RedBeanPHP\OODBBean());

        $successfullyUploadedFileCount = 0;
        $requestMock = $this->getMockBuilder('\Box_Request')->getMock();
        $requestMock->expects($this->atLeastOnce())
            ->method('hasFiles')
            ->will($this->returnValue($successfullyUploadedFileCount));
        $di = new \Box_Di();
        $di['request'] = $requestMock;
        $this->service->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Error uploading file');
        $this->service->updateProductFile($serviceDownloadableModel, $orderModel);
    }

    public function testsendFile()
    {
        $serviceDownloadableModel = new \Model_ServiceDownloadable();
        $serviceDownloadableModel->loadBean(new \RedBeanPHP\OODBBean());
        $serviceDownloadableModel->filename = 'config.cfg';
        $serviceDownloadableModel->downloads = 1;

        $filePath = 'path/to/location'.md5($serviceDownloadableModel->filename);

        $productServiceMock = $this->getMockBuilder('\Box\Mod\Product\Service')->getMock();
        $productServiceMock->expects($this->atLeastOnce())
            ->method('getSavePath')
            ->will($this->returnValue($filePath));

        $toolsMock = $this->getMockBuilder('\Box_Tools')->getMock();
        $toolsMock->expects($this->atLeastOnce())
            ->method('fileExists')
            ->will($this->returnValue(true));

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['tools'] = $toolsMock;
        $di['logger'] = new \Box_Log();
        $di['mod_service'] = $di->protect(function() use ($productServiceMock) {return $productServiceMock;});

        $this->service->setDi($di);

        $result = $this->service->sendFile($serviceDownloadableModel);
        $this->assertInternalType('bool', $result);
        $this->assertTrue($result);
    }

    public function testsendFileFileDoesNotExists()
    {
        $serviceDownloadableModel = new \Model_ServiceDownloadable();
        $serviceDownloadableModel->loadBean(new \RedBeanPHP\OODBBean());
        $serviceDownloadableModel->filename = 'config.cfg';
        $serviceDownloadableModel->downloads = 1;

        $filePath = 'path/to/location'.md5($serviceDownloadableModel->filename);

        $productServiceMock = $this->getMockBuilder('\Box\Mod\Product\Service')->getMock();
        $productServiceMock->expects($this->atLeastOnce())
            ->method('getSavePath')
            ->will($this->returnValue($filePath));

        $toolsMock = $this->getMockBuilder('\Box_Tools')->getMock();
        $toolsMock->expects($this->atLeastOnce())
            ->method('fileExists');


        $di = new \Box_Di();
        $di['tools'] = $toolsMock;
        $di['mod_service'] = $di->protect(function() use ($productServiceMock) {return $productServiceMock;});

        $this->service->setDi($di);

        $this->setExpectedException('\Box_Exception', 'File can not be downloaded at the moment. Please contact support', 404);
        $this->service->sendFile($serviceDownloadableModel);
    }










}
 