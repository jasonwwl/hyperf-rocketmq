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
namespace Turing\HyperfRocketmq;

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Psr\Container\ContainerInterface;
use Turing\HyperfRocketmq\Annotation\Consumer as ConsumerAnnotation;
use Turing\HyperfRocketmq\Annotation\ConsumerTag;

class ConsumerManager
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function run()
    {
        $consumers = AnnotationCollector::getClassesByAnnotation(ConsumerAnnotation::class);
        $tagsAnnotation = AnnotationCollector::getMethodsByAnnotation(ConsumerTag::class);
        $configUtil = $this->container->get(ConfigInterface::class);
        $config = $configUtil->get('rocketmq.default');

        /**
         * @var string $consumerClass
         * @var ConsumerAnnotation $annotation
         */
        foreach ($consumers as $consumerClass => $annotation) {
            // $instance = make($consumerClass);
            $tagAnnotations = [];
            foreach ($tagsAnnotation as $tagClassItem) {
                if ($tagClassItem['class'] === $consumerClass) {
                    $tags = explode(',', $tagClassItem['annotation']->tag);
                    foreach ($tags as $tag) {
                        if (isset($tagAnnotations[$tag])) {
                            throw new Exception('Tag"' . $tag . "\"不可定义于多个method上!\n(错误Consumer: " . $consumerClass . ', 错误Method: ' . $tagClassItem['method'] . ')');
                        }
                        $tagAnnotations[$tag] = $tagClassItem;
                    }
                }
            }
            $consumerProcess = new ConsumerProcess($config, $consumerClass, $annotation, $tagAnnotations);
            $process = $this->createProcess($consumerProcess);
            $process->nums = $annotation->numOfProcess;
            $process->name = 'consumer-' . $annotation->topic;
            ProcessManager::register($process);
        }
    }

    public function createProcess(ConsumerProcess $consumerProcess)
    {
        return new class($this->container, $consumerProcess) extends AbstractProcess {
            private ConsumerInterface $consumer;

            private ConsumerProcess $consumerProcess;

            public function __construct(ContainerInterface $container, ConsumerProcess $consumerProcess)
            {
                parent::__construct($container);
                $this->consumerProcess = $consumerProcess;
            }

            public function handle(): void
            {
                $this->consumerProcess->consume();
            }
        };
    }
}
