<?php
namespace Vivo\CMS\RefInt;

use Vivo\CMS\Api\CMS as CmsApi;
use Vivo\CMS\Model\Site;
use Vivo\Repository\Exception\EntityNotFoundException;
use Vivo\CMS\UuidConvertor\UuidConvertorInterface;

/**
 * Class SymRefConvertor
 * Converts URLs to symbolic references and vice versa
 * @package Vivo\CMS\RefInt
 */
class SymRefConvertor implements SymRefConvertorInterface
{
    /**
     * CMS Api
     * @var \Vivo\CMS\Api\CMS
     */
    protected $cmsApi;

    /**
     * Uuid convertor
     * @var \Vivo\CMS\UuidConvertor\UuidConvertorInterface
     */
    protected $uuidConvertor;

    /**
     * Current site
     * @var \Vivo\CMS\Model\Site
     */
    protected $site;

    /**
     * Constructor
     * @param \Vivo\CMS\Api\CMS $cmsApi
     * @param \Vivo\CMS\UuidConvertor\UuidConvertorInterface $uuidConvertor
     * @param \Vivo\CMS\Model\Site $site
     */
    public function __construct(CmsApi $cmsApi, UuidConvertorInterface $uuidConvertor, Site $site)
    {
        $this->cmsApi           = $cmsApi;
        $this->uuidConvertor    = $uuidConvertor;
        $this->site             = $site;
    }

    /**
     * Converts URLs to symbolic references
     * @param string|array|object $value
     * @return string|array|object The same object / value.
     */
    public function convertUrlsToReferences($value)
    {
        if (is_string($value)) {
            //String
            $value = preg_replace_callback('/(\.|)('.self::PATTERN_URL.')/', array($this, 'replaceUrl'), $value);
        } elseif (is_array($value)) {
            //Array
            foreach ($value as $key => $val) {
                $value[$key] = $this->convertUrlsToReferences($val);
            }
        } elseif (is_object($value)) {
            //Object
            $ref = new \ReflectionObject($value);
            foreach ($ref->getProperties() as $prop) {
                $prop->setAccessible(true);
                $prop->setValue($value, $this->convertUrlsToReferences($prop->getValue($value)));
            }
        }
        return $value;
    }

    /**
     * Converts symbolic references to URLs
     * @param string|array|object $value
     * @return string|array|object $value The same object / value
     */
    public function convertReferencesToURLs($value)
    {
        if (is_string($value)) {
            //String
            $value = preg_replace_callback('/\[ref:('.self::PATTERN_UUID.')\]/i', array($this, 'replaceUuid'), $value);
        } elseif (is_array($value)) {
            //Array
            foreach ($value as $key => $val) {
                $value[$key] = $this->convertReferencesToURLs($val);
            }
        } elseif (is_object($value)) {
            //Object
            $ref = new \ReflectionObject($value);
            foreach ($ref->getProperties() as $prop) {
                $prop->setAccessible(true);
                $prop->setValue($value, $this->convertReferencesToURLs($prop->getValue($value)));
            }
        }
        return $value;
    }

    /**
     * Callback used from convertUrlsToReferences()
     * @param array $matches
     * @return string
     */
    protected function replaceUrl(array $matches)
    {
        $url    = $matches[2];
        try {
            /** @var $doc \Vivo\CMS\Model\Entity */
            $doc    = $this->cmsApi->getSiteEntity($url, $this->site);
            $symRef = sprintf('[ref:%s]', $doc->getUuid());
        } catch (EntityNotFoundException $e) {
            $symRef = $url;
        }
        return $symRef;
    }

    /**
     * Callback used from convertReferencesToUrls()
     * @param array $matches
     * @throws \Exception
     * @return string
     */
    protected function replaceUuid(array $matches)
    {
        $uuid   = strtoupper($matches[1]);
        switch ($uuid) {
            case 'self':
                //TODO - Implement the 'self' branch
                throw new \Exception(sprintf('%s: The self branch not implemented!', __METHOD__));
                break;
            default:
                $path   = $this->uuidConvertor->getPath($uuid);
                if(is_null($path)) {
                    //UUID not found
                    $path   = '[invalid-ref:' . $uuid . ']';
                } else {
                    //UUID found
                    $path   = $this->cmsApi->getEntityRelPath($path);
                }
                break;
        }
        return $path;
    }
}
