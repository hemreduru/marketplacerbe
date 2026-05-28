<?php

namespace App\Support\Enums;

enum ClaimReturnReason: string
{
    case DefectiveProduct = 'defective_product';
    case WrongProduct = 'wrong_product';
    case DoesntMatchDescription = 'doesnt_match_description';
    case ChangedMind = 'changed_mind';
    case LateDelivery = 'late_delivery';
    case SizeIssue = 'size_issue';
    case ColorIssue = 'color_issue';
    case QualityIssue = 'quality_issue';
    case DamagedInTransit = 'damaged_in_transit';
    case Other = 'other';
}
