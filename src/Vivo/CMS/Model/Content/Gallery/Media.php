<?php
namespace Vivo\CMS\Model\Content\Gallery;

use Vivo\CMS\Model\Content;
use Vivo\Stdlib\OrderableInterface;

class Media extends Content\File implements OrderableInterface
{
    /**
     * @var string Media name.
     */
    protected $name;

    /**
     * @var string Media description.
     */
    protected $description;

    /**
     * @var int Size.
     */
    protected $originalWidth;

    /**
     * @var int Size.
     */
    protected $originalHeight;

    /**
     * @var int Order.
     */
    protected $order = 0;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Image size.
     *
     * @param int $width
     */
    public function setOriginalWidth($width)
    {
        $this->originalWidth = intval($width);
    }

    /**
     * Image size.
     *
     * @return int
     */
    public function getOriginalWidth()
    {
        return $this->originalWidth;
    }

    /**
     * Image size.
     *
     * @param int $height
     */
    public function setOriginalHeight($height)
    {
        $this->originalHeight = intval($height);
    }

    /**
     * Image size.
     *
     * @return int
     */
    public function getOriginalHeight()
    {
        return $this->originalHeight;
    }

    /**
     * @return bool
     */
    public function isLandscape()
    {
        return $this->originalWidth > $this->originalHeight;
    }

    public function setOrder($order)
    {
        $this->order = intval($order);
    }

    public function getOrder()
    {
        return $this->order;
    }
}
