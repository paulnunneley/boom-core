<?php

namespace BoomCMS\Tests\Repositories;

use BoomCMS\Database\Models\Page;
use BoomCMS\Database\Models\Site;
use BoomCMS\Repositories\Page as PageRepository;
use BoomCMS\Support\Facades\Router;
use BoomCMS\Tests\AbstractTestCase;
use Mockery as m;

class PageTest extends AbstractTestCase
{
    /**
     * @var Page
     */
    protected $model;

    /**
     * @var Page
     */
    protected $page;

    /**
     * @var PageRepository
     */
    protected $repository;

    /**
     * @var type @var Site
     */
    protected $site;

    public function setUp()
    {
        parent::setUp();

        $this->model = m::mock(Page::class);
        $this->repository = m::mock(PageRepository::class, [$this->model])->makePartial();
        $this->page = m::mock(Page::class);

        $this->site = new Site();
        $this->site->{Site::ATTR_ID} = 1;

        Router::shouldReceive('getActiveSite')->andReturn($this->site);
    }

    public function testDelete()
    {
        $this->page->shouldReceive('delete');

        $this->assertEquals($this->repository, $this->repository->delete($this->page));
    }

    public function testFindByPrimaryUri()
    {
        $uri = 'test';

        $this->repository
            ->shouldReceive('findBySiteAndPrimaryUri')
            ->once()
            ->with($this->site, $uri)
            ->andReturn($this->page);

        $this->assertEquals($this->page, $this->repository->findByPrimaryUri($uri));
    }

    public function testFindBySiteAndPrimaryUri()
    {
        $uri = 'test';

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with(Page::ATTR_SITE, '=', $this->site->getId())
            ->andReturnSelf();

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with(Page::ATTR_PRIMARY_URI, '=', $uri)
            ->andReturnSelf();

        $this->model
            ->shouldReceive('first')
            ->once()
            ->andReturn($this->page);

        $this->assertEquals($this->page, $this->repository->findBySiteAndPrimaryUri($this->site, $uri));
    }

    public function testFindBySiteAndPrimaryUriWithArray()
    {
        $uris = ['test1', 'test2'];

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with(Page::ATTR_SITE, '=', $this->site->getId())
            ->andReturnSelf();

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with(Page::ATTR_PRIMARY_URI, 'in', $uris)
            ->andReturnSelf();

        $this->model
            ->shouldReceive('get')
            ->once()
            ->andReturn([$this->page, $this->page]);

        $this->assertEquals([$this->page, $this->page], $this->repository->findBySiteAndPrimaryUri($this->site, $uris));
    }

    public function testFindByUri()
    {
        $uri = 'test';

        $this->repository
            ->shouldReceive('findBySiteAndUri')
            ->once()
            ->with($this->site, $uri)
            ->andReturn($this->page);

        $this->assertEquals($this->page, $this->repository->findByUri($uri));
    }

    public function testFindBySiteAndUri()
    {
        $uri = 'test';

        $this->model
            ->shouldReceive('join')
            ->once()
            ->with('page_urls', 'page_urls.page_id', '=', 'pages.id')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with('pages.site_id', '=', $this->site->getId())
            ->andReturnSelf();

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with('location', '=', $uri)
            ->andReturnSelf();

        $this->model
            ->shouldReceive('select')
            ->once()
            ->with('pages.*')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('first')
            ->once()
            ->andReturn($this->page);

        $this->assertEquals($this->page, $this->repository->findBySiteAndUri($this->site, $uri));
    }

    public function testFindBySiteAndUriWithArray()
    {
        $uris = ['test1', 'test2'];

        $this->model
            ->shouldReceive('join')
            ->once()
            ->with('page_urls', 'page_urls.page_id', '=', 'pages.id')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with('pages.site_id', '=', $this->site->getId())
            ->andReturnSelf();

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with('location', 'in', $uris)
            ->andReturnSelf();

        $this->model
            ->shouldReceive('select')
            ->once()
            ->with('pages.*')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('get')
            ->once()
            ->andReturn([$this->page, $this->page]);

        $this->assertEquals([$this->page, $this->page], $this->repository->findBySiteAndUri($this->site, $uris));
    }

    public function testInternalNameExists()
    {
        $name = 'test';
        $exists = true;

        $this->model
            ->shouldReceive('withTrashed')
            ->once()
            ->andReturnSelf();

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with('internal_name', $name)
            ->andReturnSelf();

        $this->model
            ->shouldReceive('exists')
            ->once()
            ->andReturn($exists);

        $this->assertEquals($exists, $this->repository->internalNameExists($name));
    }

    public function testRecurse()
    {
        $pageId = 1;
        $children = [m::mock(Page::class), m::mock(Page::class)];

        $this->repository
            ->shouldReceive('findByParentId')
            ->once()
            ->with($pageId)
            ->andReturn($children);

        $this->model
            ->shouldReceive('getId')
            ->once()
            ->andReturn($pageId);

        $this->model
            ->shouldReceive('save')
            ->once();

        foreach ($children as $i => $child) {
            $child
                ->shouldReceive('getId')
                ->once()
                ->andReturn($i);

            $child
                ->shouldReceive('save')
                ->once();

            $this->repository
                ->shouldReceive('findByParentId')
                ->once()
                ->with($i)
                ->andReturn(null);
        }

        $this->repository->recurse($this->model, function (Page $page) {
            $page->save();
        });
    }
}
