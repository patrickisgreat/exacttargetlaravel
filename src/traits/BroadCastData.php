<?php

namespace digitaladditive\ExactTargetLaravel;

use \App\Events\DataBroadCast;

trait BroadCastData
{
    public static function broadcast($data, $deName)
    {
        event(new DataBroadCast($data, $deName));
    }
}
