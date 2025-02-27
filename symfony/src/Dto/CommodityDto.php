<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CommodityDto
{
    #[Assert\NotBlank]
    #[Assert\Url]
    public ?string $url = null;
}
