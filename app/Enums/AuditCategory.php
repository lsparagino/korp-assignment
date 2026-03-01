<?php

namespace App\Enums;

enum AuditCategory: string
{
    case Auth = 'auth';
    case Transaction = 'transaction';
    case Team = 'team';
    case Wallet = 'wallet';
    case Settings = 'settings';
}
