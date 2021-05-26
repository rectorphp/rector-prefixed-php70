<?php

namespace RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Http\Io;

use RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\Evenement\EventEmitter;
use RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\Psr\Http\Message\StreamInterface;
use RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Stream\ReadableStreamInterface;
use RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Stream\Util;
use RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Stream\WritableStreamInterface;
/**
 * @internal
 */
class ReadableBodyStream extends \RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\Evenement\EventEmitter implements \RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Stream\ReadableStreamInterface, \RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\Psr\Http\Message\StreamInterface
{
    private $input;
    private $position = 0;
    private $size;
    private $closed = \false;
    public function __construct(\RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Stream\ReadableStreamInterface $input, $size = null)
    {
        $this->input = $input;
        $this->size = $size;
        $that = $this;
        $pos =& $this->position;
        $input->on('data', function ($data) use($that, &$pos, $size) {
            $that->emit('data', array($data));
            $pos += \strlen($data);
            if ($size !== null && $pos >= $size) {
                $that->handleEnd();
            }
        });
        $input->on('error', function ($error) use($that) {
            $that->emit('error', array($error));
            $that->close();
        });
        $input->on('end', array($that, 'handleEnd'));
        $input->on('close', array($that, 'close'));
    }
    public function close()
    {
        if (!$this->closed) {
            $this->closed = \true;
            $this->input->close();
            $this->emit('close');
            $this->removeAllListeners();
        }
    }
    public function isReadable()
    {
        return $this->input->isReadable();
    }
    public function pause()
    {
        $this->input->pause();
    }
    public function resume()
    {
        $this->input->resume();
    }
    public function pipe(\RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Stream\WritableStreamInterface $dest, array $options = array())
    {
        \RectorPrefix20210526\_HumbugBox0b2f2d5c77b8\React\Stream\Util::pipe($this, $dest, $options);
        return $dest;
    }
    public function eof()
    {
        return !$this->isReadable();
    }
    public function __toString()
    {
        return '';
    }
    public function detach()
    {
        throw new \BadMethodCallException();
    }
    public function getSize()
    {
        return $this->size;
    }
    public function tell()
    {
        throw new \BadMethodCallException();
    }
    public function isSeekable()
    {
        return \false;
    }
    public function seek($offset, $whence = \SEEK_SET)
    {
        throw new \BadMethodCallException();
    }
    public function rewind()
    {
        throw new \BadMethodCallException();
    }
    public function isWritable()
    {
        return \false;
    }
    public function write($string)
    {
        throw new \BadMethodCallException();
    }
    public function read($length)
    {
        throw new \BadMethodCallException();
    }
    public function getContents()
    {
        throw new \BadMethodCallException();
    }
    public function getMetadata($key = null)
    {
        return $key === null ? array() : null;
    }
    /** @internal */
    public function handleEnd()
    {
        if ($this->position !== $this->size && $this->size !== null) {
            $this->emit('error', array(new \UnderflowException('Unexpected end of response body after ' . $this->position . '/' . $this->size . ' bytes')));
        } else {
            $this->emit('end');
        }
        $this->close();
    }
}
