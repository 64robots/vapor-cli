<?php

namespace Laravel\VaporCli\Commands;

use DateTime;
use Laravel\VaporCli\Helpers;
use Laravel\VaporCli\BuildProcess\ProcessAssets;
use Symfony\Component\Console\Input\InputOption;
use Laravel\VaporCli\BuildProcess\InjectHandlers;
use Laravel\VaporCli\BuildProcess\CollectSecrets;
use Laravel\VaporCli\BuildProcess\CompressVendor;
use Symfony\Component\Console\Input\InputArgument;
use Laravel\VaporCli\BuildProcess\ConfigureArtisan;
use Laravel\VaporCli\BuildProcess\InjectErrorPages;
use Laravel\VaporCli\BuildProcess\RemoveIgnoredFiles;
use Laravel\VaporCli\BuildProcess\CompressApplication;
use Laravel\VaporCli\BuildProcess\SetBuildEnvironment;
use Laravel\VaporCli\BuildProcess\ExecuteBuildCommands;
use Laravel\VaporCli\BuildProcess\ExecuteGroupBuildCommands;
use Laravel\VaporCli\BuildProcess\InjectRdsCertificate;
use Laravel\VaporCli\BuildProcess\CopyApplicationToBuildPath;
use Laravel\VaporCli\BuildProcess\ConfigureComposerAutoloader;
use Laravel\VaporCli\BuildProcess\HarmonizeConfigurationFiles;
use Laravel\VaporCli\BuildProcess\ExtractAssetsToSeparateDirectory;
use Laravel\VaporCli\BuildProcess\ExtractVendorToSeparateDirectory;

class BuildCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name', 'staging')
            ->addOption('asset-url', null, InputOption::VALUE_OPTIONAL, 'The asset base URL')
            ->addOption('no-rebuild', null, InputOption::VALUE_OPTIONAL, 'Don\'t do a full rebuild (used for groups)', false) 
            ->addOption('group', null, InputOption::VALUE_OPTIONAL, 'Group deployment', false)
            ->setDescription('Build the project archive');
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        Helpers::ensure_api_token_is_available();

        Helpers::line('Building project for environment '.$this->argument('environment').'...');
  
        $startedAt = new DateTime;
        collect($this->getBuildCommands())->each->__invoke();

        $time = (new DateTime)->diff($startedAt)->format('%im%Ss');

        Helpers::line();
        Helpers::line('<info>Project built successfully.</info> ('.$time.')');
    }

    protected function getBuildCommands()
    {
        $commands = $this->option('no-rebuild') ? [] :
            [
                new CopyApplicationToBuildPath,
                new HarmonizeConfigurationFiles,
                new SetBuildEnvironment($this->argument('environment'), $this->option('asset-url')),
                $this->option('group') ?
                    new ExecuteGroupBuildCommands($this->option('group')) :
                    new ExecuteBuildCommands($this->argument('environment')),
                new ConfigureArtisan,
                new ConfigureComposerAutoloader,
                new RemoveIgnoredFiles,
                new ProcessAssets($this->option('asset-url')),
                new ExtractAssetsToSeparateDirectory,
                new InjectHandlers,
                new InjectErrorPages,
                new InjectRdsCertificate,
            ];

        return array_merge($commands, [
            new CollectSecrets($this->argument('environment')),
            new ExtractVendorToSeparateDirectory,
            new CompressApplication,
            new CompressVendor,
        ]);
    }
}
