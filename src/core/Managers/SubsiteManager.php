<?php
declare(strict_types=1);

namespace MapasCulturais\Managers;

use MapasCulturais\App;
use MapasCulturais\Entities\Subsite;

class SubsiteManager
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function create(array $data): Subsite
    {
        $subsite = new Subsite();

        $subsite->name = $data['name'];
        $subsite->url = $data['url'];
        $subsite->owner = $this->app->repo('Agent')->find($data['owner']);
        $subsite->namespace = $data['namespace'] ?? 'Subsite';

        $this->app->em->persist($subsite);

        try {
            $this->app->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            throw new \Exception("Duplicate URL: " . $data['url'], 0, $e);
        }

        return $subsite;
    }

    public function update(int $id, array $data): Subsite
    {
        $subsite = $this->app->repo('Subsite')->find($id);

        if (!$subsite) {
            throw new \Exception("Subsite not found");
        }

        if (isset($data['name'])) {
            $subsite->name = $data['name'];
        }

        if (isset($data['url'])) {
            $subsite->url = $data['url'];
        }

        $this->app->em->flush();

        return $subsite;
    }

    public function delete(int $id): void
    {
        $subsite = $this->app->repo('Subsite')->find($id);

        if (!$subsite) {
            throw new \Exception("Subsite not found");
        }

        $this->app->em->remove($subsite);
        $this->app->em->flush();
    }

    public function toggleStatus(int $id): void
    {
        $subsite = $this->app->repo('Subsite')->find($id);

        if (!$subsite) {
            throw new \Exception("Subsite not found");
        }

        $subsite->status = $subsite->status === 1 ? 0 : 1;
        $this->app->em->flush();
    }

    public function list(): array
    {
        return $this->app->repo('Subsite')->findAll();
    }
}