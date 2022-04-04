<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ConnectionCheckerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $client;

    /** @var ConnectionChecker */
    protected $checker;

    protected function setUp(): void
    {
        $this->client = $this->createMock(WebsocketClientInterface::class);

        $this->checker = new ConnectionChecker($this->client);
        $this->setUpLoggerMock($this->checker);
    }

    public function testCheckConnection(): void
    {
        $this->client->expects(self::once())
            ->method('connect');
        $this->client->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertTrue($this->checker->checkConnection());
    }

    public function testWsConnectedFail(): void
    {
        $this->client->expects(self::once())
            ->method('connect');
        $this->client->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);

        self::assertFalse($this->checker->checkConnection());
    }

    /**
     * @dataProvider notInstalledDataProvider
     */
    public function testWsConnectedExceptionWhenNotInstalled(?bool $installed): void
    {
        $exception = new \Exception('sample message');
        $this->client->expects(self::once())
            ->method('connect')
            ->willThrowException($exception);
        $this->client->expects(self::never())
            ->method('isConnected');
        $this->loggerMock->expects(self::never())
            ->method(self::anything());

        $this->checker->setApplicationInstalled($installed);

        self::assertFalse($this->checker->checkConnection());
    }

    public function notInstalledDataProvider(): array
    {
        return [[null], [false]];
    }

    public function testWsConnectedException(): void
    {
        $exception = new \Exception('sample message');
        $this->client->expects(self::once())
            ->method('connect')
            ->willThrowException($exception);
        $this->client->expects(self::never())
            ->method('isConnected');
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Failed to connect to websocket server: {message}',
                ['message' => $exception->getMessage(), 'e' => $exception]
            );

        $this->checker->setApplicationInstalled(true);

        self::assertFalse($this->checker->checkConnection());
    }
}
