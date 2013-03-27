<?php
namespace Vivo\IO;

/**
 *
 */
class IOUtil
{

    /**
     * Copies data from source stream to target stream.
     * @param InputStreamInterface $source
     * @param OutputStreamInterface $target
     * @param integer $bufferSize
     * @return Ambigous <boolean, integer>
     */
    public function copy(InputStreamInterface $source,
        OutputStreamInterface $target, $bufferSize = 32768)
    {
        $copied = 0;
        while (($block = $source->read($bufferSize)) !== false) {
            $copied += strlen($block);
            $target->write($block);
        }
        return $copied;
    }
}
