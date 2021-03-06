<?php

namespace BoomCMS\Jobs;

use BoomCMS\Contracts\Models\Person;
use BoomCMS\Contracts\Models\Site;
use BoomCMS\Database\Models\Page;
use BoomCMS\Events\PageWasCreated;
use BoomCMS\Support\Facades\Page as PageFacade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class CreatePage extends Command
{
    /**
     * @var Person
     */
    protected $createdBy;

    /**
     * @var Page
     */
    protected $parent;

    /**
     * The site to which to add the new page.
     *
     * @var Site
     */
    protected $site;

    /**
     * @var string
     */
    protected $title = Page::DEFAULT_TITLE;

    /**
     * @param Person $createdBy
     * @param Page   $parent
     */
    public function __construct(Person $createdBy, Site $site, Page $parent = null)
    {
        $this->createdBy = $createdBy;
        $this->parent = $parent;
        $this->site = $site;
    }

    public function handle()
    {
        $attrs = [
            'created_time' => time(),
            'created_by'   => $this->createdBy->getId(),
            'site_id'      => $this->site->getId(),
        ];

        if ($this->parent) {
            $attrs += [
                'parent_id'                   => $this->parent->getId(),
                'visible_in_nav'              => $this->parent->childrenAreVisibleInNav(),
                'visible_in_nav_cms'          => $this->parent->childrenAreVisibleInCmsNav(),
                'children_visible_in_nav'     => $this->parent->childrenAreVisibleInNav(),
                'children_visible_in_nav_cms' => $this->parent->childrenAreVisibleInCmsNav(),
                'enable_acl'                  => $this->parent->aclEnabled(),
            ];
        }

        $page = PageFacade::create($attrs);

        $page->addVersion([
            'template_id'     => $this->parent ? $this->parent->getDefaultChildTemplateId() : null,
            'title'           => $this->title,
            'embargoed_until' => time(),
        ]);

        if ($this->parent) {
            $groupIds = $this->parent->getAclGroupIds();

            foreach ($groupIds as $groupId) {
                $page->addAclGroupId($groupId);
            }
        }

        Event::fire(new PageWasCreated($page, $this->parent));

        return $page;
    }

    /**
     * Set a title to be used for the new page.
     *
     * @param type $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }
}
