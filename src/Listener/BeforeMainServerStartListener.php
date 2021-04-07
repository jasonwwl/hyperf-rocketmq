<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Turing\HyperfRocketmq\Listener;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Psr\Container\ContainerInterface;
use Turing\HyperfRocketmq\ConsumerManager;

class BeforeMainServerStartListener implements ListenerInterface
{
    /**
     * @Inject
     */
    private ContainerInterface $container;

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
        ];
    }

    public function process(object $event)
    {
        /**
         * @var ConsumerManager $consumerManager
         */
        $consumerManager = $this->container->get(ConsumerManager::class);
        $consumerManager->run();
    }
}
