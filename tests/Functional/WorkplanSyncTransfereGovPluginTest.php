<?php
namespace MapasCulturais\Tests;

use MapasCulturais\App;
use MapasCulturais\Entities\Opportunity;
use PHPUnit\Framework\TestCase;
class WorkplanSyncTransfereGovPluginTest extends TestCase
{
    private $plugin;
    private $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::i();
    }

    public function testGenerateWorkplan()
    {
        // Mock opportunity
        $opportunity = $this->createMock(Opportunity::class);
        $opportunity->method('getId')->willReturn(1);

        // Mock the app repository to return our mocked opportunity
        $this->app->expects($this->once())
            ->method('repo')
            ->with('Opportunity')
            ->willReturn(new class($opportunity) {
                private $opportunity;
                public function __construct($opportunity) {
                    $this->opportunity = $opportunity;
                }
                public function find($id) {
                    return $this->opportunity;
                }
            });

        // Mock TransfereGov API response
        $mockPlanoAcao = [
            [
                'id_plano_acao' => 123,
                'numero_plano_acao' => '001',
                'ano_plano_acao' => '2023',
                'data_fim_vigencia_plano_acao' => '2023-12-31'
            ]
        ];

        // Create a mock plugin that overrides the API call
        $mockPlugin = $this->getMockBuilder(Plugin::class)
            ->onlyMethods(['get_transfreregov_plano_de_acao'])
            ->getMock();

        $mockPlugin->expects($this->once())
            ->method('get_transfreregov_plano_de_acao')
            ->willReturn($mockPlanoAcao);

        // Execute the test
        $workplan = $mockPlugin->generate_workplan(1);

        // Assertions
        $this->assertNotNull($workplan);
        $this->assertEquals(123, $workplan->getMetadata('transferegov_plano_acao_id'));
    }
}

