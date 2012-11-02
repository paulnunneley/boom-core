<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Slideshow chunk model
 *
 * @package	Sledge
 * @category	Chunks
 * @category	Models
 * @author	Rob Taylor
 * @copyright	Hoop Associates
 *
 */
class Sledge_Model_Chunk_Slideshow extends ORM
{
	/**
	* Properties to create relationships with Kohana's ORM
	*/
	protected $_primary_key = 'chunk_id';
	protected $_has_many = array(
		'slides' => array('model' => 'Chunk_Slideshow_Slide', 'foreign_key' => 'chunk_id'),
	);
	protected $_load_with = array('slides');
	protected $_table_columns = array(
		'title'		=>	'',
		'chunk_id'	=>	'',
	);
	
	private $_asset_ids = array();
	
	protected $_slides = NULL;

	/**
	* Return an array of asset ids which are used in this slideshow.
	*
	* @todo This needs optimizing so that the database query is only done once per request.
	*/
	public function get_asset_ids()
	{
		if (empty($this->_asset_ids))
		{
			foreach ($this->slides->find_all() as $slide)
			{
				$this->_asset_ids[] = $slide->asset_id;
			}
		}
		
		return $this->_asset_ids;
	}

	public function preview($template)
	{	
		$v = View::factory("site/slots/slideshow/$template");
		$v->chunk = $this;
		
		$v->title = $this->title;
		$v->slides = $this->slides();	
		
		return $v;
	}
	
	/**
	* Accepts and array of asset IDs and sets them as the slides.
	*
	*/
	public function set_asset_ids( array $assets)
	{
		$this->_asset_ids = $assets;
	}
	
	/**
	* Sets or gets the slideshows slides
	*
	*/
	public function slides($slides = NULL)
	{
		if ($slides === NULL)
		{
			if ($this->_slides === NULL)
			{
				$this->_slides = $this->slides->find_all();
			}
			
			return $this->_slides;
		}
		else
		{
			$this->_slides = $slides;
		}
	}

	public function save( Validation $validation = NULL)
	{
		$return = parent::save($validation);
		
		// Remove all existing slides.
		DB::query(Database::DELETE, "delete from chunk_slideshow_slides where chunk_id = " . $this->pk());
				
		foreach ($this->_asset_ids as $asset)
		{
			// Check the asset exists.
			$asset = ORM::factory('Asset', $asset);
			
			if ($asset->loaded())
			{
				ORM::factory('Chunk_slideshow_slide')
					->values( array(
						'chunk_id'	=>	$this->chunk_id,
						'asset_id'	=>	$asset->pk()
					))
					->create();
			}
		}
		
		return $return;
	}
}
