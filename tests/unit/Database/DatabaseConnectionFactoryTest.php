<?php

use Mockery as m;

class DatabaseConnectionFactoryPDOStub extends PDO {
	public function __construct() {}
}

class DatabaseConnectionFactoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMakeCallsCreateConnection()
	{
		$factory = $this->getMock('Illuminate\Database\Connectors\ConnectionFactory', array('createConnector', 'createConnection'));

		$config = array('driver' => 'mysql', 'prefix' => 'prefix', 'database' => 'database');

		$pdo = new DatabaseConnectionFactoryPDOStub;

        $connector = m::mock('stdClass');
		$connector->shouldReceive('connect')->once()->with($config)->andReturn($pdo);

        $mockConnection = $this->getMock('Illuminate\Database\Connection', array('setReconnector'), array($pdo));
        $mockConnection->expects($this->once())->method('setReconnector')->will($this->returnValue($mockConnection));

		$factory->expects($this->once())->method('createConnector')->with($config)->will($this->returnValue($connector));
		$factory->expects($this->once())->method('createConnection')->with($this->equalTo('mysql'), $this->equalTo($pdo), $this->equalTo('prefix'))->will($this->returnValue($mockConnection));

        $connection = $factory->make($config);

		$this->assertSame($mockConnection, $connection);
	}


	public function testMakeCallsCreateConnectionForReadWrite()
	{
		$factory = $this->getMock('Illuminate\Database\Connectors\ConnectionFactory', array('createConnector', 'createConnection'), array());
		$connector = m::mock('stdClass');
		$config = array(
			'read' => array('database' => 'database'),
			'write' => array('database' => 'database'),
			'driver' => 'mysql', 'prefix' => 'prefix', 'name' => 'foo'
		);
		$expect = $config;
		unset($expect['read']);
		unset($expect['write']);
		$expect['database'] = 'database';
		$pdo = new DatabaseConnectionFactoryPDOStub;
		$connector->shouldReceive('connect')->twice()->with($expect)->andReturn($pdo);

        $mockConnection = $this->getMock('Illuminate\Database\Connection', array('setReconnector'), array($pdo));
        $mockConnection->expects($this->once())->method('setReconnector')->will($this->returnValue($mockConnection));

        $factory->expects($this->exactly(2))->method('createConnector')->with($expect)->will($this->returnValue($connector));
		$factory->expects($this->once())->method('createConnection')->with($this->equalTo('mysql'), $this->equalTo($pdo), $this->equalTo('prefix'))->will($this->returnValue($mockConnection));
		$connection = $factory->make($config, 'foo');

		$this->assertSame($mockConnection, $connection);
	}

	public function testProperInstancesAreReturnedForProperDrivers()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory();
		$this->assertInstanceOf('Illuminate\Database\Connectors\MySqlConnector', $factory->createConnector(array('driver' => 'mysql')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\PostgresConnector', $factory->createConnector(array('driver' => 'pgsql')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\SQLiteConnector', $factory->createConnector(array('driver' => 'sqlite')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\SqlServerConnector', $factory->createConnector(array('driver' => 'sqlsrv')));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testIfDriverIsntSetExceptionIsThrown()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory();
		$factory->createConnector(array('foo'));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionIsThrownOnUnsupportedDriver()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory();
		$factory->createConnector(array('driver' => 'foo'));
	}

}