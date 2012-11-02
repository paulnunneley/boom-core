<?php defined('SYSPATH') OR die('No direct script access.');
/**
* PDF decorator for assets.
*
* @package Sledge
* @category Assets
* @author Hoop Associates	www.thisishoop.com	mail@hoopassociates.co.uk
* @copyright 2011, Hoop Associates
*
*/
class Sledge_Asset_Word extends Sledge_Asset
{
	public function show(Response $response)
	{
		$response->headers('Content-type', File::mime(ASSETPATH . $this->_asset->id));
		$response->headers('Content-Disposition', 'inline; filename="' . $this->_asset->filename . '"');
		$response->headers('Content-Transfer-Encoding', 'binary');
		$response->headers('Content-Length', $this->_asset->filesize);
		$response->headers('Accept-Ranges', 'bytes');

		$response->body(readfile(ASSETPATH . $this->_asset->id));
	}

	public function preview(Response $response)
	{
		$image = Image::factory(MODPATH . 'sledge/static/cms/img/icons/ms_word.jpg');
		$image->resize( 40, 40);

		$response->headers('Content-type', 'image/jpg');
		$response->body($image->render());
	}
}