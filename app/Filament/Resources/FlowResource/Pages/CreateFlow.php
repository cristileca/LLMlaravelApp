<?php

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\FlowResource;
use App\Models\User;
use Auth;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFlow extends CreateRecord
{
    protected static string $resource = FlowResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $index = 1;
        $data['id_user'] = Auth::id();    
        if (isset($this->data['stages']) && is_array($this->data['stages'])) {
            foreach ($this->data['stages'] as $key => $stage) {
                $stage['stage_number'] = $index;
                if(isset($stage['stage_number']) && $stage['stage_number'] == 1) {
                    $stage['status_stage'] = 'Inprogres';
                }else{
                    $stage['status_stage'] = 'Ongoing';
                } 
                $this->data['stages'][$key] = $stage;
                $index +=1;
            }
            $data['stages'] = $this->data['stages']; 
        }
        
        return $data;
    }   
}
