<?php

namespace App\Services;

use App\Models\ProjectDescription;

class ProjectDescriptionService
{
    public function list()
    {
        return ProjectDescription::latest()->paginate(10);
    }

    public function store(array $data)
    {
        return ProjectDescription::create($data);
    }

    public function update(ProjectDescription $project, array $data)
    {
        $project->update($data);

        return $project;
    }

    public function delete(ProjectDescription $project)
    {
        $project->delete();
    }
}