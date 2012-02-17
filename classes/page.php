<?php defined('SYSPATH') or die('No direct script access.');

/**
* Page class.
* Provides command methods to the page interfaces.
* @package Page
* @author Hoop Associates	www.thisishoop.com	mail@hoopassociates.co.uk
* @copyright 2011, Hoop Associates
*/
abstract class Page 
{
	/**
	* Holds the page which we're interfacing
	*
	* @access private
	* @var object
	*/
	protected $_page;
	
	/**
	* Page constructor
	* Gets the page from the database and stores it in the $_page property.
	*
	* @param int $page_id
	* @return void
	*/
	public function __construct( $page )
	{
		if (is_object( $page ))
			$this->_page = $page;
		else
			$this->_page = ORM::factory( 'page', $page_id );
	}
	
	/**
	* Factory method for retrieving a page
	* 
	* @param $type string The type of page. Can be cms or site
	* @param $page_id int The ID of the page
	* @return Page The page object
	*/
	public static function factory( $type, $page_id )
	{
		switch( $type )
		{
			case 'cms':
				return new Page_Cms( $page_id );
			default:
				return new Page_Site( $page_id );
		}		
	}
	
	public function __get( $property )
	{
		return $this->_page->$property;
	}
	
	public function __set( $property, $value )
	{
		return $page->_page->$property = $value;
	}
	
	public function __isset( $property )
	{
		return isset( $this->_page, $property );
	}
	
	/**
	* Pass any method calls not handled by this class to the page object.
	*
	* @param string $method Method name
	* @param array $args array of arguments
	*/
	public function __call( $method, $args )
	{
		try
		{
			$method = new ReflectionMethod( get_class( $this->_page ), $method );
			return $method->invokeArgs( $this->_page, $args );
		}
		catch (ReflectionException $c)
		{
			$method = new ReflectionMethod( get_class( $this->_page->version ), $method );
			return $method->invokeArgs( $this->_page->version, $args );
		}
	}	
	
	public function __toString()
	{
		return $this->_page->__toString();
	}
	
	/**
	* Find which pages to display in this page's lefnav
	*
	* @uses Model_Person::logged_in()
	* @return ORM_Iterator Pages to display in leftnav
	*/
	public function leftnav_pages()
	{	
		$query = DB::SELECT( 'page.id', 'page_uri.uri', 'v.title', 'page_mptt.*' )
					->from( 'page' )
					->join( 'page_mptt' )
					->on( 'page_mptt.page_id', '=', 'page.id' )
					->join( 'page_uri', 'inner' )
					->on( 'page_uri.page_id', '=', 'page.id' )
					->where( 'scope', '=', $this->_page->mptt->scope )
					->where( 'v.deleted', '=', false );	
					
					
		// CMS or Site leftnav?
		if (!Auth::instance()->logged_in())
		{
			$query->join( array( 'page_v', 'v'), 'inner' )
				  ->on( 'page.published_vid', '=', 'v.id' )
				  ->where( 'v.visible_from', '<=', time() )
				  ->and_where_open()
				  ->or_where_open()
				  ->where( 'v.visible_to', '>=', time() )
				  ->or_where( 'v.visible_to', '=', 0 )
				  ->or_where_close()
				  ->and_where_close()
				  ->where( 'v.visible_in_leftnav', '=', true )
				  ->where( 'page.published_vid', '!=', null )
				  ->where( 'page.visible', '=', true );	
		}
		else
		{	
			$query->join( array( 'page_v', 'v'), 'inner' )
				  ->on( 'page.active_vid', '=', 'v.id' )
				  ->where( 'v.visible_in_leftnav_cms', '=', true );
		}
		
		$query->order_by( 'page_mptt.lft', 'asc' );
		
		$pages = $query->execute()->as_array();
					
		return $pages;
	}
}

?>
