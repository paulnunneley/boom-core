<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * @package	BoomCMS
 * @category	Chunks
 * @category	Models
 * @author	Rob Taylor
 *
 */
class Boom_Model_Chunk_Text extends ORM
{
	/**
	* Properties to create relationships with Kohana's ORM
	*/
	protected $_table_columns = array(
		'text'		=>	'',
		'id'		=>	'',
		'title'		=>	'',
		'slotname'	=>	'',
	);

	protected $_table_name = 'chunk_texts';

	/**
	 * Clean the text with HTML Purifier.
	 *
	 * @param string $text
	 * @return string
	 */
	public function clean_text($text)
	{
		if ($this->slotname == 'standfirst')
		{
			// For standfirsts remove all HTML tags.
			return strip_tags($text);
		}
		elseif ($this->slotname == 'bodycopy' OR $this->slotname == 'bodycopy2')
		{
			// For the bodycopy clean the HTML.
			require_once Kohana::find_file('vendor', 'htmlpurifier/library/HTMLPurifier.auto');

			// Get the HTML Purifier config from a config file.
			$config = HTMLPurifier_Config::createDefault();
			$config->loadArray(Kohana::$config->load('htmlpurifier'));

			// Create a purifier object.
			$purifier = new HTMLPurifier($config);

			// Return the cleaned text.
			return $purifier->purify($text);
		}
		else
		{
			// For everything else allow b, i , and a tags.
			return strip_tags($text, '<b><i><a>');
		}
	}

	/**
	 * When creating a text chunk log which assets are linked to from it.
	 *
	 * @param	Validation $validation
	 * @return 	Boom_Model_Chunk_Text
	 */
	public function create(Validation $validation = NULL)
	{
		// Clean the text.
		// This is done now rather than as a filter as the rules for what is allowed in the text varies with the slotname.
		// Using a filter we can't be sure that the slotname has been set before the text which could result in the wrong rules being applied.
		$this->_object['text'] = $this->clean_text($this->_object['text']);

		// Munge links in the text, e.g. to assets.
		 // This needs to be done after the text is cleaned by HTML Purifier because HTML purifier strips out invalid images.
		$this->_object['text'] = $this->munge($this->_object['text']);

		// Find which assets are linked to within the text chunk.
		preg_match_all('|hoopdb://image/(\d+)|', $this->_object['text'], $matches);

		// Create the text chunk.
		parent::create($validation);

		// Are there any asset links?
		if ( ! empty($matches[1]))
		{
			$assets = array_unique($matches[1]);

			// Log which assets are being referenced with a multi-value insert.
			$query = DB::insert('chunk_text_assets', array('chunk_id', 'asset_id', 'position'));

			foreach ($assets as $i => $asset_id)
			{
				$query->values(array($this->id, $asset_id, $i));
			}

			try
			{
				$query->execute();
			}
			catch (Database_Exception $e)
			{
				// Don't let database failures in logging prevent the chunk from being saved.
				Kohana_Exception::log($e);
			}
		}

		return $this;
	}

	/**
	 * @link http://kohanaframework.org/3.2/guide/orm/filters
	 */
	public function filters()
	{
		return array(
			'text' => array(
				array('urldecode'),
				array(
					function($text)
					{
						return str_replace('&nbsp;', ' ', $text);
					}
				),
			),
			'title'	=> array(
				array('strip_tags'),
				array(
					function($text)
					{
						return str_replace('&nbsp;', ' ', $text);
					}
				),
			)
		);
	}

	/**
	 * Munges text chunk contents to be saved in the database.
	 * e.g. Turns text links, such as <img src='/asset/view/324'> in hoopdb:// links
	 *
	 * @param 	string	$text		Text to munge
	 * @return 	string
	 *
	 */
	public function munge($text)
	{
		return preg_replace('|<(.*?)src=([\'"])/asset/view/(.*?)([\'"])(.*?)>|', '<$1src=$2hoopdb://image/$3$4$5>', $text);
	}

	/**
	 * Turns text chunk contents into HTML.
	 * e.g. replaces hoopdb:// links to <img> and <a> links
	 *
	 * @param	string	$text	Text to decode
	 * @return 	string
	 */
	public function unmunge($text)
	{
		// Image links in the form hoopdb://image/123
		$text = preg_replace('|hoopdb://image/(\d+)|', '/asset/view/$1/400', $text);

		// Fix internal page links.
		$text = preg_replace_callback('|hoopdb://page/(\d+)|',
			function ($match)
			{
				return ORM::factory('Page', $match[1])->url();
			},
			$text
		);

		return $text;
	}
}