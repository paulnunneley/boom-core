<?php

namespace BoomCMS\Tests\Chunk;

use BoomCMS\Chunk\BaseChunk;
use BoomCMS\Chunk\Provider;
use BoomCMS\Database\Models\Chunk\BaseChunk as ChunkModel;
use BoomCMS\Database\Models\Page;
use BoomCMS\Database\Models\PageVersion;
use BoomCMS\Support\Facades\Editor;
use BoomCMS\Support\Facades\Router;
use BoomCMS\Tests\AbstractTestCase;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Contracts\Auth\Access\Gate;
use Mockery as m;

class ProviderTest extends AbstractTestCase
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @var Provider
     */
    protected $provider;

    public function setUp()
    {
        parent::setUp();

        $this->gate = m::mock(Gate::class);
        $this->cache = m::mock(Cache::class);
        $this->provider = m::mock(Provider::class, [$this->gate, $this->cache])->makePartial();
    }

    public function testAllowedToEditWithoutPageIsTrue()
    {
        $this->assertTrue($this->provider->allowedToEdit());
    }

    public function testAllowedToEditIsFalseIfEditorIsNotEnabled()
    {
        Editor::shouldReceive('isEnabled')->once()->andReturn(false);

        $page = new Page();

        $this->assertFalse($this->provider->allowedToEdit($page));
    }

    public function testAllowedToEditIfUserCanEditPage()
    {
        $page = new Page();

        Editor::shouldReceive('isEnabled')->twice()->andReturn(true);

        $this->gate
            ->shouldReceive('allows')
            ->once()
            ->with('edit', $page)
            ->andReturn(false);

        $this->gate
            ->shouldReceive('allows')
            ->once()
            ->with('edit', $page)
            ->andReturn(true);

        $this->assertFalse($this->provider->allowedToEdit($page));
        $this->assertTrue($this->provider->allowedToEdit($page));
    }

    public function testGetClassName()
    {
        $type = 'text';
        $className = 'BoomCMS\Chunk\Text';

        $this->assertEquals($className, $this->provider->getClassName($type));
    }

    public function testGetModelName()
    {
        $type = 'text';
        $className = 'BoomCMS\Database\Models\Chunk\Text';

        $this->assertEquals($className, $this->provider->getModelName($type));
    }

    public function testGetFromCacheReturnsFalseIfNotFound()
    {
        $key = 'test';

        $this->provider
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);

        $this->cache
            ->shouldReceive('get')
            ->with($key, false)
            ->andReturn(false);

        $this->assertFalse($this->provider->getFromCache('test', 'test', new PageVersion()));
    }

    public function testGetFromCacheReturnsCachedItem()
    {
        $chunk = [];
        $key = 'test';

        $this->provider
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);

        $this->cache
            ->shouldReceive('get')
            ->with($key, false)
            ->andReturn($chunk);

        $this->assertEquals($chunk, $this->provider->getFromCache('test', 'test', new PageVersion()));
    }

    public function testGetCacheKey()
    {
        $type = 'text';
        $slotname = 'standfirst';
        $version = new PageVersion();
        $version->{PageVersion::ATTR_ID} = 1;

        $key = md5("$type-$slotname-{$version->getId()}");

        $this->assertEquals($key, $this->provider->getCacheKey($type, $slotname, $version));
    }

    public function testFindReturnsFromCached()
    {
        $chunk = [];
        $type = 'text';
        $slotname = 'standfirst';
        $version = new PageVersion();

        $this->provider
            ->shouldReceive('getFromCache')
            ->once()
            ->with($type, $slotname, $version)
            ->andReturn($chunk);

        $this->assertEquals($chunk, $this->provider->find($type, $slotname, $version));
    }

    public function testInsertWithPage()
    {
        $page = new Page();
        $type = 'text';
        $slotname = 'standfirst';
        $chunk = m::mock(BaseChunk::class);

        Router::shouldReceive('getActivePage')->once()->andReturn(new Page());

        $this->provider
            ->shouldReceive('get')
            ->once()
            ->with($type, $slotname, $page)
            ->andReturn($chunk);

        $this->assertEquals($chunk, $this->provider->insert($type, $slotname, $page));
    }

    public function testInsertWithActivePage()
    {
        $page = new Page();
        $type = 'text';
        $slotname = 'standfirst';
        $chunk = m::mock(BaseChunk::class);

        Router::shouldReceive('getActivePage')->once()->andReturn($page);

        $this->provider
            ->shouldReceive('edit')
            ->once()
            ->with($type, $slotname, $page)
            ->andReturn($chunk);

        $this->assertEquals($chunk, $this->provider->insert($type, $slotname, $page));
    }

    public function testInsertWithoutPage()
    {
        $type = 'text';
        $slotname = 'standfirst';
        $chunk = m::mock(BaseChunk::class);

        $this->provider
            ->shouldReceive('edit')
            ->once()
            ->with($type, $slotname, null)
            ->andReturn($chunk);

        $this->assertEquals($chunk, $this->provider->insert($type, $slotname));
    }

    public function testSaveToCache()
    {
        $chunk = m::mock(ChunkModel::class);
        $key = 'test';
        $type = 'text';
        $slotname = 'standfirst';
        $version = new PageVersion();

        $this->provider
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);

        $this->cache
            ->shouldReceive('forever')
            ->once()
            ->with($key, $chunk);

        $this->provider->saveToCache($type, $slotname, $version, $chunk);
    }

    public function testSaveToCacheCanSaveNull()
    {
        $key = 'test';
        $type = 'text';
        $slotname = 'standfirst';
        $version = new PageVersion();

        $this->provider
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);

        $this->cache
            ->shouldReceive('forever')
            ->once()
            ->with($key, null);

        $this->provider->saveToCache($type, $slotname, $version, null);
    }
}
