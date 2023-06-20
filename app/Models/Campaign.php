<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public function campaignConditions() {
        return $this->hasMany(CampaignCondition::class, 'campaign_id');
    }
}
