<?php
namespace Vivo\ZendSearch\Lucene\Storage\File;

use Vivo\Storage\StorageInterface;
use ZendSearch\Lucene;

/**
 * StorageHandle
 * Lucene file implemented over Vivo Storage
 */
class StorageHandle extends AbstractFile
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Path to a file in storage
     * @var string
     */
    protected $path;

    /**
     * File pointer position
     * @var int
     */
    protected $position = 0;

    /**
     * Cached file data
     * @var string
     */
    protected $data;

    /**
     * Constructor
     * @param \Vivo\Storage\StorageInterface $storage
     * @param string $path
     */
    public function __construct(StorageInterface $storage, $path)
    {
        $this->storage  = $storage;
        $this->path     = $path;
        $this->position = 0;
    }

    /**
     * Returns size of the file
     * @return int
     */
    public function size()
    {
        return $this->storage->size($this->path);
    }

    /**
     * Sets the file position indicator and advances the file pointer.
     * The new position, measured in bytes from the beginning of the file,
     * is obtained by adding offset to the position specified by whence,
     * whose values are defined as follows:
     * SEEK_SET - Set position equal to offset bytes.
     * SEEK_CUR - Set position to current location plus offset.
     * SEEK_END - Set position to end-of-file plus offset. (To move to
     * a position before the end-of-file, you need to pass a negative value
     * in offset.)
     * SEEK_CUR is the only supported offset type for compound files
     * Upon success, returns 0; otherwise, returns -1
     * @param int $offset
     * @param int $whence
     * @return integer 0 on success, -1 on failure
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                $retval = true;
                break;

            case SEEK_CUR:
                $this->position += $offset;
                $retval = true;
                break;

            case SEEK_END:
                $this->position = $this->storage->size($this->path);
                $this->position += $offset;
                $retval = true;
                break;

            default:
                $retval = false;
                break;
        }
        return $retval;
    }

    /**
     * Get file position
     * @return integer|boolean Returns false on error
     */
    public function tell()
    {
        return $this->position;
    }

    /**
     * Flush output
     * Returns true on success or false on failure.
     * @return boolean True on success
     */
    public function flush()
    {
        //No flushing necessary
        return true;
    }

    /**
     * Close File object
     * @return boolean True on success
     */
    public function close()
    {
        //No closing necessary
        return true;
    }

    /**
     * Read a $length bytes from the file and advance the file pointer.
     * @param integer $length
     * @return string
     */
    protected function _fread($length = 1)
    {
        if ($length == 0) {
            return '';
        }
        if ($length < 1024) {
            return $this->doRead($length);
        }
        $data = '';
        while ($length > 0 && ($nextBlock = $this->doRead($length)) != false) {
            $data .= $nextBlock;
            $length -= strlen($nextBlock);
        }
        return $data;
    }

    /**
     * Performs actual reading
     * @param int $length
     * @return string|boolean False on error
     */
    protected function doRead($length = 1)
    {
        if (!$this->data) {
            $this->data = $this->storage->get($this->path);
        }
        $chunk  = substr($this->data, $this->position, $length);
        $this->position += strlen($chunk);
        return $chunk;
    }

    /**
     * Writes $length number of bytes (all, if $length===null) to the end of the file.
     * @param string $data
     * @param integer $length
     * @return int|null
     */
    protected function _fwrite($data, $length = null)
    {
        if (!$this->data) {
            $this->data = $this->storage->get($this->path);
        }
        $dataLength = strlen($data);
        if (!is_null($length) && ($length > $dataLength)) {
            $length = $dataLength;
        }
        if ($length) {
            $this->data     = substr_replace($this->data, $data, $this->position, $length);
            $written        = $length;
            $this->position += $written;
        } else {
            $this->data     = substr_replace($this->data, $data, $this->position);
            $written        = $dataLength;
            $this->position += $written;
        }
        $this->storage->set($this->path, $this->data);
        return $written;
    }

    /**
     * Lock file
     *
     * Lock type may be a LOCK_SH (shared lock) or a LOCK_EX (exclusive lock)
     *
     * @param integer $lockType
     * @param boolean $nonBlockingLock
     * @return boolean
     */
    public function lock($lockType, $nonBlockingLock = false)
    {
        //TODO - Locking not supported
        return true;

//        if ($nonBlockingLock) {
//            return flock($this->fileHandle, $lockType | LOCK_NB);
//        } else {
//            return flock($this->fileHandle, $lockType);
//        }
    }

    /**
     * Unlock file
     *
     * Returns true on success
     *
     * @return boolean
     */
    public function unlock()
    {
        //TODO - Locking not supported
        return true;

//        if ($this->fileHandle !== null ) {
//            return flock($this->fileHandle, LOCK_UN);
//        } else {
//            return true;
//        }
    }
}
