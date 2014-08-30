<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Compiler;

/**
 * Prioritize order of services.
 *
 * @author John Kary <john@johnkary.net>
 */
class ServicePrioritizer
{
    private $storage = array();

    /**
     * Add an item to be prioritized.
     *
     * @param mixed $item
     * @param int $priority
     */
    public function add($item, $priority = 0)
    {
        $this->storage[(int) $priority][] = $item;
    }

    /**
     * Get items in prioritized order.
     *
     * @return array
     */
    public function toArray()
    {
        if (empty($this->storage)) {
            return array();
        }

        $services = $this->storage;
        krsort($services);

        return call_user_func_array('array_merge', $services);
    }
}
