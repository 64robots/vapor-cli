<?php

namespace Laravel\VaporCli\BuildProcess;

use Laravel\VaporCli\ConsoleVaporClient;
use Laravel\VaporCli\Helpers;
use Laravel\VaporCli\Manifest;

class CollectSecrets
{
    use ParticipatesInBuildProcess;

    /**
     * Execute the build process step.
     *
     * @return void
     */
    public function __invoke()
    {
        Helpers::step('<bright>Collecting Secrets</>');

        $secrets = collect(
                Helpers::app(ConsoleVaporClient::class)
                    ->secrets(Manifest::id(), $this->environment)
        )->mapWithKeys(function ($secret) {
            return [$secret['name'] => $secret['version']];
        })->toArray();

        $this->files->replace(
            $this->appPath.'/vaporSecrets.php',
            '<?php return '.var_export($secrets, true).';'
        );
    }
}
