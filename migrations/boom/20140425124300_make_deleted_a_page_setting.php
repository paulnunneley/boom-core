<?php defined('SYSPATH') or die('No direct script access.');

class Migration_Boom_20140425124300 extends Minion_Migration_Base
{

	public function up(Kohana_Database $db)
	{
		$db->query(null, "alter table pages add deleted boolean default false");

		$pages = ORM::factory('Page')->find_all();
		foreach ($pages as $page)
		{
			$result = DB::select('page_deleted')
				->from('page_versions')
				->where('id', '=', $page->version()->id)
				->execute()
				->as_array();

			if (isset($result[0]))
			{
				$page->isDeleted() = $result[0]['page_deleted'];
				$page->update();
			}
		}

		$db->query(null, "alter table page_versions drop page_deleted");
		$db->query(null, "alter table page_versions drop index page_v_id_deleted");
		$db->query(null, 'alter table page_versions drop index page_versions_page_id_page_deleted');
		$db->query(null, 'create index pages_sitelist on pages(deleted, visible, visible_from desc, visible_to, visible_in_nav)');
		$db->query(null, 'create index pages_sitelist on pages(deleted, visible_in_nav_cms, visible_from desc)');
		$db->query(null, "alter table pages drop index pages_visible_visible_from_visible_to_visible_in_nav");
		$db->query(null, "alter table pages drop index pages_visible_visible_from_visible_to_visible_in_nav_cms");
	}

	public function down(Kohana_Database $db)
	{
	}
}