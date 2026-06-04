<?php

namespace App\Erp\Policies;

use App\Erp\Models\Project;
use Illuminate\Contracts\Auth\Authenticatable;

class ProjectPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.projects.view'); }
    public function view(Authenticatable $user, Project $project): bool { return $this->can($user, 'erp.projects.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.projects.create'); }
    public function update(Authenticatable $user, Project $project): bool { return $this->can($user, 'erp.projects.update'); }
    public function delete(Authenticatable $user, Project $project): bool { return $this->can($user, 'erp.projects.delete'); }
}
