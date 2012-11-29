<?php defined('SYSPATH') OR die('No direct script access.');

/**
 *
 * @package	Sledge
 * @category	Models
 * @author	Rob Taylor
 * @copyright	Hoop Associates
 *
 */
class Sledge_Model_Page extends ORM_Taggable
{
	/**
	 * Properties to create relationships with Kohana's ORM
	 */
	protected $_belongs_to = array(
		'mptt'		=>	array('model' => 'Page_MPTT', 'foreign_key' => 'id')
	);

	protected $_has_many = array(
		'revisions'	=> array('model' => 'Page_Version', 'foreign_key' => 'page_id'),
		'links'	=> array('model' => 'Page_Link', 'foreign_key' => 'page_id'),
		'chunks'	=> array('through' => 'pages_chunks'),
	);

	protected $_table_columns = array(
		'id'						=>	'',
		'deleted'					=>	'',
		'active_vid'				=>	'',
		'sequence'					=>	'',
		'published_vid'				=>	'',
		'visible'					=>	'',
		'visible_from'				=>	'',
		'visible_to'				=>	'',
		'internal_name'				=>	'',
		'external_indexing'			=>	'',
		'internal_indexing'			=>	'',
		'visible_in_nav'				=>	'',
		'visible_in_nav_cms'			=>	'',
		'children_visible_in_nav'		=>	'',
		'children_visible_in_nav_cms'	=>	'',
		'children_template_id'		=>	'',
		'children_link_prefix'			=>	'',
		'children_ordering_policy'		=>	'',
		'children_prompt_for_template'	=>	'',
		'grandchild_template_id'		=>	'',
	);

	protected $_cache_columns = array('internal_name');

	/**
	 * Child ordering policy value for manual
	 * @var	integer
	 */
	const CHILD_ORDER_MANUAL = 1;

	/**
	 * Child ordering policy value for alphabetic
	 * @var	integer
	 */
	const CHILD_ORDER_ALPHABETIC = 2;

	/**
	 * Child ordering policy value for date
	 * @var	integer
	 */
	const CHILD_ORDER_DATE = 4;

	/**
	 * Child ordering policy for ascending.
	 * @var	integer
	 */
	const CHILD_ORDER_ASC = 8;

	/**
	 * Child ordering policy for descending.
	 * @var	integer
	 */
	const CHILD_ORDER_DESC = 16;

	/**
	 * Hold the calculated thumbnail for this version.
	 * @see Model_Version_Sledge_Asset::thumbnail()
	 * @var Model_Asset
	 */
	protected $_thumbnail;

	/**
	 * Holds the calculated primary URI
	 *
	 * @access	private
	 * @var		string
	 */
	private $_primary_link;

	/**
	 * Cached result for self::link()
	 *
	 * @access	private
	 * @var		string
	 */
	private $_link;

	/**
	 * Adds a new child page to this page's MPTT tree.
	 * Ensures that the child is added in the correct position according to this page's child ordering policy.
	 *
	 * @param	Model_Page	$page	The new child page.
	 * @return	Model_Page
	 */
	public function add_child(Model_Version_Page $page)
	{
		// Get the child ordering policy column and direction.
		list($column, $direction) = $this->children_ordering_policy();

		// Find the page_mptt record of the page which comes after this one.
		$mptt = ORM::factory('page_mptt')
			->join('page_versions', 'inner')
			->on('page_versions.page_id', '=', 'page_mptt.id')
			->join(array(
				DB::select(array(DB::expr('max(id)'), 'id'), 'page_id')
					->from('page_versions')
					->group_by('page_id'),
				'pages'
			))
			->on('page_versions.page_id', '=', 'pages.page_id')
			->on('page_versions.id', '=', 'pages.id')
			->where('page_mptt.parent_id', '=', $this->mptt->id)
			->where($column, '>', $page->$column)
			->order_by($column, $direction)
			->limit(1)
			->find();

		if ( ! $mptt->loaded())
		{
			// If a record wasn't loaded then there's no page after this one.
			// Insert as the last child of the parent.
			$page->mptt->insert_as_last_child($this->mptt);
		}
		else
		{
			$page->mptt->insert_as_next_sibling($mptt);
		}

		// Reload the MPTT values for this page.
		$this->mptt->reload();

		// Return the current object.
		return $this;
	}

	/**
	 * Getter / setter for the children_ordering_policy column.
	 * When used as a getter converts the integer stored in the children_ordering_policy column to an array of column and direction which can be used when querying the database.
	 * When used as a setter converts the column and direction to an integer which can be stored in the children_ordering_policy column.
	 *
	 * @param	string	$column
	 * @param	string	$direction
	 */
	public function children_ordering_policy($column = NULL, $direction = NULL)
	{
		if ($column === NULL AND $direction === NULL)
		{
			// Act as getter.
			// Determine which column to sort by.
			if ($this->children_ordering_policy & Model_Page::CHILD_ORDER_ALPHABETIC)
			{
				$column = 'title';
			}
			elseif ($this->children_ordering_policy & Model_Page::CHILD_ORDER_DATE)
			{
				$column = 'visible_from';
			}
			else
			{
				$column = 'sequence';
			}

			// Determine the direction to sort in.
			$direction = ($this->children_ordering_policy & Model_Page::CHILD_ORDER_ASC)? 'asc' : 'desc';

			// Return the column and direction as an array.
			return array($column, $direction);
		}
		else
		{
			// Act as setter.

			// Convert the column into an integer.
			switch ($column)
			{
				case 'manual':
					$order = Model_Page::CHILD_ORDER_MANUAL;
					break;

				case 'date':
					$order = Model_Page::CHILD_ORDER_DATE;
					break;

				default:
					$order = Model_Page::CHILD_ORDER_ALPHABETIC;
			}

			// Convert the direction to an integer and apply it to $order
			switch ($direction)
			{
				case 'asc':
					$order | Model_Page::CHILD_ORDER_ASC;
					break;

				default:
					$order | Model_Page::CHILD_ORDER_DESC;
			}

			// Set the value of the children_ordering_policy column.
			$this->children_ordering_policy = $order;

			// Rethrn the current object.
			return $this;
		}
	}

	/**
	 * Delete a page.
	 *
	 * Deleting a page involves three things:
	 *	*	Sets the deleted flag on the page to true
	 *	*	Deletes the page from the MPTT tree.
	 *	*	If $with_children is true calls itself recursively to delete child pages
	 *
	 * @param	boolean	$with_children	Whether to delete the child pages as well.
	 * @return	Model_Version_Page
	 */
	public function delete($with_children = FALSE)
	{
		// Can't delete a page which doesn't exist.
		if ($this->loaded())
		{
			// Delete the child pages as well?
			if ($with_children === TRUE)
			{
				// Get the mptt values of the child pages.
				foreach ($this->mptt->children() as $mptt)
				{
					// Delete the page.
					ORM::factory('Page', $mptt->id)
						->delete($with_children);
				}

				// Reload the MPTT values.
				// When the MPTT record is deleted the gap is closed in the tree
				// So if we don't reload the values after deleting children the gap will be closed incorrectly and the tree will get screwed up.
				$this->mptt->reload();
			}

			// Delete the page from the MPTT tree.
			$this->mptt->delete();

			// Flag the page as deleted.
			$this->deleted = TRUE;

			// Save the page.
			$this->save();

			// Return a cleared object.
			return $this->clear();
		}
	}

	/**
	 * Get the first version of the current page.
	 */
	public function first_version()
	{
		return ORM::factory('Page')
			->where('page_id', '=', $this->id)
			->order_by('id', 'asc')
			->find();
	}

	/**
	 * Determine whether a published version exists for the page.
	 *
	 * @return	bool
	 */
	public function has_published_version()
	{
		return TRUE;
		return ! ($this->published_vid == 0);
	}

	/**
	 * Checks that a page is published.
	 * @return	boolean
	 */
	public function is_published()
	{
		return TRUE;
		return ($this->published_from <= $_SERVER['REQUEST_TIME'] AND ($this->published_to >= $_SERVER['REQUEST_TIME'] OR $this->published_to == 0));
	}

	public function is_visible()
	{
		return ($this->visible AND $this->visible_from <= $_SERVER['REQUEST_TIME'] AND ($this->visible_to >= $_SERVER['REQUEST_TIME'] OR $this->visible_to == 0));
	}

	/**
	 * Returns the page's absolute link.
	 * This method uses Kohana's URL::base() method to generate the base URL from the current request (protocol, hostnane etc.) {@link http://kohanaframework.org/3.2/guide/api/URL#base}
	 *
	 * @uses	URL::base()
	 * @uses	Request::Instance()
	 * @return string The absolute URI.
	 */
	public function link()
	{
		if ($this->_link === NULL)
		{
			// Get the base link of the current request.
			$this->_link = URL::site($this->primary_link());
		}

		return $this->_link;
	}

	/**
	 * Get the parent page for the current page.
	 * If the current page is the root node then the current page is returned.
	 * Otherwise a page object for the parent page is returned.
	 *
	 * @return 	Model_Version_Page
	 */
	public function parent()
	{
		return ($this->mptt->is_root())? $this : ORM::factory('Page', $this->mptt->parent_id);
	}

	/**
	 * Get the page's primary URI
	 * From the page's available URIs finds the one which is marked as the primary URI.
	 *
	 * @return	string	The RELATIVE primary URI of the page.
	 */
	public function primary_link()
	{
		if ($this->_primary_link == NULL)
		{
			$cache_key = "primary_link_for_page:$this->id";
			$this->_primary_link = $this->_cache->get($cache_key);

			if ($this->_primary_link === NULL)
			{
				$this->_primary_link = DB::select('location')
					->from('page_links')
					->where('page_id', '=', $this->id)
					->where('is_primary', '=', TRUE)
					->limit(1)
					->execute()
					->get('location');

				$this->_cache->set($cache_key, $this->_primary_link);
			}
		}

		return $this->_primary_link;
	}


	/**
	 * Generate a short URI for the page, similar to t.co etc.
	 * Returns the page ID encoded to base-36 prefixed with an underscore.
	 * We prefix the short URIs to avoid the possibility of conflicts with real URIs
	 *
	 * @return 	string
	 */
	public function short_link()
	{
		return "_" . base_convert($this->id, 10, 36);
	}

	/**
	 * Sort the page's children as specified by the child ordering policy.
	 * Called when changing a page's child ordering policy.
	 *
	 * @return	Model_Page
	 */
	public function sort_children()
	{
		// Get the column and direction to order by.
		list($column, $direction) = $this->children_ordering_policy();

		// Find the children, sorting the database results by the column we want the children ordered by.
		$children = ORM::factory('Page_MPTT')
			->join('page', 'inner')
			->on('page_mptt.id', '=', 'page.id')
			->join('page_v', 'inner')
			->on('page.active_vid', '=', 'page_v.id')
			->where('parent_id', '=', $this->id)
			->order_by($column, $direction)
			->find_all();

		// Flag to show that loop is on it's first iteration.
		$first = TRUE;

		// Loop through the children assigning new left and right values.
		foreach ($children as $child)
		{
			if ($first)
			{
				// First iteration of the loop so make the page the first child.
				$child->move_to_first_child($this->id);
				$first = FALSE;
			}
			else
			{
				// For all the other children move to the end.
				$child->move_to_last_child($this->id);
			}
		}

		return $this;
	}

	/**
	 * Returns a thumbnail for the current page.
	 * The thumbnail is the first image in the bodycopy.
	 *
	 * This function:
	 * * SHOULD always return an instance of Model_Asset
	 * * SHOULD return an instance of Model_Asset where the type property = Sledge_Asset::IMAGE (i.e. not return an asset representing a video or pdf)
	 * * SHOULD NOT return an asset which is unpublished when the current user is not logged in.
	 * * For guest users SHOULD return the first image in the body copy which is published.
	 * * Where there is no image (or no published image) in the bodycopy SHOULD return an empty Model_Asset
	 *
	 * @todo 	Need to write tests for the above.
	 * @return 	Model_Asset
	 */
	public function thumbnail()
	{
		// Try and get it from the $_thumbnail property to prevent running the code multiple times
		if ($this->_thumbnail !== NULL)
		{
			return $this->_thumbnail;
		}

		// Try and get it from the cache.
		// We don't have to worry about updating this cache - when the bodycopy is changed a new page version is created anyway.
		// So once the thumbnail for a particular page version is cached the cache should never have to be changed.
		$cache_key = 'thumbnail_for_page_version:' . $this->id;

		if ( ! $asset_id = $this->_cache->get($cache_key))
		{
			// Get the standfirst for this page version.
			$chunk = Chunk::find('text', 'bodycopy', $this);

			if ( ! $chunk->loaded())
			{
				$asset_id = 0;
			}
			else
			{
				// Find the first image in this chunk.
				$query = DB::select('chunk_text_assets.asset_id')
					->from('chunk_text_assets')
					->join('assets', 'inner')
					->on('chunk_text_assets.asset_id', '=', 'assets.id')
					->order_by('position', 'asc')
					->limit(1)
					->where('chunk_text_assets.chunk_id', '=', $chunk->id)
					->where('assets.type', '=', Sledge_Asset::IMAGE);

				// If the current user isn't logged in then make sure it's a published asset.
				if ( ! Auth::instance()->logged_in())
				{
					$query->where('assets.visible_from', '<=', $_SERVER['REQUEST_TIME'])
						->where('status', '=', Model_Asset::STATUS_PUBLISHED);
				}

				// Run the query and get the result.
				$result = $query->execute()->as_array();
				$asset_id = (isset($result[0]))? $result[0]['asset_id'] : 0;
			}

			// Save it to cache.
			$this->_cache->set($cache_key, $asset_id);
		}

		// Return a Model_Asset object for this asset ID.
		return $this->_thumbnail = ORM::factory('Asset', $asset_id);
	}

	/**
	 * Gets the Model_Page_Version object for the current version of the page.
	 * Will be the most recent version if logged in or the most recent published version for site visitors.
	 *
	 * @return	Model_Page_Version
	 */
	public function version()
	{
		// TODO: return most recent published version in site mode, just return the active version for now.
		return ORM::factory('Page_Version', $this->active_vid);
	}
}