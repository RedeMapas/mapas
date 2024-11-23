<?php

namespace MapasCulturais\Tests;

use MapasCulturais\App;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Entities\Opportunity;
use OpportunityWorkplan\Entities\Workplan;
use PHPUnit\Framework\TestCase;
use WorkplanSyncTransfereGov\Plugin;

require_once __DIR__ . '/../../src/plugins/WorkplanSyncTransfereGov/Plugin.php';

class WorkplanSyncTransfereGovPluginTest extends TestCase
{
    private $plugin;
    private $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::i();
        // $this->plugin = new Plugin();
    }

    public function testGetHttpResponseContentJson()
    {
        // Mock curl operations using php-mock
        $mock = $this->getMockBuilder(Plugin::class)
            ->onlyMethods(['get_http_response_content_json'])
            ->getMock();

        $expectedData = [
            'id_plano_acao' => 123,
            'numero_plano_acao' => '001',
            'ano_plano_acao' => 2023
        ];

        $mock->expects($this->once())
            ->method('get_http_response_content_json')
            ->willReturn($expectedData);

        $result = $mock->get_http_response_content_json('test-url');
        $this->assertEquals($expectedData, $result);
    }

    public function testGetOrCreateRegistration()
    {
        // Create test opportunity
        $opportunity = new Opportunity();
        $opportunity->name = 'Test Opportunity';
        
        $planoAcao = [
            'id_plano_acao' => 123,
            'numero_plano_acao' => '001',
            'ano_plano_acao' => 2023
        ];

        $registration = $this->plugin->get_or_create_registration($opportunity, $planoAcao);

        $this->assertInstanceOf(Registration::class, $registration);
        $this->assertEquals($planoAcao['id_plano_acao'], $registration->getMetadata('transferegov_plano_acao_id'));
        $this->assertEquals($planoAcao['numero_plano_acao'], $registration->getMetadata('transferegov_numero_plano_acao'));
        $this->assertEquals($planoAcao['ano_plano_acao'], $registration->getMetadata('transferegov_ano_plano_acao'));
    }

    public function testGenerateWorkplanFromTransferegov()
    {
        // Create test registration
        $registration = new Registration();
        $registration->setMetadata('transferegov_plano_acao_id', 123);

        // Mock the get_transfreregov_meta method
        $mock = $this->getMockBuilder(Plugin::class)
            ->onlyMethods(['get_transfreregov_meta'])
            ->getMock();

        $metaData = [[
            'id_meta_plano_acao' => 1,
            'numero_meta_plano_acao' => '001',
            'nome_meta_plano_acao' => 'Test Meta',
            'descricao_meta_plano_acao' => 'Test Description',
            'valor_meta_plano_acao' => 1000.00
        ]];

        $mock->expects($this->once())
            ->method('get_transfreregov_meta')
            ->willReturn($metaData);

        $workplan = $mock->generate_workplan_from_transferegov($registration->id);

        $this->assertInstanceOf(Workplan::class, $workplan);
        $this->assertEquals($registration, $workplan->registration);
        $this->assertEquals(123, $workplan->getMetadata('transferegov_plano_acao_id'));
    }

    public function testGenerateWorkplan()
    {
        // Create test opportunity
        $opportunity = new Opportunity();
        $opportunity->name = 'Test Opportunity';

        // Mock necessary methods
        $mock = $this->getMockBuilder(Plugin::class)
            ->onlyMethods(['get_transfreregov_plano_de_acao', 'generate_workplan_from_transferegov'])
            ->getMock();

        $planoAcaoData = [[
            'id_plano_acao' => 123,
            'numero_plano_acao' => '001',
            'ano_plano_acao' => 2023
        ]];

        $mock->expects($this->once())
            ->method('get_transfreregov_plano_de_acao')
            ->willReturn($planoAcaoData);

        $workplan = new Workplan();
        $mock->expects($this->once())
            ->method('generate_workplan_from_transferegov')
            ->willReturn($workplan);

        $result = $mock->generate_workplan($opportunity->id);

        $this->assertIsArray($result);
        $this->assertInstanceOf(Workplan::class, $result[0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up any test data if necessary
    }
}
