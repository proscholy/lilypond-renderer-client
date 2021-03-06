<?php

use Orchestra\Testbench\TestCase;
use ProScholy\LilypondRenderer\Client;
use ProScholy\LilypondRenderer\RenderResult;
use ProScholy\LilypondRenderer\LilypondSrc;

class LilypondRendererClientTest extends TestCase
{
    protected Client $client;

    protected function getPackageProviders($app)
    {
        return ['ProScholy\LilypondRenderer\LilypondRendererServiceProvider'];
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->client = new Client();
    }

    public function testBasicLilypond()
    {
        $res = $this->client->renderSvg('{ c }');

        $this->assertIsString($res->getTmp());
        $this->assertIsArray($res->getContents());

        return $res;
    }

    /**
     * @depends testBasicLilypond
     */
    public function testLilypondSuccess($res)
    {
        $this->assertTrue($res->isSuccessful());
    }

    /**
     * @depends testBasicLilypond
     */
    public function testLilypondGetSvg($res)
    {
        $svg = $this->client->getResultOutputFile($res);

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
    }
    
    /**
     * @depends testBasicLilypond
     */
    public function testLilypondGetLog($res)
    {
        $log = $this->client->getResultLog($res);

        $this->assertIsString($log);
        $this->assertStringContainsString('Success: compilation successfully completed', $log);
    }

    /**
     * @depends testBasicLilypond
     */
    public function testDeleteResult($res)
    {
        $deleted = $this->client->deleteResult($res);

        $this->assertTrue($deleted);
        $this->assertTrue($res->isDeleted());

        return $res;
    }

    /**
     * @depends testDeleteResult
     */
    public function testDeleteDeletedResult($res)
    {
        $deletingSuccess = $this->client->deleteResult($res);

        $this->assertFalse($deletingSuccess);
        $this->assertTrue($res->isDeleted());
    }

    public function testDeleteNonExistentDir()
    {
        $fakeDir = new stdClass();
        $fakeDir->name = "your_mama";
        $fakeResult = new RenderResult('whatever_recipe', [$fakeDir]);

        $deletingSuccess = $this->client->deleteResult($fakeResult);

        $this->assertFalse($deletingSuccess);
    }

    public function testLilypondFromLilypondSrc()
    {
        $ly_src = new LilypondSrc('{ c }');
        $ly_src->applyLayout()->applyInfinitePaper();

        $res = $this->client->renderSvg($ly_src, false);

        $this->assertIsString($res->getTmp());
        $this->assertIsArray($res->getContents());

        return $res;
    }

    /**
     * @depends testLilypondFromLilypondSrc
     */
    public function testLilypondFromLilypondSrcSuccess($res)
    {
        $this->assertTrue($res->isSuccessful());

        $svg = $this->client->getResultOutputFile($res);

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
    }


    // MALFORMED LILYPOND SRC

    public function testLilypondErr()
    {
        $res = $this->client->renderSvg('{ c ');

        $this->assertFalse($res->isSuccessful());
        return $res;
    }

    /**
     * @depends testLilypondErr
     */
    public function testLilypondErrorLog($res)
    {
        $log = $this->client->getResultLog($res);

        $this->assertIsString($log);
        $this->assertStringContainsString('fatal error: failed files: "score.ly"', $log);
    }
}