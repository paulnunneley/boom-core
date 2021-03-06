<?php

namespace BoomCMS\Contracts\Models;

use BoomCMS\Database\Models\AssetVersion;
use DateTime;

interface Asset
{
    /**
     * @return string
     */
    public function directory();

    /**
     * @return bool
     */
    public function exists();

    /**
     * @return float
     */
    public function getAspectRatio();

    /**
     * @return string
     */
    public function getCredits();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return int
     */
    public function getDownloads();

    /**
     * @return string
     */
    public function getExtension();

    /**
     * @return string
     */
    public function getFilename();

    /**
     * Returns the filesize in bytes.
     *
     * @return int
     */
    public function getFilesize();

    /**
     * @return int
     */
    public function getHeight();

    /**
     * @return int
     */
    public function getId();

    public function getEmbedHtml($height = null, $width = null);

    /**
     * @return DateTime
     */
    public function getLastModified();

    public function getLatestVersion();

    /**
     * @return int
     */
    public function getLatestVersionId();

    /**
     * @return string
     */
    public function getMimetype();

    /**
     * @return string
     */
    public function getOriginalFilename();

    /**
     * @return Site
     */
    public function getSite();

    /**
     * @return array
     */
    public function getTags();

    /**
     * @return int
     */
    public function getThumbnailAssetId();

    public function getThumbnail();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getType();

    /**
     * Person.
     */
    public function getUploadedBy();

    /**
     * @return DateTime
     */
    public function getUploadedTime();

    /**
     * @return int
     */
    public function getWidth();

    /**
     * @return bool
     */
    public function hasThumbnail();

    /**
     * @return $this
     */
    public function incrementDownloads();

    /**
     * @return bool
     */
    public function isImage();

    /**
     * @param string $credits
     *
     * @return $this
     */
    public function setCredits($credits);

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description);

    /**
     * Set the site that the asset belongs to.
     *
     * @param Site $site
     *
     * @return $this
     */
    public function setSite(Site $site);

    /**
     * @param int $assetId
     *
     * @return $this
     */
    public function setThumbnailAssetId($assetId);

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title);

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type);

    /**
     * @param Person $person
     *
     * @return $this
     */
    public function setUploadedBy(Person $person);

    public function setUploadedTime(DateTime $time);

    /**
     * @param AssetVersion
     *
     * @return $this
     */
    public function setVersion(AssetVersion $version);
}
