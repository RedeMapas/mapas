<?php
namespace MapasCulturais\Traits;

use MapasCulturais\App;
use MapasCulturais\Entities\ProjectOpportunity;
use MapasCulturais\Entity;
use MapasCulturais\Definitions\Metadata AS DefinitionMetadata;

trait EntityManagerModel {

    private ProjectOpportunity $opportunity;
    private ProjectOpportunity $opportunityModel;

    function ALL_generatemodel(){
        $app = App::i();

        $this->requireAuthentication();
        $this->opportunity = $this->requestedEntity;
        $this->opportunityModel = $this->generateModel();

        $this->generateEvaluationMethods();
        $this->generatePhases();
        $this->generateMetadata();
        $this->generateRegistrationFieldsAndFiles();

        $this->opportunityModel->save(true);
        
        if($this->isAjax()){
            $this->json($this->opportunity);
        }else{
            $app->redirect($app->request->getReferer());
        }
    }

    function ALL_generateopportunity(){
        $app = App::i();

        $this->requireAuthentication();
        $this->opportunity = $this->requestedEntity;

        $this->opportunityModel = $this->generateOpportunity();

        $this->generateEvaluationMethods();
        $this->generatePhases();
        $this->generateMetadata(0, 0);
        $this->generateRegistrationFieldsAndFiles();

        $this->opportunityModel->save(true);
       
        $this->json($this->opportunityModel); 
    }

    private function generateModel() : ProjectOpportunity
    {
        $app = App::i();

        $postData = $this->postData;

        $name = $postData['name'];
        $description = $postData['description'];

        $this->opportunityModel = clone $this->opportunity;

        $this->opportunityModel->setName($name);
        $this->opportunityModel->setStatus(-1);
        $this->opportunityModel->setShortDescription($description);
        $this->opportunityModel->registrationCategories = [];
        $app->em->persist($this->opportunityModel);
        $app->em->flush();

        return $this->opportunityModel;
    }

    private function generateOpportunity() : ProjectOpportunity
    {
        $app = App::i();

        $postData = $this->postData;

        $name = $postData['name'];

        $this->opportunityModel = clone $this->opportunity;

        $this->opportunityModel->setName($name);
        $this->opportunityModel->setStatus(0);
        $this->opportunityModel->registrationCategories = [];
        $app->em->persist($this->opportunityModel);
        $app->em->flush();

        return $this->opportunityModel;
    }

    private function generateOpportunity() : ProjectOpportunity
    {
        $app = App::i();

        $postData = $this->postData;

        $name = $postData['name'];

        $this->opportunityModel = clone $this->opportunity;

        $this->opportunityModel->setName($name);
        $this->opportunityModel->setStatus(0);
        $app->em->persist($this->opportunityModel);
        $app->em->flush();

        return $this->opportunityModel;
    }

    private function generateEvaluationMethods() : void
    {
        $app = App::i();

        // duplica o método de avaliação para a oportunidade primária
        $evaluationMethodConfigurations = $app->repo('EvaluationMethodConfiguration')->findBy([
            'opportunity' => $this->opportunity
        ]);
        foreach ($evaluationMethodConfigurations as $evaluationMethodConfiguration) {
            $newMethodConfiguration = clone $evaluationMethodConfiguration;
            $newMethodConfiguration->setOpportunity($this->opportunityModel);
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
            'parent' => $this->opportunity
        ]);
        foreach ($phases as $phase) {
            if (!$phase->getMetadata('isLastPhase')) {
                $newPhase = clone $phase;
                $newPhase->setParent($this->opportunityModel);

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
                'parent' => $this->opportunityModel
            ]);
    
            foreach ($phases as $phase) {
                if ($phase->getMetadata('isLastPhase')) {
                    $phase->setPublishTimestamp($publishDate);
                    $phase->save(true);
                }
            }
        }       
    }


    private function generateMetadata($isModel = 1, $isModelOfficial = 0) : void
    {
        foreach ($this->opportunity->getMetadata() as $metadataKey => $metadataValue) {
            if (!is_null($metadataValue) && $metadataValue != '') {
                $this->opportunityModel->setMetadata($metadataKey, $metadataValue);
            }
        }

        $this->opportunityModel->setMetadata('isModel', $isModel);
        $this->opportunityModel->setMetadata('isModelOfficial', $isModelOfficial);

        $this->opportunityModel->saveTerms();
    }

    private function generateRegistrationFieldsAndFiles() : void
    {
        foreach ($this->opportunity->getRegistrationFieldConfigurations() as $registrationFieldConfiguration) {
            $fieldConfiguration = clone $registrationFieldConfiguration;
            $fieldConfiguration->setOwnerId($this->opportunityModel->getId());
            $fieldConfiguration->save(true);
        }

        foreach ($this->opportunity->getRegistrationFileConfigurations() as $registrationFileConfiguration) {
            $fileConfiguration = clone $registrationFileConfiguration;
            $fileConfiguration->setOwnerId($this->opportunityModel->getId());
            $fileConfiguration->save(true);
        }

    }
}