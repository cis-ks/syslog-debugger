<?php

namespace App\Console\Commands;

use App\Services\Syslog\SyslogMessage;
use App\Services\Syslog\SyslogServer;
use Illuminate\Console\Command;

class SyslogServerCommand extends Command
{
    protected $signature = 'syslog:server
        {--udp-host= : UDP host address to bind}
        {--udp-port= : UDP port to listen on}
        {--tls-host= : TLS host address to bind}
        {--tls-port= : TLS port to listen on}
        {--no-udp : Disable UDP listener}
        {--no-tls : Disable TLS listener}';

    protected $description = 'Start the reactive Syslog server (UDP 514 & TLS 6514)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string|null $udpHostOpt */
        $udpHostOpt = $this->option('udp-host');
        $udpHost = $udpHostOpt ?: (string) config('syslog.udp.host', '0.0.0.0');

        /** @var string|null $udpPortOpt */
        $udpPortOpt = $this->option('udp-port');
        $udpPort = $udpPortOpt !== null ? (int) $udpPortOpt : (int) config('syslog.udp.port', 514);

        /** @var string|null $tlsHostOpt */
        $tlsHostOpt = $this->option('tls-host');
        $tlsHost = $tlsHostOpt ?: (string) config('syslog.tls.host', '0.0.0.0');

        /** @var string|null $tlsPortOpt */
        $tlsPortOpt = $this->option('tls-port');
        $tlsPort = $tlsPortOpt !== null ? (int) $tlsPortOpt : (int) config('syslog.tls.port', 6514);

        $enableUdp = !(bool) $this->option('no-udp') && (bool) config('syslog.udp.enabled', true);
        $enableTls = !(bool) $this->option('no-tls') && (bool) config('syslog.tls.enabled', true);

        $this->components->info('Starting Syslog Server...');

        if ($enableUdp) {
            $this->components->twoColumnDetail('UDP Listener', "{$udpHost}:{$udpPort}");
        }

        if ($enableTls) {
            $this->components->twoColumnDetail('TLS Listener', "{$tlsHost}:{$tlsPort}");
        }

        $server = new SyslogServer(
            udpHost: $udpHost,
            udpPort: $udpPort,
            tlsHost: $tlsHost,
            tlsPort: $tlsPort,
            enableUdp: $enableUdp,
            enableTls: $enableTls,
        );

        $server->onMessage(function (SyslogMessage $msg): void {
            $proto = strtoupper($msg->protocol);
            $app = $msg->appName ?? '-';
            $host = $msg->hostname ?? $msg->sourceIp ?? '-';
            $sev = $msg->severityName;

            $this->line(sprintf(
                '[%s] [%s] <%s> [%s@%s]: %s',
                now()->format('Y-m-d H:i:s'),
                $proto,
                $sev,
                $app,
                $host,
                $msg->message
            ));
        });

        $this->components->info('Server is running. Press Ctrl+C to terminate.');
        $server->run();

        return self::SUCCESS;
    }
}
