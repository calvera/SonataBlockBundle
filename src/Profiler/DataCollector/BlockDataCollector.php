<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Profiler\DataCollector;

use Sonata\BlockBundle\Templating\Helper\BlockHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Block data collector for the symfony web profiling.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class BlockDataCollector implements DataCollectorInterface, \Serializable
{
    /**
     * @var BlockHelper
     */
    protected $blocksHelper;

    /**
     * @var array
     */
    protected $blocks = [];

    /**
     * @var array
     */
    protected $containers = [];

    /**
     * @var array
     */
    protected $realBlocks = [];

    /**
     * @var array
     */
    protected $containerTypes = [];

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @param BlockHelper $blockHelper    Block renderer
     * @param array       $containerTypes array of container types
     */
    public function __construct(BlockHelper $blockHelper, array $containerTypes)
    {
        $this->blocksHelper = $blockHelper;
        $this->containerTypes = $containerTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->blocks = $this->blocksHelper->getTraces();

        // split into containers & real blocks
        foreach ($this->blocks as $id => $block) {
            if (!\is_array($block)) {
                return; // something went wrong while collecting information
            }

            if ('_events' == $id) {
                foreach ($block as $uniqid => $event) {
                    $this->events[$uniqid] = $event;
                }

                continue;
            }

            if (\in_array($block['type'], $this->containerTypes)) {
                $this->containers[$id] = $block;
            } else {
                $this->realBlocks[$id] = $block;
            }
        }
    }

    /**
     * Returns the number of block used.
     *
     * @return int
     */
    public function getTotalBlock()
    {
        return \count($this->realBlocks) + \count($this->containers);
    }

    /**
     * Return the events used on the current page.
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Returns the block rendering history.
     *
     * @return array
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * Returns the container blocks.
     *
     * @return array
     */
    public function getContainers()
    {
        return $this->containers;
    }

    /**
     * Returns the real blocks (non-container).
     *
     * @return array
     */
    public function getRealBlocks()
    {
        return $this->realBlocks;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $data = [
            'blocks' => $this->blocks,
            'containers' => $this->containers,
            'realBlocks' => $this->realBlocks,
            'events' => $this->events,
        ];

        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($data)
    {
        $merged = unserialize($data);

        $this->blocks = $merged['blocks'];
        $this->containers = $merged['containers'];
        $this->realBlocks = $merged['realBlocks'];
        $this->events = $merged['events'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'block';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->blocks = [];
        $this->containers = [];
        $this->realBlocks = [];
        $this->events = [];
    }
}
