<?php

namespace App\Domain\Shared\DTO;

use BackedEnum;
use Illuminate\Support\Str;

abstract class AbstractModelDTO {
    public function toArray(): array
    {
        $arrayWithCamelCase = (array) $this;
        $arrayWithSnakeCase = [];

        foreach ($arrayWithCamelCase as $key => $value) {
            if ($value === null)
                continue;

            if ($value instanceof BackedEnum)
                $value = $value->value;
            
            $arrayWithSnakeCase[Str::snake($key)] = $value;
        } 

        return $arrayWithSnakeCase;
    }

    public function fromArray(array $data): self
    {
        foreach ($data as $key => $value) {
            $camelKey = Str::camel($key);
            if (property_exists($this, $camelKey)) {
                $this->$camelKey = $value;
            }
        }

        return $this;
    }
}