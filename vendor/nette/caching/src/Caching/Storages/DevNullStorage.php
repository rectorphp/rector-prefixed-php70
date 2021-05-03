<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace RectorPrefix20210503\Nette\Caching\Storages;

use RectorPrefix20210503\Nette;
/**
 * Cache dummy storage.
 */
class DevNullStorage implements \RectorPrefix20210503\Nette\Caching\Storage
{
    use Nette\SmartObject;
    public function read(string $key)
    {
    }
    /**
     * @return void
     */
    public function lock(string $key)
    {
    }
    /**
     * @return void
     */
    public function write(string $key, $data, array $dependencies)
    {
    }
    /**
     * @return void
     */
    public function remove(string $key)
    {
    }
    /**
     * @return void
     */
    public function clean(array $conditions)
    {
    }
}