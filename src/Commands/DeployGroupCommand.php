<?php

namespace Laravel\VaporCli\Commands;

use Laravel\VaporCli\Path;
use Laravel\VaporCli\Helpers;
use Laravel\VaporCli\Manifest;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class DeployGroupCommand extends Command
{
    use DisplaysDeploymentProgress;

    protected $noRebuild = false;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('deploy:group')
            ->addArgument('group', InputArgument::REQUIRED, 'The group name')
            ->setDescription('Deploy an environment group');
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        Helpers::ensure_api_token_is_available();

        Manifest::environmentsForGroup($this->argument('group'))
            ->each(fn($environment) => $this->deployEnvironment($environment));
        
        (new Filesystem)->deleteDirectory(Path::vapor());
    }

    protected function deployEnvironment(string $environment)
    {
        $this->call('deploy', [
            'environment' => $environment,
            '--no-rebuild' => $this->noRebuild,
            '--group' => $this->argument('group'),
        ]);

        $this->noRebuild = true;
    }
}
