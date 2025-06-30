<?php

use PHPUnit\Framework\TestCase;
use Arris\Entity\File;

class FileTest extends TestCase
{
    public static File $catfile;

    /**
     * @return void
     * @testdox
     */
    public static function setUpBeforeClass():void
    {
        parent::setUpBeforeClass();

        self::$catfile = new File(__DIR__ . '/the_cat.jpg');
    }

    /*public function tearDown():void
    {
        $this->tearDown();
        self::$catfile->close();
    }*/

    /**
     * @return void
     * @testdox This file exists
     */
    public function testExists(): void
    {
        $file = new File(__FILE__);
        $this->assertTrue( $file->exists() );
    }

    /**
     * @return void
     * @testdox Cat file directory
     */
    public function testGetPathThis():void
    {
        $this->assertEquals(__DIR__, self::$catfile->getDirectory());
    }

    /**
     * @return void
     * @testdox Cat file path (directory + filename)
     */
    public function testGetPathCat()
    {
        $this->assertEquals(__DIR__ . '/the_cat.jpg', self::$catfile->getPath());
    }

    /**
     * @return void
     * @testdox Cat file extension
     */
    public function testGetExtension():void
    {
        $this->assertEquals('jpg', self::$catfile->getExtension());
    }

    /**
     * @return void
     * @testdox Cat file name
     */
    public function testGetFilename()
    {
        $this->assertEquals("the_cat.jpg", self::$catfile->getFilename());
    }

    /**
     * @return void
     * @testdox Cat file name w/o extension
     */
    public function testGetFilenameWithoutExtension()
    {
        $this->assertEquals("the_cat", self::$catfile->getFilenameWithoutExtension());
    }

    /**
     * @return void
     * @testdox Cat file mimetype
     */
    public function testMimetype()
    {
        $this->assertEquals("image/jpeg", self::$catfile->getMimeType());
    }

    /**
     * @return void
     * @testdox Cat file size
     */
    public function testSize()
    {
        $this->assertEquals(285_267, self::$catfile->getSize());
    }

    /**
     * @return void
     * @testdox Cat file flags
     */
    public function testFlags()
    {
        $this->assertTrue( self::$catfile->isReadable());
        $this->assertTrue( self::$catfile->isWritable());
        $this->assertFalse( self::$catfile->isExecutable());
        $this->assertFalse( self::$catfile->isLink());
    }

    /**
     * @return void
     * @testdox Cat file SHA1 hash
     */
    public function testHash()
    {
        $this->assertEquals("d13d118ae0d3c4b7bf5883a3ced99ff2de1751639bd6634511d5bd1f84d0c3e2", self::$catfile->getHash());
    }

    /**
     * @return void
     * @testdox Cat file is IMAGE
     */
    public function testIsImage()
    {
        $this->assertTrue(self::$catfile->isImage());
    }

    /**
     * @return void
     * @testdox Cat file is VIDEO
     */
    public function testIsVideo()
    {
        $this->assertFalse(self::$catfile->isVideo());
    }

    /**
     * @return void
     * @testdox Put content to newly created file test.txt
     */
    public function testPutContent()
    {
        $message = "Test message!";
        fopen(__DIR__ . '/test.txt', 'w+');

        $f = new File(__DIR__ . '/test.txt');
        $l = $f->putContent($message);
        
        $this->assertEquals(strlen($message), $l);

        $f->delete();
    }

    /**
     * @return void
     * @testdox Get content from directly created file
     */
    public function testGetContent()
    {
        $message = "Test message!";
        $h = fopen(__DIR__ . '/test.txt', 'w+');
        fwrite($h, $message);

        $f = new File(__DIR__ . '/test.txt');

        $this->assertEquals($message, $f->getContent());

        $f->delete();
    }

    /**
     * @return void
     * @testdox Put and get content to file
     */
    public function testPutGetContent()
    {
        $message = "Test message!";
        $h = fopen(__DIR__ . '/test.txt', 'w+');


        $f = new File(__DIR__ . '/test.txt');
        $f->putContent($message);

        $this->assertEquals($message, $f->getContent());

        $f->delete();
    }

    /**
     * @return void
     * @testdox Truncate file
     */
    public function testTruncateFile()
    {
        $message = "Test message!";
        $h = fopen(__DIR__ . '/test.txt', 'w+');
        fwrite($h, $message);

        $f = new File(__DIR__ . '/test.txt');

        $this->assertTrue($f->truncate());
        $this->assertEquals(0, $f->getSize());

        $f->delete();
    }

    /**
     * @return void
     * @testdox Copy cat file to ./temp directory and check exists
     */
    public function testCopy()
    {
        $f1 = new File(__DIR__ . '/the_cat.jpg');

        $f2 = $f1->copy(__DIR__ . '/temp/the_cat.jpg');

        $this->assertTrue( $f2->exists() );

        $f2->delete();
    }

    /**
     * @return void
     * @testdox Create copy of cat file, move it and check exists
     */
    public function testMoveToFile()
    {
        $f1 = new File(__DIR__ . '/the_cat.jpg');

        $f2 = $f1->copy(__DIR__ . '/2.jpg');

        $f2->move(__DIR__ . '/temp/3.jpg');

        $this->assertTrue( $f2->exists() );

        $f3 = new File(__DIR__ . '/temp/3.jpg');

        $this->assertTrue( $f3->exists());

        $f2->delete();

        $this->assertFalse($f3->exists());
    }

    /**
     * @return void
     * @testdox Create copy of cat file, move it to directory and check exists
     */
    public function testMoveToDirectory()
    {
        $f1 = new File(__DIR__ . '/the_cat.jpg');

        $f2 = $f1->copy(__DIR__ . '/2.jpg');

        $f2->move(__DIR__ . '/temp/');

        $this->assertTrue( $f2->exists() );

        $f3 = new File(__DIR__ . '/temp/2.jpg');

        $this->assertTrue( $f3->exists());

        $f2->delete();
    }

    /**
     * @return void
     * @testdox Check lastmod time of catfile
     */
    public function testGetLastModTime()
    {
        $file_mod_time = filemtime(__DIR__ . '/the_cat.jpg');

        $this->assertEquals(
            $file_mod_time,
            self::$catfile->getLastModifiedTime()
        );
    }

    /**
     * @return void
     * @testdox Open catfile and check file handler
     */
    public function testOpenFile()
    {
        self::$catfile->open();

        $this->assertIsResource(self::$catfile->handler);
        $this->assertTrue(self::$catfile->is_opened);
    }

    /**
     * @return void
     * @testdox Create temp file with content, read and test equal
     */
    public function testCreateTempFile()
    {
        $content_to = 'Sample entity content';
        $file = File::createTemp('entity_', $content_to);

        $content_from = $file->getContent();

        $this->assertEquals($content_from, $content_to);
    }

    /**
     * @return void
     * @testdox Create temp file, write, read and force delete
     */
    public function testCreateTempFileAndWrite(): void
    {
        $content_to = 'Sample entity content';
        $file = File::createTemp('entity_');
        $file->putContent($content_to);

        $this->assertTrue($file->exists());

        $content_from = $file->getContent();

        $this->assertEquals($content_from, $content_to);
        $file->delete();

        $this->assertFalse($file->exists());
    }

    /**
     * @return void
     * @testdox Create regular file, write content, read later, then - remove
     */
    public function testCreateRegularFileWithContent(): void
    {
        $content_to = 'Sample entity content';
        $file = File::create(__DIR__ . '/test.txt');

        $this->assertTrue($file->exists());

        $file->putContent($content_to);

        $file_test = new File(__DIR__ . '/test.txt');

        $this->assertEquals(
            $file_test->getContent(),
            $content_to
        );

        $file_test->close();
    }

    /**
     * @return void
     * @testdox Close file
     */
    /*public function testClose(): void
    {
        $this->assertTrue(self::$catfile->is_opened);

        // var_dump(self::$catfile);

        self::$catfile->close();
        $this->assertFalse(self::$catfile->is_opened);
    }*/

    /**
     * @return void
     * @testdox Put content to file, twice, with append
     */
    public function testAppend(): void
    {
        $message1 = 'Test message';
        $message2 = 'Appended message';
        $file = new File(__DIR__ . '/test.txt');
        $file->putContent($message1);
        $file->putContent($message2);

        $this->assertEquals($message2, $file->getContent());

        $file->truncate();

        $this->assertEquals(0, $file->getSize());

        $file->putContent($message1);
        $file->putContent($message2, FILE_APPEND);

        $this->assertEquals($message1 . $message2, $file->getContent());

        $file->delete();
        $this->assertFalse($file->exists());
    }

    /**
     * @return void
     * @testdox Read from position
     */
    public function testReadFromPosition():void
    {
        $file = File::create(__DIR__ .'/temp/test.txt', "Test message");

        $this->assertEquals("message", $file->readFromPosition(5));

        $file->delete();
    }

    /**
     * @return void
     * @testdox Write from position and check content
     */
    public function testWriteFromPosition():void
    {
        $file = File::create(__DIR__ .'/temp/test.txt', "Test message about...");

        $file->writeFromPosition("MESSAGE", 5);

        $this->assertEquals("Test MESSAGE about...", $file->getContent());

        $file->delete();
    }





}
