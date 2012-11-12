<?php
namespace Vivo\ZendSearch\Lucene\Storage\File;

use Vivo\IO\InOutStreamInterface;
use Vivo\IO\CloseableInterface;

use ZendSearch\Lucene\Storage\File\AbstractFile as AbstractLuceneFile;
use ZendSearch\Lucene\Exception;

/**
 * VivoStorage
 * File implementation over Vivo Storage for ZendSearch\Lucene
 */
class VivoStorage extends AbstractLuceneFile
{
    /**
     * @var InOutStreamInterface
     */
    protected $stream;

    /**
     * Constructor
     * @param \Vivo\IO\InOutStreamInterface $stream
     */
    public function __construct(InOutStreamInterface $stream)
    {
        $this->stream   = $stream;
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
     * Upon success, returns 0; otherwise, returns -1
     *
     * @param integer $offset
     * @param integer $whence
     * @return integer
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->stream->seek($offset, $whence);
    }

    /**
     * Get file position.
     * @return integer
     */
    public function tell()
    {
        return $this->stream->tell();
    }

    /**
     * Flush output.
     * Returns true on success or false on failure.
     * @return boolean
     */
    public function flush()
    {
        return $this->stream->streamFlush();
    }

    /**
     * Lock file
     * Lock type may be a LOCK_SH (shared lock) or a LOCK_EX (exclusive lock)
     * @param integer $lockType
     * @param bool $nonBlockinLock
     * @return boolean
     */
    public function lock($lockType, $nonBlockinLock = false)
    {
        //Locking not supported
        return true;
    }

    /**
     * Unlock file
     */
    public function unlock()
    {
        //Locking not supported
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
        $data   = $this->stream->read($length);
        return $data;
    }

    /**
     * Writes $length number of bytes (all, if $length===null) to the end of the file.
     * @param string $data
     * @param integer|null $length
     * @return void
     */
    protected function _fwrite($data, $length = null)
    {
        if ($length === null ) {
            $this->stream->write($data);
        } else {
            $this->stream->write($data, $length);
        }
    }

    /**
     * Close File object
     */
    public function close()
    {
        if ($this->stream !== null ) {
            if ($this->stream instanceof CloseableInterface) {
                $this->stream->close();
            }
            $this->stream = null;
        }
    }
}