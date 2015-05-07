<?php

namespace BoomCMS\Core\Page\Command\Delete;

use BoomCMS\Core\Page\Page as Page;
use \DB as DB;
use \ORM as ORM;

class FlagDeleted extends \Boom\Page\Command
{
    public function execute(Page $page)
    {
        DB::update('pages')
            ->set(['deleted' => true])
            ->where('id', '=', $page->getId())
            ->execute();

        ORM::factory('Page_MPTT', $page->getId())->delete();
    }
}
