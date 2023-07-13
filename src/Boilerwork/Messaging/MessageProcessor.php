<?php

declare(strict_types=1);

namespace Boilerwork\Messaging;

use Boilerwork\Container\IsolatedContainer;
use Boilerwork\Tracking\TrackingContext;
use Boilerwork\Server\ExceptionHandler;
use DateTime;

use function call_user_func;
use function container;
use function error;
use function explode;
use function globalContainer;
use function json_encode;
use function sprintf;
use function substr;

use const JSON_PRETTY_PRINT;
use const PHP_EOL;

final class MessageProcessor
{
    private MessageClientInterface $messageClient;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        private readonly MessagingProviderInterface $subscriptions,
        private readonly array $topics,
    ) {
        $this->messageClient = globalContainer()->get(MessageClientInterface::class);

        $this->exceptionHandler = new ExceptionHandler();
    }

    public function process(): void
    {
        $consumer = $this->messageClient->subscribe(topics: $this->topics);

        if ($consumer === null) {
            echo "\n\n########\nERROR CONNECTING TO KAFKA BROKER\n########\n\n ";
            unset($consumer);
            throw new \OpenSwoole\Exception("ERROR CONNECTING TO KAFKA BROKER", 500);
        }

        while (true) {
            try {
                $this->consumeMessage($consumer);
            } catch (\Throwable $th) {
//                $errorMessage = sprintf(
//                    'ERROR HANDLED CONSUMING MESSAGE: %s',
//                    $th->getMessage(),
//                );

//                $exception = new \Exception($errorMessage, 500, $th);

                $this->exceptionHandler->handle($th);

                continue;
            }
        }
    }

    private function consumeMessage($consumer): void
    {
        $messageReceived = $consumer->consume($this->messageClient->timeout() * 1000);

        // Create an isolated container for each incoming Message
        globalContainer()->setIsolatedContainer(new IsolatedContainer());

        switch ($messageReceived->err) {
            case \RD_KAFKA_RESP_ERR_NO_ERROR:
                $this->processMessage($messageReceived);
                break;
            case \RD_KAFKA_RESP_ERR__PARTITION_EOF:
                echo "Kafka: No more messages; will wait for more\n";
                break;
            case \RD_KAFKA_RESP_ERR__TIMED_OUT:
                // echo "Kafka: Timed out\n";
                break;
            default:
                error($messageReceived->errstr());
                throw new \OpenSwoole\Exception($messageReceived->errstr(), $messageReceived->err);
        }
    }

    private function processMessage($messageReceived): void
    {
        foreach ($this->subscriptions->getSubscriptions() as $item) {
            $topicReceived = explode('__', $messageReceived->topic_name)[1];

            // Empty Payload checks if message is a test or only used to pre-create topic
            if ($topicReceived === $item['topic'] && $messageReceived->payload !== '') {
                $message = new Message(
                    payload  : $messageReceived->payload,
                    topic    : $messageReceived->topic_name,
                    createdAt: (new DateTime())->setTimestamp((int)substr((string)$messageReceived->timestamp, 0, 10)),
                    error    : $messageReceived->err,
                    key      : $messageReceived->key,
                    headers  : $messageReceived->headers,
                );

                // Make it Accesible in local isolated container
                container()->instance(TrackingContext::NAME, $message->trackingContext());

                $class = container()->get($item['target']);
                try {
                    call_user_func($class, $message);
                } catch (\Throwable $th) {
//                    $errorMessage = sprintf(
//                        'ERROR HANDLED PROCESSING MESSAGE: %s ||%sMESSAGE RECEIVED: %s',
//                        $th->getMessage(),
//                        PHP_EOL,
//                        json_encode($messageReceived, JSON_PRETTY_PRINT),
//                    );
//
//                    $exception = new \Exception($errorMessage, 500, $th);

                    $this->exceptionHandler->handle($th);
                    continue;
                }
            }
        }
    }
}
