<?php

namespace BoomCMS\Support\Traits;

use BoomCMS\Contracts\Models\Site;
use BoomCMS\Database\Models\Site as SiteModel;
use Illuminate\Database\Eloquent\Builder;

trait SingleSite
{
    /**
     * @var Site
     */
    protected $site;

    public function getSite()
    {
        if ($this->site === null) {
            $this->site = $this->belongsTo(SiteModel::class, 'site_id')->first();
        }

        return $this->site;
    }

    public function scopeWhereSiteIs(Builder $query, $site)
    {
        return $query->where('site_id', '=', $site->getId());
    }

    /**
     * @param Site $site
     *
     * @return $this
     */
    public function setSite(Site $site)
    {
        $this->site_id = $site->getId();

        return $this;
    }
}
