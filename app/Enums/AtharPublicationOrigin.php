<?php

namespace App\Enums;

enum AtharPublicationOrigin: string
{
    case ContributorSelected = 'contributor_selected';
    case ContributorEdited = 'contributor_edited';
    case IbrahimSuggested = 'ibrahim_suggested';
}
