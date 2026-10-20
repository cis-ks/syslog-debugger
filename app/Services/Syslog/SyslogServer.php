<?php

namespace App\Services\Syslog;

use App\Events\SyslogMessageReceived;
use React\Datagram\Factory as DatagramFactory;
use React\Datagram\Socket as DatagramSocket;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

class SyslogServer
{
    protected LoopInterface $loop;

    protected SyslogParser $parser;

    protected DatagramSocket|null $udpSocket = null;

    protected SocketServer|null $tlsServer = null;

    /**
     * @var array<int, callable(SyslogMessage): void>
     */
    protected array $messageCallbacks = [];

    /**
     * @param array<string, mixed>|null $tlsOptions
     */
    public function __construct(
        public readonly string $udpHost = '0.0.0.0',
        public readonly int $udpPort = 514,
        public readonly string $tlsHost = '0.0.0.0',
        public readonly int $tlsPort = 6514,
        public readonly bool $enableUdp = true,
        public readonly bool $enableTls = true,
        public readonly array|null $tlsOptions = null,
        SyslogParser|null $parser = null,
        LoopInterface|null $loop = null,
    ) {
        $this->parser = $parser ?? new SyslogParser();
        $this->loop = $loop ?? Loop::get();
    }

    /**
     * @param callable(SyslogMessage): void $callback
     */
    public function onMessage(callable $callback): self
    {
        $this->messageCallbacks[] = $callback;

        return $this;
    }

    public function start(): void
    {
        if ($this->enableUdp && $this->udpPort > 0) {
            $this->startUdpListener();
        }

        if ($this->enableTls && $this->tlsPort > 0) {
            $this->startTlsListener();
        }
    }

    public function stop(): void
    {
        if ($this->udpSocket !== null) {
            $this->udpSocket->close();
            $this->udpSocket = null;
        }

        if ($this->tlsServer !== null) {
            $this->tlsServer->close();
            $this->tlsServer = null;
        }
    }

    public function run(): void
    {
        $this->start();
        $this->loop->run();
    }

    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    protected function startUdpListener(): void
    {
        $factory = new DatagramFactory($this->loop);
        $address = $this->udpHost . ':' . $this->udpPort;

        $factory->createServer($address)->then(
            function (DatagramSocket $socket): void {
                $this->udpSocket = $socket;

                $socket->on('message', function (string $data, string $peerAddress): void {
                    $peerIp = parse_url('udp://' . $peerAddress, PHP_URL_HOST) ?? $peerAddress;
                    $message = $this->parser->parse($data, (string) $peerIp, 'udp');
                    $this->handleMessage($message);
                });
            },
            function (\Throwable $e): void {
                // Log or report UDP server binding error
            }
        );
    }

    protected function startTlsListener(): void
    {
        $options = $this->tlsOptions ?? $this->resolveTlsOptions();
        $hasTlsCert = !empty($options['tls']['local_cert'] ?? null);

        $uri = ($hasTlsCert ? 'tls://' : 'tcp://') . $this->tlsHost . ':' . $this->tlsPort;

        $context = $hasTlsCert ? $options : [];
        $this->tlsServer = new SocketServer($uri, $context, $this->loop);

        $this->tlsServer->on('connection', function (ConnectionInterface $connection): void {
            $buffer = '';
            $peerAddress = $connection->getRemoteAddress() ?? '127.0.0.1';
            $peerIp = parse_url('tcp://' . $peerAddress, PHP_URL_HOST) ?? $peerAddress;

            $connection->on('data', function (string $chunk) use (&$buffer, $peerIp): void {
                $buffer .= $chunk;
                [$messages, $remaining] = $this->parser->extractFramedMessages($buffer);
                $buffer = $remaining;

                foreach ($messages as $rawMessage) {
                    $message = $this->parser->parse($rawMessage, (string) $peerIp, 'tls');
                    $this->handleMessage($message);
                }
            });

            $connection->on('end', function () use (&$buffer, $peerIp): void {
                if (trim($buffer) !== '') {
                    $message = $this->parser->parse($buffer, (string) $peerIp, 'tls');
                    $this->handleMessage($message);
                    $buffer = '';
                }
            });
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveTlsOptions(): array
    {
        $cert = config('syslog.tls.cert');
        $key = config('syslog.tls.key');

        if (!is_string($cert) || !file_exists($cert)) {
            return [];
        }

        return [
            'tls' => [
                'local_cert' => $cert,
                'local_pk' => is_string($key) && file_exists($key) ? $key : null,
                'passphrase' => config('syslog.tls.passphrase'),
                'verify_peer' => (bool) config('syslog.tls.verify_peer', false),
                'allow_self_signed' => (bool) config('syslog.tls.allow_self_signed', true),
            ],
        ];
    }

    protected function handleMessage(SyslogMessage $message): void
    {
        if (config('syslog.dispatch_events', true)) {
            SyslogMessageReceived::dispatch($message);
        }

        foreach ($this->messageCallbacks as $callback) {
            $callback($message);
        }
    }
}
