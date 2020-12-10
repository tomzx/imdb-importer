<?php

namespace ImdbImporter\Tests;

use ImdbImporter\Importer;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ImporterTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSubmitRatings()
    {
        $importer = m::mock(Importer::class, 'test')->shouldAllowMockingProtectedMethods()->makePartial();

        $importer->shouldReceive('httpRequest')
            ->once()
            ->andReturn('({"d": [{"l": "Movie 1", "id": "tt123"}]})')
            ->shouldReceive('httpRequest')
            ->once()
            ->andReturn('data-auth="some-token"')
            ->shouldReceive('httpRequest')
            ->once()
            ->andReturn('{"status": 200}');

        $logger = m::mock(LoggerInterface::class);
        $importer->setLogger($logger);

        $logger->shouldReceive('debug')->once()->with('Searching for Movie 1');
        $logger->shouldReceive('debug')->once()->with('Submitting rating for {"title":"Movie 1","rating":"5.0"} tt123');
        $logger->shouldReceive('info')->once()->with('Submitted rating for Movie 1');

        $importer->submit([
            [
                'title' => 'Movie 1',
                'rating' => '5.0'
            ],
        ]);

        self::assertTrue(true);
    }
}
