<?php

namespace digitaladditive\exacttargetlaravel;

class PostInstall {

   public static function initConfig()
    {
        // @todo needs a timer for inputs. If the user doesn't make any keystrokes within a certain period of time the script exits
        echo "Please enter the Exact Target Client ID you wish to use, Hit Enter to proceed: ";

        $firstInput = fopen ("php://stdin", "r");

        $client_id = fgets($firstInput);

        echo "Please enter the Exact Target Client Secret you wish to use, Hit Enter to proceed: ";

        $secondInput = fopen("php://stdin", "r");

        $client_secret = fgets($secondInput);

        trim($client_id);

        trim($client_secret);

        $template = "<?php
            return array(
                'clientid' => '".$client_id."',
                'clientsecret' => '".$client_secret."'
            );";

        $etApiConfigFile = 'config.php';

        file_put_contents($etApiConfigFile, $template);

    }
}


?>