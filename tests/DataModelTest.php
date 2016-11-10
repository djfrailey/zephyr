<?php

use PHPUnit\Framework\TestCase;

use const Zephyr\Model\File\{DATADIR};
use function Amp\wait;

class DataModelTest extends TestCase
{


    public function testCreatesDataFiles()
    {
        Amp\run(function() {
            $expectedPath = DATADIR . 'david.json';
            $path = yield Zephyr\Model\File\createDataFile('david');
            
            $this->assertNotEmpty($path, "A path should be returned.");
            $this->assertEquals($expectedPath, $path, "The path returned did not match the expected path.");
        });
    }

    /**
     * @depends testCreatesDataFiles
     */
    public function testOpensUserFiles()
    {
        Amp\run(function() {
            $handle = yield Zephyr\Model\File\openUserFile('david2', 'w');
            $this->assertEquals(0, $handle->tell());
            $handle->close();
        });
    }

    /**
     * @depends testCreatesDataFiles
     */
    public function testGetsUserData()
    {
        Amp\run(function() {
            $data = yield Zephyr\Model\File\getUserData('mock');

            $this->assertNotEmpty($data);
            $this->assertEquals($data['name'], "MOCK");
        });
    }

    
    public function testWritesUserData()
    {
        $data = [
            "name" => "David"
        ];

        Amp\run(function() use ($data, &$read, &$result){ 
            $result = yield Zephyr\Model\File\writeUserData('david', $data);
            
            $this->assertNotEmpty($result);
            
            $read = yield Zephyr\Model\File\getUserData('david');

            $this->assertEquals($data, $read);

        });
    }
}