<?php

/**
*
* @package Models
* @author Hoop Associates	www.thisishoop.com	mail@hoopassociates.co.uk
* @copyright 2011, Hoop Associates
* @todo Work out which methods we actually need from hoopbasepagemodel and implement them nicely. Then just extend ORM 
*
*/
class Model_Page extends ORM_Versioned {
	/**
	* Properties to create relationships with Kohana's ORM
	*/
	protected $_table_name = 'page';
	protected $_has_one = array( 
		'mptt'		=> array( 'model' => 'page_mptt' )
	);
	protected $_has_many = array( 
		'versions'	=> array('model' => 'version_page', 'foreign_key' => 'id'),
		'uris'		=> array('model' => 'page_uri', 'foreign_key' => 'id'),
		'chunks'	=> array('model' => 'chunk', 'through' => 'chunk_page' )
	);
	protected $_belongs_to = array( 'version_page_uri' => array( 'foreign_key' => 'page_id' ) );
	
	/**
	* Page invisible value (stored in page_status column)
	* @var integer
	*/
	const STATUS_INVISIBLE = 1;
	
	/**
	* Page visible value (stored in page_status column)
	* @var integer
	*/
	const STATUS_VISIBLE = 2;
	
	/**
	* Page version status value for a draft version
	* @var integer
	*/
	const STATUS_DRAFT = 1;
	
	/**
	* Page version status value for a version awaiting approval
	* @var integer
	*/
	const STATUS_AWAITING_APPROVAL = 2;
	
	/**
	* Page version status value for an approved version
	* @var integer
	*/
	const STATUS_APPROVED = 3;
	
	/**
	* Page version status value for a published version
	* @var integer
	*/
	const STATUS_PUBLISHED = 4;	
	
	/**
	* Child ordering policy value for manual
	* @var integer
	*/
	const CHILD_ORDER_MANUAL = 1;
	
	/**
	* Child ordering policy value for alphabetic
	* @var integer
	*/
	const CHILD_ORDER_ALPHABETIC = 2;
	
	/**
	* Child ordering policy value for date
	* @var integer
	*/
	const CHILD_ORDER_DATE = 3;
	
	/**
	* @access private
	* @var boolean
	* By default do not allow a page to be saved - overridden by cms_page which is the only time we want to allow saving of pages.
	*/
	private $_can_be_saved = false;
	
	/**
	* @access private
	* @var object
	* Holds the first version of the page. Useful for finding the creation time / person.
	*/
	private $_firstVersion;
	
	/**
	* @access private
	* @var string
	*/
	private $_absolute_uri;
	
	/**
	* Do we need to save the children?
	* Flag indicating whether we've made changes to child pages. Checked by save()
	*
	* @access private
	* @var boolean
	*/
	private $_save_children = false;
	
	/**
	* Holds the calculated primary URI
	*
	* @access private
	* @var string
	*/
	private $_primary_uri;
	
	/**
	* Holds an array of the slots embedded in the page.
	* Ensures we only query the database once for each slot - and not everytime we want to use the slot.
	* Used by the get_slot() method
	*
	* @access private
	* @var array
	*/
	private $_slots = array();
	
	/**
	* Returns a human readable page status.
	*
	* @return string Page status - currently visible or invisible.
	*/
	public function getPageStatus() {
		switch( $this->page_status ) {
			case self::STATUS_VISIBLE:
				return 'Visible';
				break;
			case self::STATUS_INVISIBLE:
				return 'Invisible';
				break;
			default:
				throw new Kohana_Exception( 'Page has unknown page status value: ' . $this->page_status );
		}		
	}
	
	/**
	* Return a human readable representation of the version status.
	*
	* @return string Version status
	*/
	public function getVersionStatus() {
		switch( $this->version_status ) {
			case self::STATUS_DRAFT:
				return 'Draft';
				break;
			case self::STATUS_AWAITING_APPROVAL:
				return 'Awaiting Approval';
				break;
			case self::STATUS_APPROVED:
				return 'Approved';
				break;
			case self::STATUS_PUBLISHED:
				return 'Published';
				break;
			default:
				return null;
		}	
	}
	
	/**
	* Return a human readable representation of the child ordering policy.
	*
	* @return string Child ordering policy
	*/
	public function getChildOrderingPolicy() {
		switch( $this->child_ordering_policy ) {
			case self::CHILD_ORDER_MANUAL:
				return 'Manual';
				break;
			case self::CHILD_ORDER_ALPHABETIC:
				return 'Alphabetic';
				break;
			case self::CHILD_ORDER_DATE:
				return 'Date';
				break;
			default:
				throw new Kohana_Exception( "Page version has unknown child ordering policy: " . $this->child_ordering_policy );
		}	
	}
	
	/**
	* Checks that the page is visible.
	* @return boolean true if it's visible, false if it isn't
	*/
	public function isVisible() {
		if ($this->page_status == self::STATUS_VISIBLE && $this->visiblefrom_timestamp <= time() && ($this->visibleto_timestamp >= time() || $this->visibleto_timestamp === null))
			return true;
		else
			return false;		
	}
	
	/**
	* Checks that a page is published.
	* @return boolean true if it's published, false if it isn't.
	*/
	public function isPublished() {
		if ($this->version_status === self::STATUS_PUBLISHED)
			return true;
		else
			return false;		
	}
	
	/**
	* Determine whether there is an RSS feed for the page.
	*
	* @return boolean true is there is, false if there isn't
	* @todo Work out how we're actually going to do this in Sledge 3.
	*/
	public function hasRss() {
		return false;
	}
	
    /**
    * Gets an object referring to the first version of the page - used for getting the creation time / author.
    * @return page_v_Model page_v_model object for first version of the page.
    */
	public function getFirstVersion()
	{
		if ($this->_firstVersion === null)
		{
			$this->_firstVersion = ORM::factory( 'version_page')->order_by('audit_time', 'asc')->where( 'rid', '=', $this->id )->limit( 1 )->find(); 
		}         
		
		return $this->_firstVersion;
	}
	
	/**
	* Returns the related mptt_Model object
	* @return object
	*/
	public function getMptt() {
		return $this->mptt;
	}
	
	/**
	* Delete a page.
	* Ensures child pages are deleted and that the pages are deleted from the MPTT tree.
	*
	* @return ORM
	*/
	public function delete()
	{
		foreach( $this->mptt->children() as $p )
		{
			$p->page->delete();
		}
		
		$this->mptt->delete();
		return parent::delete();
	}
	
	/**
	* Returns the page's absolute URI.
	* This method uses Kohana's URL::base() method to generate the base URL from the current request (protocol, hostnane etc.) {@link http://kohanaframework.org/3.2/guide/api/URL#base}
	* @uses URL::base()
	* @uses Request::Instance()
	* @todo Direct copy and past from MY_Uri library. Needs to be made properly.
	* @return string The absolute URI.
	*/
	public function url() {
		// Get the base URL of the current request.
		$url = URL::base();
		
		$url .= $this->getPrimaryUri();
		
		return $url;		
	}
	
	/**
	* Get the page's primary URI
	* From the page's available URIs finds the one which is marked as the primary URI.
	* @return string The RELATIVE primary URI of the page.
	*
	*/
	public function getPrimaryUri() {
		if ($this->_primary_uri === null)
		{
			$uri = DB::select( 'uri' )
			->from( 'page_uri' )
			->where( 'page_id', '=', $this->id )
			->and_where( 'primary_uri', '=', true )
			->execute();
			
			$this->_primary_uri = $uri->get( 'uri' );
		}
			
		return $this->_primary_uri;		
	}
	
	/**
	* Retrieves a slot belonging to the page, identified by a slotname.
	*
	* @param string $type The type of slot to show.
	* @param string $slotname The name of the slot
	* @param boolean $editable Whether to allow the slot to be editable.
	*
	* @uses slot::factory()
	* @return string The HTML representation of the slot
	*/
	public function get_slot( $type, $slotname, $editable = null)
	{
		if (!array_key_exists( $slotname, $this->_slots ))
		{
			$this->_slots[ $slotname ] = ORM::factory( "chunk_$type" )
											->with( "chunk" )
											->on( 'chunk.active_vid', '=', "chunk_$type" . ".id" )
											->join( 'chunk_page' )
											->on( 'chunk_page.chunk_id', '=', 'chunk.id' )
											->where( 'chunk_page.page_id', '=', $this->id )	
											->or_where( 'chunk_page.page_id', '=', 0 )											
											->where( 'slotname', '=', $slotname )
											->find();
		}
		
		return $this->_slots[ $slotname ];	
	}
	
	/**
	* Get all the slots associated with the page.
	*
	* @return array Array of slots
	*/
	public function slots()
	{
		$slots = array();
		
		foreach (array( 'text', 'feature', 'linkset' ) as $type)
		{
			$more = ORM::factory( "chunk_$type" )
				->with( "chunk" )
				->on( 'chunk.active_vid', '=', "chunk_$type" . ".id" )
				->join( 'chunk_page' )
				->on( 'chunk_page.chunk_id', '=', 'chunk.id' )
				->where( 'chunk_page.page_id', '=', $this->id )	
				->or_where( 'chunk_page.page_id', '=', 0 )	
				->find_all()
				->as_array();
				
			$slots = array_merge( $slots, $more );
		}
		
		return $slots;
	}
	
	/**
	* Set the page's status.
	* Used to set whether the page is visible or invisible. 
	* If a page is being set to invisible then all child pages have their setPageStatus() methods called to ensure that they are also made invisible.
	*
	* @param integer $status Page status value.
	* @return void
	*/
	public function setStatus( $status )
	{
		$this->page_status = $status;
		
		// Are we setting the page to invisble? Hide the children as well, if they're are any.
		if ($status === self::STATUS_INVISIBLE && $this->mptt->countChildren() > 0)
		{	
			foreach ($this->getChildren() as $child)
			{
				$child->setStatus( $status );
			}	
	
			// Set a flag for self::save() to indicate that we've got child pages which need to be saved.
			$this->_save_children = true;		
		}	
	}
	
	/**
	* Generates a unique URI for the page based on the title.
	*
	* @return string The new peimary URI
	*/
	public function generateUri()
	{
		$parent = $this->mptt->parent()->page;
	
		if ($parent->default_child_uri_prefix)
			$prefix = $parent->default_child_uri_prefix . '/';
		else
			$prefix = $parent->getPrimaryUri() . '/';

		$append = 0;
		$start_uri = $prefix . URL::title( strtolower( $this->title ) );
	
		// If we're adding a page to the root node the URL ends up starting with a '/', which we don't want.
		// So check for and remove a '/' from the start of the URI.
		if ($start_uri[0] == '/')
			$start_uri = substr( $start_uri, 1 );
		
		// Get a unique URI.
		// This should be done as part of one of the models - for instance when we set the title property of the page.
		// For simplicity of getting adding a page working to some extent it's here for now, it can be moved later.
		do {
			$uri = ($append > 0)? $start_uri. $append : $start_uri;
			$append++;
		
			$exists = (int) DB::select( 'page_uri.id' )
						->from( 'page_uri' )
						->where( 'uri', '=', $uri )
						->limit( 1 )
						->execute()
						->get( 'id' );
		} while ($exists !== 0);
	
		// Create a URI for the page.
		$page_uri = ORM::factory( 'page_uri' );
		$page_uri->uri = $uri;
		$page_uri->page_id = $this->id;
		$page_uri->primary_uri = true;
		$page_uri->save();	
		
		$this->_primary_uri = $uri;	
		return $uri;
	}
}


?>
