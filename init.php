<?php

// Set the directory where assets are stored.
Boom_Asset::$path = APPPATH.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR;

// Route for displaying assets
Route::set('asset', 'asset/<action>/<id>(/<width>(/<height>(/<quality>(/<crop>))))')
	->defaults(array(
		'action'	 => 'view'
	))
	->filter(function(Route $route, $params, Request $request)
		{
			// Try and get the asset from the database.
			$asset = new Model_Asset($params['id']);

			// Does the asset exist?
			if ( ! ($asset->loaded() AND file_exists(Boom_Asset::$path.$asset->id)))
			{
				return FALSE;
			}

			// Put the asset in the request params.
			$params['asset'] = $asset;

			// Set the controller depending on the asset type.
			$params['controller'] = 'Asset_'.ucfirst($asset->type());

			// Return the new request params.
			return $params;
		}
	);

/**
 * Checks for a page with the matching URL in the CMS database.
 *
 * TODO: change action depending on request headers.
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
			$page_url = new Model_Page_URL(array('location' => $params['location']));

			if ( ! $page_url->loaded())
			{
				return FALSE;
			}

			$page = ORM::factory('Page')
				->with_current_version(Editor::instance())
				->where('page.id', '=', $page_url->page_id)
				->find();

			if ($page->loaded())
			{
				if ( ! $page_url->is_primary AND $page_url->redirect)
				{
					header('Location: '.$page->url(), NULL, 301);
					exit;
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
			$redirect_to = ORM::factory('Page', $page_id)->url();

			header('Location: '.$redirect_to, NULL, 302);
			exit;
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
Route::set('child_page_plugin', 'page/children.<action>')
	->defaults(array(
		'controller'	=>	'page_children'
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
		'controller'	=>	'settings|version|tags',
	))
	->defaults(array(
		'directory' => 'cms_page',
	));

Route::set('page_urls', 'cms/page/url/<action>/<id>' )
	->defaults(array(
		'controller' => 'Cms_Page_URL',
	));