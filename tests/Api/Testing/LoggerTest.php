<?php

namespace Virtue\Api\Testing;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testLogging()
    {
        $log = new Logger();
        $log->error('anError');
        $this->assertEquals(1, $log->contains('error', 'anError'));
        $log->critical('aCriticalError');
        $this->assertEquals(1, $log->contains('critical', 'aCriticalError'));
        $log->emergency('anEmergency');
        $this->assertEquals(1, $log->contains('emergency', 'anEmergency'));
        $log->alert('anAlert');
        $this->assertEquals(1, $log->contains('alert', 'anAlert'));
        $log->info('anInfo');
        $this->assertEquals(1, $log->contains('info', 'anInfo'));
        $log->notice('aNotice');
        $this->assertEquals(1, $log->contains('notice', 'aNotice'));
        $log->warning('aWarning');
        $this->assertEquals(1, $log->contains('warning', 'aWarning'));
        $log->debug('aDebug');
        $this->assertEquals(1, $log->contains('debug', 'aDebug'));
    }
}
