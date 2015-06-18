<?php

namespace BoomCMS\Core\Chunk;

use \Kohana as Kohana;

class Timestamp extends BaseChunk
{
    public static $default_format = 'j F Y';
    public static $formats = [
        'j F Y',
        'j F Y H:i',
        'j F Y h:i A',
        'l j F Y',
        'l j F Y H:i',
        'l j F Y h:i A',
        'H:i',
        'h:i A',
    ];

    protected $_html_before = "<span class='b-chunk-timestamp'>";
    protected $_html_after = "</span>";
    protected $type = 'timestamp';

    public function attributes()
    {
        return [
            $this->attributePrefix.'timestamp' => $this->_chunk->timestamp,
            $this->attributePrefix.'format' => $this->_chunk->format,
        ];
    }

    protected function show()
    {
        return $this->_html_before.date($this->_chunk->format, $this->_chunk->timestamp).$this->_html_after;
    }

    protected function showDefault()
    {
        return $this->_html_before . $this->getPlaceholderText() . $this->_html_after;
    }

    public function hasContent()
    {
        return $this->_chunk->timestamp > 0;
    }

    public function timestamp()
    {
        return $this->_chunk->timestamp;
    }
}