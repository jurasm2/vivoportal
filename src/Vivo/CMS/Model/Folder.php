<?php
namespace Vivo\CMS\Model;

use Vivo\CMS\Security;

/**
 * Represents folder in tree.
 */
class Folder extends Entity implements SymRefDataExchangeInterface
{
    /**
     * @var string Folder name.
     */
    protected $title;

    /**
     * @var string Language.
     */
    protected $language;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var bool
     */
    protected $allowListing;

    /**
     * @var int Position of the document in layer. This property could be used as sorting option of the document.
     */
    protected $position;

    /**
     * @var string Specifies which criteria will classify sub-documents in the lists (newsletters, sitemap, menu, etc.)
     */
    protected $sorting;

    /**
     * @var string Replication id;
     */
    protected $replicationGroupId;

    /**
     * Absolute last path to entity stored in repository before move to trash.
     * @var string
     */
    protected $lastPath;

    /**
     * @var \Vivo\CMS\Model\Entity\Security
     */
    protected $security;

    /**
     * @param string $path Folder (entity) path in CMS repository.
     * @param \Vivo\CMS\Model\Entity\Security $security
     * @todo default security model
     */
    public function __construct($path = null, $security = null)
    {
        parent::__construct($path);

        $this->security = $security;

//         $security ?
//                 :
//                 //(self::$DEFAULT_SECURITY ? :
//                     new CMS\Model\Entity\Security(
//                         array( /*Security\Manager::ROLE_VISITOR			=> array(Security\Manager::GROUP_ANYONE),
//                                Security\Manager::ROLE_PUBLISHER		=> array(Security\Manager::GROUP_PUBLISHERS),
//                                Security\Manager::ROLE_ADMINISTRATOR	=> array(Security\Manager::GROUP_ADMINISTRATORS)
//                                 */
//                         ));
//         // 		);

    }

    /**
     * Sets folder title.
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns title.
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function setLanguage($language) {
        $this->language = $language;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getAllowListing() {
        return $this->allowListing;
    }

    public function getPosition() {
        return $this->position;
    }

    public function getSorting() {
        return $this->sorting;
    }

    public function setSorting($sorting) {
        $this->sorting = $sorting;
    }

    public function getSecurity() {
        return $this->security;
    }

    public function setSecurity(Security $security) {
        $this->security = $security;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @param bool allowListing
     */
    public function setAllowListing($allowListing = true)
    {
        $this->allowListing = (bool)$allowListing;
    }

    /**
     * @param array $field_names
     * @return string
     */
    public function getTextContent($field_names = array())
    {
        return parent::getTextContent(
                array_merge($field_names, array('description')));
    }

    /**
     * Exchange internal values containing symbolic refs / URLs from provided array
     * @param  array $data
     * @return void
     */
    public function exchangeArraySymRef(array $data)
    {
        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
    }

    /**
     * Return an array representation of the object's properties containing symbolic refs / URLs
     * @return array
     */
    public function getArrayCopySymRef()
    {
        $data                   = array();
        $data['description']    = $this->getDescription();
        return $data;
    }
}
