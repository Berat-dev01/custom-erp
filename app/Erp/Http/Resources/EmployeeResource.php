<?php

namespace App\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'employee_number' => $this->employee_number,
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'full_name'       => $this->full_name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'gender'          => $this->gender,
            'department'      => $this->whenLoaded('department', fn () => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'position'        => $this->whenLoaded('position', fn () => [
                'id'   => $this->position->id,
                'name' => $this->position->name,
            ]),
            'employment_type' => $this->employment_type,
            'status'          => $this->status,
            'hire_date'       => $this->hire_date?->toDateString(),
            'termination_date'=> $this->termination_date?->toDateString(),
        ];
    }
}
