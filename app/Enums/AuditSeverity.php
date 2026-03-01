<?php

namespace App\Enums;

enum AuditSeverity: string
{
    case Normal = 'normal';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
