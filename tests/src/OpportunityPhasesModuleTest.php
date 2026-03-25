<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../src/modules/OpportunityPhases/Module.php';
require_once __DIR__ . '/../../src/core/Repositories/EntityRevision.php';

use MapasCulturais\Entities\ProjectOpportunity;
use OpportunityPhases\Module;

class OpportunityPhasesModuleTest extends MapasCulturais_TestCase
{
    private function normalizeOpportunityResult($value)
    {
        $method = new ReflectionMethod(Module::class, 'normalizeOpportunityResult');
        $method->setAccessible(true);

        return $method->invoke(null, $value);
    }

    private function normalizeOpportunityResults(array $values)
    {
        $method = new ReflectionMethod(Module::class, 'normalizeOpportunityResults');
        $method->setAccessible(true);

        return $method->invoke(null, $values);
    }

    private function normalizeEntityRevisionResult($value)
    {
        $repository = $this->getMockBuilder(\MapasCulturais\Repositories\EntityRevision::class)
            ->disableOriginalConstructor()
            ->getMock();

        $method = new ReflectionMethod(\MapasCulturais\Repositories\EntityRevision::class, 'normalizeEntityResult');
        $method->setAccessible(true);

        return $method->invoke($repository, $value);
    }

    public function testNormalizeOpportunityResultUnwrapsDoctrineMixedHydrationRows()
    {
        $phase = new ProjectOpportunity();

        $result = $this->normalizeOpportunityResult([0 => $phase]);

        $this->assertSame($phase, $result);
    }

    public function testNormalizeOpportunityResultKeepsEntityResultsUntouched()
    {
        $phase = new ProjectOpportunity();

        $result = $this->normalizeOpportunityResult($phase);

        $this->assertSame($phase, $result);
    }

    public function testNormalizeOpportunityResultsUnwrapsMixedHydrationLists()
    {
        $phase = new ProjectOpportunity();

        $result = $this->normalizeOpportunityResults([[0 => $phase]]);

        $this->assertSame([$phase], $result);
    }

    public function testNormalizeEntityRevisionResultUnwrapsDoctrineMixedHydrationRows()
    {
        $revision = $this->getMockBuilder(\MapasCulturais\Entities\EntityRevision::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->normalizeEntityRevisionResult([0 => $revision]);

        $this->assertSame($revision, $result);
    }
}
