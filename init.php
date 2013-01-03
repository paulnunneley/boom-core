<?php

/* Include the userguide module if this isn't a live instance.
* @link http://kohanaframework.org/3.2/guide/userguide
*/
if (Kohana::$environment === Kohana::DEVELOPMENT)
{
	Kohana::modules(array_merge(Kohana::modules(), array(MODPATH.'guide')));
}

/**
* Route for displaying assets
*
*/
Route::set('asset', 'asset/<action>/<id>(/<width>(/<height>(/<quality>(/<crop>))))')
	->defaults(array(
		'controller' => 'asset',
		'action'	 => 'view'
	));

/**
 * Checks for a page with the matching URL in the CMS database.
 */
Route::set('boom', '<location>(.<action>)', array(
		'location'	=>	'.*?',
	))
	->defaults(array(
		'controller'	=>	'page',
		'action'		=>	'html',
	))
	->filter(function(Route $route, $params, Request $request)
		{
			$page_link = new Model_Page_Link(array('location' => $params['location']));

			if ( ! $page_link->loaded())
			{
				return FALSE;
			}

			$page = ORM::factory('Page')
				->with_current_version(Editor::instance())
				->where('page.id', '=', $page_link->page_id)
				->find();

			if ($page->loaded())
			{
				if ( ! $page_link->is_primary AND $page_link->redirect)
				{
					HTTP::redirect($page->link(), 301);
					return FALSE;
				}

				$params['page'] = $page;
				return $params;
			}

			return FALSE;
		}
	);

/**
 * Route for vanity URIs. Vanity URIs are the page ID base-36 encoded and prefixed with an underscore.
 * Vanity URIs redirect to the page's primary URI
 */
Route::set('vanity', '_<link>', array(
		'link'	=>	'[a-zA-Z0-9]',
	))
	->filter(function(Route $route, $params, Request $request)
		{
			// Turn the vanity URI into a page ID.
			$page_id = base_convert($params['link'], 36, 10);

			HTTP::redirect(ORM::factory('Page', $page_id)->link(), 302);
			return FALSE;
		}
	);

/**
 * Defines the route for plugin controllers.
 */
Route::set('plugin', '<directory>/<controller>(/<action>)',
	array(
		'directory'	=> 'plugin'
	))
	->defaults(array(
		'controller' => 'default',
		'action'     => 'index',
	));

// Route for the child page list plugin.
Route::set('child_page_plugin', 'plugin/page/children.<action>')
	->defaults(array(
		'controller'	=>	'plugin_page_children'
	));


/**********************************
 *
 * Routes for logged in users only below
 *
 **********************************
 */
/**
* Defines the route for /cms pages.
*
*/
Route::set('cms', '<directory>/<controller>(/<action>(/<id>))',
	array(
		'directory'	=> 'cms'
	))
	->defaults(array(
		'action'     => 'index',
	));

Route::set('chunks', 'cms/chunk/<controller>/<action>/<page>')
	->defaults(array(
		'directory'	=>	'cms_chunk'
	));

// Route for editing page settings.
Route::set('page_settings', 'cms/page/<controller>/<action>/<id>', array(
		'controller'	=>	'settings|version',
	))
	->defaults(array(
		'directory' => 'cms_page',
	));

Route::set('page_links', 'cms/page/link/<action>/<id>' )
	->defaults(array(
		'controller' => 'cms_page_link',
	));