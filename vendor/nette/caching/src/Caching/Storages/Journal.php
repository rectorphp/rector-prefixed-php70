<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace RectorPrefix20210503\Nette\Caching\Storages;

/**
 * Cache journal provider.
 */
interface Journal
{
    /**
     * Writes entry information into the journal.
     * @return void
     */
    function write(string $key, array $dependencies);
    /**
     * Cleans entries from journal.
     * @return array|null of removed items or null when performing a full cleanup
     */
    function clean(array $conditions);
}
\class_exists(\RectorPrefix20210503\Nette\Caching\Storages\IJournal::class);