<?php

namespace BoomCMS\Chunk;

use BoomCMS\Contracts\Models\Asset as AssetContract;
use BoomCMS\Link\Link;
use BoomCMS\Support\Facades\Asset as AssetFacade;
use Illuminate\Support\Facades\View;

class Asset extends BaseChunk
{
    /**
     * @var AssetContract
     */
    protected $asset;

    protected $defaultTemplate = 'image';

    private $filterByType;
    private $link;

    protected function show()
    {
        return View::make($this->viewPrefix."asset.$this->template", [
            'assetId' => $this->getAssetId(),
            'caption' => $this->getCaption(),
            'title'   => $this->getTitle(),
            'link'    => $this->getLink(),
            'asset'   => function () {
                return $this->getAsset();
            },
        ])->render();
    }

    public function attributes()
    {
        return [
            $this->attributePrefix.'target'       => $this->target(),
            $this->attributePrefix.'filterByType' => $this->filterByType,
        ];
    }

    /**
     * Returns the associated asset.
     *
     * @return AssetContract
     */
    public function getAsset()
    {
        if ($this->asset === null) {
            $this->asset = AssetFacade::find($this->getAssetId());
        }

        return $this->asset;
    }

    /**
     * Returns the associated asset ID.
     *
     * @return int
     */
    public function getAssetId()
    {
        return isset($this->attrs['asset_id']) ? $this->attrs['asset_id'] : 0;
    }

    public function getCaption()
    {
        return isset($this->attrs['caption']) ? $this->attrs['caption'] : '';
    }

    public function getLink()
    {
        if ($this->link === null && isset($this->attrs['url'])) {
            $this->link = Link::factory($this->attrs['url']);
        }

        return $this->link;
    }

    public function getTitle()
    {
        return isset($this->attrs['title']) ? $this->attrs['title'] : '';
    }

    /**
     * Whether the chunk has content.
     *
     * @return bool
     */
    public function hasContent()
    {
        return $this->getAssetId() != 0;
    }

    /**
     * Set which type of asset can be put into the chunk.
     * No validation is done but by default the asset picker will filter by this type.
     *
     * @param string $type An asset type. e.g. 'pdf', 'image'.
     */
    public function setFilterByType($type)
    {
        $this->filterByType = $type;

        return $this;
    }

    public function target()
    {
        return $this->hasContent() ? $this->getAssetId() : 0;
    }
}
