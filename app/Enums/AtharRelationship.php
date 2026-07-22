<?php

namespace App\Enums;

enum AtharRelationship: string
{
    case FormerClient = 'former_client';
    case CurrentClient = 'current_client';
    case Collaborator = 'collaborator';
    case Colleague = 'colleague';
    case Manager = 'manager';
    case TeamMember = 'team_member';
    case Mentor = 'mentor';
    case Mentee = 'mentee';
    case BusinessPartner = 'business_partner';
    case Friend = 'friend';
    case PersonalConnection = 'personal_connection';
    case CommunityConnection = 'community_connection';

    public function label(): string
    {
        return __("admin.athar.relationships.{$this->value}");
    }
}
