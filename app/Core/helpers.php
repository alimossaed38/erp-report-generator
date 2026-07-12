<?php

function money($n): string
{
    return number_format((float)$n, 0) . ' ر.س';
}
