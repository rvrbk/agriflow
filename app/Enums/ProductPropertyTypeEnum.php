<?php

namespace App\Enums;

enum ProductPropertyTypeEnum: string
{
    case INT = 'int';
    case FLOAT = 'float';
    case STRING = 'string';
    case BOOL = 'bool';
    case DATE = 'date';
    case LINK = 'link';
}