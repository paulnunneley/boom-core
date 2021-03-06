<?php

namespace BoomCMS\Http\Controllers\Page;

use BoomCMS\Database\Models\Page;
use BoomCMS\Database\Models\Site;
use BoomCMS\Events\PageWasReverted;
use BoomCMS\Http\Controllers\Controller;
use BoomCMS\Jobs\CreatePage;
use BoomCMS\Support\Facades\Page as PageFacade;
use BoomCMS\Support\Facades\PageVersion;
use BoomCMS\Support\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class PageController extends Controller
{
    public function getIndex(Request $request)
    {
        return Helpers::getPages($request->input());
    }

    /**
     * @param Site $site
     * @param Page $page
     *
     * @return Page
     */
    public function postAdd(Site $site, Page $page)
    {
        $this->authorize('add', $page);

        $parent = $page->getAddPageParent();
        $newPage = $this->dispatch(new CreatePage(auth()->user(), $site, $parent));

        return PageFacade::find($newPage->getId());
    }

    public function postDiscard(Page $page)
    {
        $this->authorize('edit', $page);

        Event::fire(new PageWasReverted($page));

        PageVersion::deleteDrafts($page);
    }
}
