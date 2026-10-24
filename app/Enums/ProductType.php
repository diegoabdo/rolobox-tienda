<?php

namespace App\Enums;

enum ProductType: string
{
    /** Caja armable: el cliente elige exactamente `box_size` sabores. */
    case Box = 'box';

    /** Un rol suelto de un sabor. */
    case Single = 'single';
}
