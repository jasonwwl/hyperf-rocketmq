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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Psr\Container\ContainerInterface;
use Turing\HyperfRocketmq\Annotation\Consumer as ConsumerAnnotation;
use Turing\HyperfRocketmq\Annotation\ConsumerTag;
use Turing\HyperfRocketmq\Annotation\Producer as ProducerAnnotation;

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
        $messageAnnotations = AnnotationCollector::getClassesByAnnotation(ProducerAnnotation::class);
        $tagsAnnotation = AnnotationCollector::getMethodsByAnnotation(ConsumerTag::class);
        $configUtil = $this->container->get(ConfigInterface::class);
        $config = $configUtil->get('rocketmq.default');
        /**
         * @var string $consumerClass
         * @var ConsumerAnnotation $annotation
         */
        foreach ($consumers as $consumerClass => $annotation) {
            $instance = make($consumerClass);
            $consumerProcess = new ConsumerProcess($config, $annotation, $messageAnnotations);
            $methodAnnotations = [];
            foreach ($tagsAnnotation as $tagClassItem) {
                if ($tagClassItem['class'] === $consumerClass) {
                    $methodAnnotations[$tagClassItem['method']] = $tagClassItem['annotation'];
                }
            }
            $process = $this->createProcess($consumerProcess, $instance, $methodAnnotations);
            $process->nums = $annotation->numOfProcess;
            $process->name = 'consumer-' . $annotation->topic;
            ProcessManager::register($process);
        }
    }

    public function createProcess(ConsumerProcess $consumerProcess, ConsumerInterface $consumer, array $methodAnnotations)
    {
        return new class($this->container, $consumerProcess, $consumer, $methodAnnotations) extends AbstractProcess {
            private ConsumerInterface $consumer;

            private ConsumerProcess $consumerProcess;

            private array $methodAnnotations;

            public function __construct(ContainerInterface $container, ConsumerProcess $consumerProcess, ConsumerInterface $consumer, array $methodAnnotations)
            {
                parent::__construct($container);
                $this->consumerProcess = $consumerProcess;
                $this->consumer = $consumer;
                $this->methodAnnotations = $methodAnnotations;
            }

            public function handle(): void
            {
                $this->consumerProcess->consume($this->consumer, $this->methodAnnotations);
            }
        };
    }
}
