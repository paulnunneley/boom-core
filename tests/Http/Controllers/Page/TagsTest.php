<?php

namespace BoomCMS\Tests\Http\Controllers;

use BoomCMS\Database\Models\Page;
use BoomCMS\Database\Models\Site;
use BoomCMS\Database\Models\Tag;
use BoomCMS\Http\Controllers\Page\Tags as Controller;
use BoomCMS\Support\Facades\Tag as TagFacade;
use Illuminate\Http\Request;
use Mockery as m;

class TagsTest extends BaseControllerTest
{
    /**
     * @var string
     */
    protected $className = Controller::class;

    /**
     * @var Page
     */
    protected $page;

    /**
     * @var Tag
     */
    protected $tag;

    public function setUp()
    {
        parent::setUp();

        $this->tag = new Tag();
        $this->tag->{Tag::ATTR_ID} = 1;

        $this->page = m::mock(Page::class);

        $this->requireRole('edit', $this->page);
    }

    public function testAdd()
    {
        $site = new Site();
        $name = 'test';
        $group = 'group';

        $request = new Request([
            'tag'   => $name,
            'group' => $group,
        ]);

        $this->page->shouldReceive('addTag')->with($this->tag);

        TagFacade::shouldReceive('findOrCreate')
            ->with($site, $name, $group)
            ->andReturn($this->tag);

        $this->assertEquals($this->tag->getId(), $this->controller->add($request, $site, $this->page));
    }

    public function testRemove()
    {
        $this->page->shouldReceive('removeTag')->with($this->tag);

        $this->controller->remove($this->page, $this->tag);
    }
}
