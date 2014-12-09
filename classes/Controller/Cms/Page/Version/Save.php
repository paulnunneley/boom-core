<?php

class Controller_Cms_Page_Version_Save extends Controller_Cms_Page_Version
{
    /**
	 * @var Database
	 */
    public $db;

    /**
	 * @var	Model_Page_Version
	 */
    public $new_version;

    public function before()
    {
        parent::before();

        $this->_csrf_check();

        // Start a database transaction.
        $this->db = Database::instance();
        $this->db->begin();

        // Create a new version of the page.
        $this->new_version = $this->page->createVersion($this->old_version, [
            'edited_by'    =>    $this->person->getId(),
        ]);

        // If the embargo time of the new version is in the past, set the embargo time to null
        // This means that if the old version was published, the new version will be a draft.
        // If the embargo time is in the future don't change it.
        if ($this->new_version->embargoed_until <= $_SERVER['REQUEST_TIME']) {
            $this->new_version->embargoed_until = null;
        }
    }

    public function action_embargo()
    {
        parent::action_embargo();

        $embargoed_until = $this->request->post('embargoed_until') ? strtotime($this->request->post('embargoed_until')) : $_SERVER['REQUEST_TIME'];

        $this->new_version
            ->set('pending_approval', false)
            ->create()
            ->embargo($embargoed_until)
            ->copy_chunks($this->old_version);

        if ($this->new_version->is_published()) {
            $commander = new \Boom\Page\Commander($this);

            return $commander
                ->addCommand(new \Boom\Page\Command\Delete\Drafts())
                ->execute();
        }
    }

    public function action_request_approval()
    {
        parent::action_request_approval();

        $this->new_version
            ->set('pending_approval', true)
            ->create()
            ->copy_chunks($this->old_version);
    }

    public function action_template()
    {
        parent::action_template();

        $this->new_version
            ->set('template_id', $this->request->post('template_id'))
            ->create()
            ->copy_chunks($this->old_version);

        $this->page->getTemplate()->onApplied($this->page);
    }

    public function action_title()
    {
        $this->new_version->set('title', $this->request->post('title'));

        if ($this->new_version->changed('title') && $this->old_version->title == 'Untitled' && ! $this->page->getMptt()->is_root()) {
            $location = \Boom\Page\URL::fromTitle($this->page->parent()->url()->location, $this->request->post('title'));
            $url = \Boom\Page\URL::createPrimary($location, $this->page->getId());

            // Put the page's new URL in the response body so that the JS will redirect to the new URL.
            $this->response->body(json_encode([
                'location' => URL::site($url->location, $this->request),
            ]));
        }

        $this->new_version
            ->create()
            ->copy_chunks($this->old_version);

        $this->page->getTemplate()->onPageSave($this->page);
    }

    public function after()
    {
        // Commit the changes.
        $this->db->commit();

        if ( ! $this->response->body()) {
            $this->response->body($this->new_version->status());
        }

        parent::after();
    }
}
