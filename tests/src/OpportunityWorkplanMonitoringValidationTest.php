<?php
namespace Tests;

use OpportunityWorkplan\Entities\Delivery;
use OpportunityWorkplan\Entities\Goal;
use OpportunityWorkplan\Entities\Workplan;
use Tests\Abstract\TestCase;
use Tests\Traits\OpportunityDirector;
use Tests\Traits\RegistrationDirector;
use Tests\Traits\UserDirector;

class OpportunityWorkplanMonitoringValidationTest extends TestCase
{
    use UserDirector;
    use OpportunityDirector;
    use RegistrationDirector;

    public function testExecutedCommunicationChannelsValidationError()
    {
        $user = $this->userDirector->createUser();
        $this->login($user);

        $opportunity = $this->createOpportunityWithWorkplan([
            'workplan_monitoringInformCommunicationChannels' => true,
            'workplan_monitoringRequireCommunicationChannels' => true,
        ]);

        $registration = $this->createRegistrationWithWorkplan($opportunity, $user, [
            'delivery' => [
                'executedCommunicationChannels' => null,
            ]
        ]);

        $errors = $registration->getValidationErrors();

        $this->assertArrayHasKey('delivery', $errors);
        $this->assertNotEmpty($errors['delivery']);

        $fieldLabel = \OpportunityWorkplan\Module::getFieldLabel('executedCommunicationChannels');
        $hasFieldError = false;
        foreach ($errors['delivery'] as $msg) {
            if (str_contains($msg, $fieldLabel)) {
                $hasFieldError = true;
                break;
            }
        }
        $this->assertTrue($hasFieldError, "Esperava erro de validação para 'executedCommunicationChannels' mas não encontrou. Erros: " . implode('; ', $errors['delivery']));
    }

    public function testExecutedTeamCompositionGenderValidationError()
    {
        $user = $this->userDirector->createUser();
        $this->login($user);

        $opportunity = $this->createOpportunityWithWorkplan([
            'workplan_monitoringInformTeamComposition' => true,
            'workplan_monitoringRequireTeamCompositionGender' => true,
        ]);

        $registration = $this->createRegistrationWithWorkplan($opportunity, $user, [
            'delivery' => [
                'executedTeamCompositionGender' => null,
            ]
        ]);

        $errors = $registration->getValidationErrors();

        $this->assertArrayHasKey('delivery', $errors);
        $this->assertNotEmpty($errors['delivery']);

        $fieldLabel = \OpportunityWorkplan\Module::getFieldLabel('executedTeamCompositionGender');
        $hasFieldError = false;
        foreach ($errors['delivery'] as $msg) {
            if (str_contains($msg, $fieldLabel)) {
                $hasFieldError = true;
                break;
            }
        }
        $this->assertTrue($hasFieldError, "Esperava erro de validação para 'executedTeamCompositionGender' mas não encontrou. Erros: " . implode('; ', $errors['delivery']));
    }

    public function testExecutedCommunityCoauthorsDetailValidationError()
    {
        $user = $this->userDirector->createUser();
        $this->login($user);

        $opportunity = $this->createOpportunityWithWorkplan([
            'workplan_monitoringInformCommunityCoauthors' => true,
            'workplan_monitoringRequireCommunityCoauthorsDetail' => true,
        ]);

        $registration = $this->createRegistrationWithWorkplan($opportunity, $user, [
            'delivery' => [
                'executedHasCommunityCoauthors' => 'true',
                'executedCommunityCoauthorsDetail' => null,
            ]
        ]);

        $errors = $registration->getValidationErrors();

        $this->assertArrayHasKey('delivery', $errors);
        $this->assertNotEmpty($errors['delivery']);

        $fieldLabel = \OpportunityWorkplan\Module::getFieldLabel('executedCommunityCoauthorsDetail');
        $hasFieldError = false;
        foreach ($errors['delivery'] as $msg) {
            if (str_contains($msg, $fieldLabel)) {
                $hasFieldError = true;
                break;
            }
        }
        $this->assertTrue($hasFieldError, "Esperava erro de validação para 'executedCommunityCoauthorsDetail' mas não encontrou. Erros: " . implode('; ', $errors['delivery']));
    }

    private function createOpportunityWithWorkplan(array $metadata = [])
    {
        $app = $this->app;
        $agent = $app->user?->profile ?? $this->userDirector->createUser()->profile;

        $project = new \MapasCulturais\Entities\Project;
        $project->name = 'Projeto Teste Monitoramento';
        $project->shortDescription = 'Teste';
        $project->owner = $agent;
        $project->type = array_key_first($app->getRegisteredEntityTypes($project));
        $project->save(true);

        $opportunityClass = $project->opportunityClassName;
        $opportunity = new $opportunityClass;
        $opportunity->name = 'Oportunidade Teste Monitoramento';
        $opportunity->shortDescription = 'Teste';
        $opportunity->owner = $agent;
        $opportunity->ownerEntity = $project;
        $opportunity->registrationFrom = new \DateTime('now');
        $opportunity->registrationTo = new \DateTime('+30 days');
        $opportunity->enableWorkplan = true;
        $opportunity->workplan_deliveryReportTheDeliveriesLinkedToTheGoals = true;

        foreach ($metadata as $key => $value) {
            $opportunity->$key = $value;
        }

        $opportunity->save(true);

        return $opportunity;
    }

    private function createRegistrationWithWorkplan($opportunity, $user, array $data = [])
    {
        $app = $this->app;

        $registration = new \MapasCulturais\Entities\Registration;
        $registration->opportunity = $opportunity;
        $registration->owner = $user->profile;
        $registration->save(true);

        $workplan = new Workplan;
        $workplan->registration = $registration;
        $workplan->owner = $user->profile;
        $workplan->projectDuration = 12;
        $workplan->save(true);

        $goal = new Goal;
        $goal->workplan = $workplan;
        $goal->owner = $user->profile;
        $goal->monthInitial = 1;
        $goal->monthEnd = 12;
        $goal->title = 'Meta Teste';
        $goal->description = 'Descrição teste';
        $goal->save(true);

        $delivery = new Delivery;
        $delivery->goal = $goal;
        $delivery->owner = $user->profile;
        $delivery->name = 'Entrega Teste';
        $delivery->description = 'Descrição teste';
        $delivery->typeDelivery = 'Outro';

        foreach (($data['delivery'] ?? []) as $key => $value) {
            $delivery->$key = $value;
        }

        $delivery->save(true);

        // Limpa o identity map e re-busca do DB para garantir que as
        // collections lazy (goals, deliveries) estejam atualizadas.
        $registrationId = $registration->id;
        $app->em->clear();

        return $app->repo(\MapasCulturais\Entities\Registration::class)->find($registrationId);
    }
}
