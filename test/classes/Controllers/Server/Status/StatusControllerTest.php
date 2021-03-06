<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\StatusController;
use PhpMyAdmin\Replication;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;

class StatusControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
    }

    public function testIndex(): void
    {
        $data = new Data();

        $bytesReceived = 100;
        $bytesSent = 200;
        $maxUsedConnections = 500;
        $abortedConnections = 200;
        $connections = 1000;
        $data->status['Uptime'] = 36000;
        $data->status['Bytes_received'] = $bytesReceived;
        $data->status['Bytes_sent'] = $bytesSent;
        $data->status['Max_used_connections'] = $maxUsedConnections;
        $data->status['Aborted_connects'] = $abortedConnections;
        $data->status['Connections'] = $connections;

        $response = new Response();
        $template = new Template();

        $controller = new StatusController(
            $response,
            $template,
            $data,
            new ReplicationGui(new Replication(), $template),
            $GLOBALS['dbi']
        );

        $replicationInfo = $data->getReplicationInfo();
        $replicationInfo->primaryVariables = [];
        $replicationInfo->replicaVariables = [];

        $controller->index();
        $html = $response->getHTMLResult();

        $traffic = $bytesReceived + $bytesSent;
        $trafficHtml = 'Network traffic since startup: ' . $traffic . ' B';
        $this->assertStringContainsString(
            $trafficHtml,
            $html
        );
        //updatetime
        $upTimeHtml = 'This MySQL server has been running for '
            . '0 days, 10 hours, 0 minutes and 0 seconds';
        $this->assertStringContainsString(
            $upTimeHtml,
            $html
        );
        //master state
        $masterHtml = 'This MySQL server works as <b>master</b>';
        $this->assertStringContainsString(
            $masterHtml,
            $html
        );

        //validate 2: Status::getHtmlForServerStateTraffic
        $trafficHtml = '<table id="serverstatustraffic" class="pma-table w-100 data noclick col-12 col-md-5">';
        $this->assertStringContainsString(
            $trafficHtml,
            $html
        );
        //traffic hint
        $trafficHtml = 'On a busy server, the byte counters may overrun';
        $this->assertStringContainsString(
            $trafficHtml,
            $html
        );
        //$bytes_received
        $this->assertStringContainsString(
            '<td class="value">' . $bytesReceived . ' B',
            $html
        );
        //$bytes_sent
        $this->assertStringContainsString(
            '<td class="value">' . $bytesSent . ' B',
            $html
        );

        //validate 3: Status::getHtmlForServerStateConnections
        $this->assertStringContainsString(
            '<th>Connections</th>',
            $html
        );
        $this->assertStringContainsString(
            '<th>&oslash; per hour</th>',
            $html
        );
        $this->assertStringContainsString(
            '<table id="serverstatusconnections" class="pma-table w-100 data noclick col-12 col-md-6">',
            $html
        );
        $this->assertStringContainsString(
            '<th class="name">Max. concurrent connections</th>',
            $html
        );
        //Max_used_connections
        $this->assertStringContainsString(
            '<td class="value">' . $maxUsedConnections,
            $html
        );
        $this->assertStringContainsString(
            '<th class="name">Failed attempts</th>',
            $html
        );
        //Aborted_connects
        $this->assertStringContainsString(
            '<td class="value">' . $abortedConnections,
            $html
        );
        $this->assertStringContainsString(
            '<th class="name">Aborted</th>',
            $html
        );
    }
}
