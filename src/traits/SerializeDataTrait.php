<?php

namespace digitaladditive\ExactTargetLaravel;

trait SerializeDataTrait {

    public function it_serializes_data($data)
    {
        $serialized = [];
        foreach ($data as $key => $value)
        {
            $serialized[] =
                [
                    "keys" => $value['keys'],
                    "values" => $value['values']
                ];
        }
        $serialized = json_encode($serialized);
        return $serialized;
    }
}

