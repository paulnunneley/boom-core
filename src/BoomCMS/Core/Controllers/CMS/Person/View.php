<?php

use BoomCMS\Core\Group;

class Controller_Cms_Person_View extends Controller_Cms_Person
{
    public function add()
    {
        return View::make($this->viewDirectory."new", [
            'groups'    =>    ORM::factory('Group')->names(),
        ]);
    }

    public function add_group()
    {
        $finder = new Group\Finder();
        $finder
            ->addFilter(new Group\Finder\Filter\ExcludingPersonsGroups($this->edit_person))
            ->setOrderBy('name');

        return View::make("$this->viewDirectory/addgroup", [
            'person' => $this->edit_person,
            'groups' => $finder->findAll(),
        ]);
    }

    public function view()
    {
        if ( ! $this->edit_person->loaded()) {
            throw new HTTP_Exception_404();
        }

        return View::make($this->viewDirectory."view", [
            'person' => $this->edit_person,
            'request' => $this->request,
            'groups' => $this->edit_person->getGroups(),
            'activities' => [],
        ]);
    }
}
