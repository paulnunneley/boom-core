<?php
/**
* Model for the feature chunk table.
*
* Table name: chunk_feature
*
*************************** Table Columns ************************
****	Name			****	Data Type	****	Description					
****	id				****	integer		****	Primary key, auto increment
****	target_page_id	****	integer		****	The page ID being featured.
****	audit_person	****	integer		****	ID of the person who edited the chunk.
****	audit_time		****	integer		****	Unix timestamp of when the chunk was edited.
****	deleted			****	boolean		****	Whether the chunk has been deleted.
******************************************************************
*
* @package Models
* @author Hoop Associates	www.thisishoop.com	mail@hoopassociates.co.uk
* @copyright 2011, Hoop Associates
*
*/
class Model_Chunk_Feature extends ORM implements Interface_Slot
{
	/**
	* Properties to create relationships with Kohana's ORM
	*/
	protected $_table_name = 'chunk_feature';
	protected $_primary_key = 'chunk_id';
	
	protected $_belongs_to = array(
		'page' => array( 'model' => 'page', 'foreign_key' => 'target_page_id' ),
	);
	
	public function show( $template = 'main' )
	{
		if ($this->loaded())
		{
			if (!$template)
			{
				$template = 'main';
			}
			
			$v = View::factory( "site/slots/slottype/feature/subtpl_$template" );
		
			$target = ORM::factory( 'page', $this->target_page_id );
			$v->url = $target->url();
			$v->title = $target->title;
			$v->text = $target->get_slot( 'text', 'standfirst' )->show();
			
			return $v;	
		}	
	}
	
	public function show_default()
	{
		$v = View::factory( 'site/slots/feature' );
		$v->url = '';
		$v->title = 'Default Feature';
		$v->text = 'Click on me to add a feature box here.';	
		return $v;
	}
		
	public function get_slotname()
	{
		return $this->chunk->slotname;
	}
	
	public function getTitle()
	{
		
		
		
	}	
}

?>
