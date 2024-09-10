<?php
namespace MapasCulturais\Traits;

use MapasCulturais\App;
use MapasCulturais\Entity;

trait EntityManagerModel {

    private $entityOpportunity;
    private $entityOpportunityModel;

    function ALL_generatemodel(){
        $app = App::i();

        $this->requireAuthentication();
        $this->entityOpportunity = $this->requestedEntity;
        $this->entityOpportunityModel = $this->generateModel();

        $this->generateEvaluationMethods();
        $this->generatePhases();
        $this->generateMetadata();
        $this->generateRegistrationFieldsAndFiles();

        $this->entityOpportunityModel->save(true);
        
        if($this->isAjax()){
            $this->json($this->entityOpportunity);
        }else{
            $app->redirect($app->request->getReferer());
        }
    }

    function ALL_generateopportunity(){
        $app = App::i();

        $this->requireAuthentication();
        $this->entityOpportunity = $this->requestedEntity;

        $this->entityOpportunityModel = $this->generateOpportunity();

        $this->generateEvaluationMethods();
        $this->generatePhases();
        $this->generateMetadata(0, 0);
        $this->generateRegistrationFieldsAndFiles();

        $this->entityOpportunityModel->save(true);
       
        $this->json($this->entityOpportunityModel); 
    }

    function GET_findOpportunitiesModels()
    {
        $app = App::i();
        $dataModels = [];
        
        $opportunities = $app->em->createQuery("
            SELECT 
                op.id
            FROM
                MapasCulturais\Entities\Opportunity op
        ");

        foreach ($opportunities->getResult() as $opportunity) {
            $opp = $app->repo('Opportunity')->find($opportunity['id']);
            if ($opp->isModel) {
                $phases = $opp->phases;

                $lastPhase = array_pop($phases);
                
                $days = !is_null($opp->registrationFrom) && !is_null($lastPhase->publishTimestamp) ? $lastPhase->publishTimestamp->diff($opp->registrationFrom)->days . " Dia(s)" : 'N/A';
                $tipoAgente = $opp->registrationProponentTypes ? implode(', ', $opp->registrationProponentTypes) : 'N/A';
                $dataModels[] = [
                    'id' => $opp->id,
                    'numeroFases' => count($opp->phases),
                    'descricao' => $opp->shortDescription,
                    'tempoEstimado' => $days,
                    'tipoAgente'   =>  $tipoAgente  
                ];
            }
        }

        echo json_encode($dataModels);
    }

    private function generateModel()
    {
        $app = App::i();

        $postData = $this->postData;

        $name = $postData['name'];
        $description = $postData['description'];

        $this->entityOpportunityModel = clone $this->entityOpportunity;

        $this->entityOpportunityModel->name = $name;
        $this->entityOpportunityModel->status = -1;
        $this->entityOpportunityModel->shortDescription = $description;
        $app->em->persist($this->entityOpportunityModel);
        $app->em->flush();

        // necessário adicionar as categorias, proponetes e ranges após salvar devido a trigger public.fn_propagate_opportunity_insert
        $this->entityOpportunityModel->registrationCategories = $this->entityOpportunity->registrationCategories;
        $this->entityOpportunityModel->registrationProponentTypes = $this->entityOpportunity->registrationProponentTypes;
        $this->entityOpportunityModel->registrationRanges = $this->entityOpportunity->registrationRanges;
        $this->entityOpportunityModel->save(true);

        return $this->entityOpportunityModel;

        
    }

    private function generateOpportunity()
    {
        $app = App::i();

        $postData = $this->postData;

        $name = $postData['name'];

        $this->entityOpportunityModel = clone $this->entityOpportunity;

        $this->entityOpportunityModel->name = $name;
        $this->entityOpportunityModel->status = Entity::STATUS_DRAFT;
        $app->em->persist($this->entityOpportunityModel);
        $app->em->flush();

        // necessário adicionar as categorias, proponetes e ranges após salvar devido a trigger public.fn_propagate_opportunity_insert
        $this->entityOpportunityModel->registrationCategories = $this->entityOpportunity->registrationCategories;
        $this->entityOpportunityModel->registrationProponentTypes = $this->entityOpportunity->registrationProponentTypes;
        $this->entityOpportunityModel->registrationRanges = $this->entityOpportunity->registrationRanges;
        $this->entityOpportunityModel->save(true);

        return $this->entityOpportunityModel;
    }

    private function generateEvaluationMethods() : void
    {
        $app = App::i();

        // duplica o método de avaliação para a oportunidade primária
        $evaluationMethodConfigurations = $app->repo('EvaluationMethodConfiguration')->findBy([
            'opportunity' => $this->entityOpportunity
        ]);
        foreach ($evaluationMethodConfigurations as $evaluationMethodConfiguration) {
            $newMethodConfiguration = clone $evaluationMethodConfiguration;
            $newMethodConfiguration->setOpportunity($this->entityOpportunityModel);
            $newMethodConfiguration->save(true);

            // duplica os metadados das configurações do modelo de avaliação
            foreach ($evaluationMethodConfiguration->getMetadata() as $metadataKey => $metadataValue) {
                $newMethodConfiguration->setMetadata($metadataKey, $metadataValue);
                $newMethodConfiguration->save(true);
            }
        }
    }

    private function generatePhases() : void
    {
        $app = App::i();

        $phases = $app->repo('Opportunity')->findBy([
            'parent' => $this->entityOpportunity
        ]);
        foreach ($phases as $phase) {
            if (!$phase->getMetadata('isLastPhase')) {
                $newPhase = clone $phase;
                $newPhase->setParent($this->entityOpportunityModel);

                foreach ($phase->getMetadata() as $metadataKey => $metadataValue) {
                    if (!is_null($metadataValue) && $metadataValue != '') {
                        $newPhase->setMetadata($metadataKey, $metadataValue);
                        $newPhase->save(true);
                    }
                }

                $newPhase->save(true);

                $evaluationMethodConfigurations = $app->repo('EvaluationMethodConfiguration')->findBy([
                    'opportunity' => $phase
                ]);

                foreach ($evaluationMethodConfigurations as $evaluationMethodConfiguration) {
                    $newMethodConfiguration = clone $evaluationMethodConfiguration;
                    $newMethodConfiguration->setOpportunity($newPhase);
                    $newMethodConfiguration->save(true);

                    // duplica os metadados das configurações do modelo de avaliação para a fase
                    foreach ($evaluationMethodConfiguration->getMetadata() as $metadataKey => $metadataValue) {
                        $newMethodConfiguration->setMetadata($metadataKey, $metadataValue);
                        $newMethodConfiguration->save(true);
                    }
                }
            }

            if ($phase->getMetadata('isLastPhase')) {
                $publishDate = $phase->getPublishTimestamp();
            }
        }

        if (isset($publishDate)) {
            $phases = $app->repo('Opportunity')->findBy([
                'parent' => $this->entityOpportunityModel
            ]);
    
            foreach ($phases as $phase) {
                if ($phase->getMetadata('isLastPhase')) {
                    $phase->setPublishTimestamp($publishDate);
                    $phase->save(true);
                }
            }
        }       
    }


    private function generateMetadata($isModel = 1, $isModelPublic = 0) : void
    {
        foreach ($this->entityOpportunity->getMetadata() as $metadataKey => $metadataValue) {
            if (!is_null($metadataValue) && $metadataValue != '') {
                $this->entityOpportunityModel->setMetadata($metadataKey, $metadataValue);
            }
        }

        $this->entityOpportunityModel->setMetadata('isModel', $isModel);
        $this->entityOpportunityModel->setMetadata('isModelPublic', $isModelPublic);

        $this->entityOpportunityModel->saveTerms();
    }

    private function generateRegistrationFieldsAndFiles() : void
    {
        foreach ($this->entityOpportunity->getRegistrationFieldConfigurations() as $registrationFieldConfiguration) {
            $fieldConfiguration = clone $registrationFieldConfiguration;
            $fieldConfiguration->setOwnerId($this->entityOpportunityModel->getId());
            $fieldConfiguration->save(true);
        }

        foreach ($this->entityOpportunity->getRegistrationFileConfigurations() as $registrationFileConfiguration) {
            $fileConfiguration = clone $registrationFileConfiguration;
            $fileConfiguration->setOwnerId($this->entityOpportunityModel->getId());
            $fileConfiguration->save(true);
        }

    }
}
