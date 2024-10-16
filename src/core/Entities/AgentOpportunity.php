<?php
namespace MapasCulturais\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @property \MapasCulturais\Entities\Agent $ownerEntity
 * @property self $parent
 * 
 * @ORM\Entity
 * @ORM\entity(repositoryClass="MapasCulturais\Repository")
 */
class AgentOpportunity extends Opportunity{

    /**
     * @var \MapasCulturais\Entities\Agent
     *
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\Agent")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $ownerEntity;

    /**
     * @var \MapasCulturais\Entities\AgentOpportunity
     *
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\AgentOpportunity", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $parent;

    public function getSpecializedClassName() {
        return AgentOpportunity::class;
    }
}
