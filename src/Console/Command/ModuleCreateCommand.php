<?php
/**
 * This file is part of the PPI Framework.
 *
 * @copyright  Copyright (c) 2011-2013 Paul Dragoonis <paul@ppi.io>
 * @license    http://opensource.org/licenses/mit-license.php MIT
 *
 * @link       http://www.ppi.io
 */

namespace PPI\Framework\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Module Command.
 *
 * @author      Paul Dragoonis <paul@ppi.io>
 * @author      Vítor Brandão <vitor@ppi.io>
 */
class ModuleCreateCommand extends AbstractCommand
{

    const TPL_ENGINE_PHP = 'php';
    const TPL_ENGINE_TWIG = 'twig';

    const ROUTING_ENGINE_SYMFONY = 'symfony';

    protected $skeletonModuleDir;
    protected $modulesDir;
    protected $tplEngine;
    protected $routingEngine;

    /**
     * @var array
     */
    protected $coreDirs = [
        'src',
        'src/Controller',
        'tests',
        'resources',
        'resources/routes',
        'resources/config',
        'resources/views',
        'resources/views/index'
    ];

    /**
     * @var array
     */
    protected $coreFiles = [
        'Module.php',
        'src/Controller/Index.php',
        'src/Controller/Shared.php',
        'resources/config/config.php'
    ];

    /**
     * @var array
     */
    protected $tplEngineFilesMap = [
        'php' => [
            'resources/views/index/index.html.php'
        ],
        'twig' => [
            'resources/views/index/base.html.twig',
            'resources/views/index/index.html.twig'
        ]
        // @todo - add Smarty
    ];

    protected $routingEngineFilesMap = [
        'symfony' => [
            'resources/routes/symfony.yml'
        ]
    ];

    protected $routingEngineTokenMap = [
        'symfony' => [
            'ROUTING_LOAD_METHOD' => 'loadSymfonyRoutes',
            'ROUTING_DEF_FILE' => 'symfony.yml'
        ]
        // @todo - add Aura
    ];

    /**
     * @param string $moduleDir
     */
    public function setSkeletonModuleDir($moduleDir)
    {
        $this->skeletonModuleDir = realpath($moduleDir);
    }

    /**
     * @param string $moduleDir
     */
    public function setTargetModuleDir($moduleDir)
    {
        $this->modulesDir = realpath($moduleDir);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('module:create')
            ->setDescription('Create a module')
            ->addArgument('name', InputArgument::REQUIRED, 'What is your module name?')
            ->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'Specify the modules directory')
            ->addOption('tpl', null, InputOption::VALUE_OPTIONAL, 'Specify the templating engine');
            ->addOption('routing', null, InputOption::VALUE_OPTIONAL, 'Specify the routing engine');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $moduleName = $input->getArgument('name');
        $moduleDir = $this->modulesDir . DIRECTORY_SEPARATOR . $moduleName;

        // Acquire Module Information
        $this->askQuestions($input, $output);
        $this->createModuleStructure($moduleDir, $moduleName);
        // Copy the core files
        $this->copyFiles($this->skeletonModuleDir, $moduleDir, $this->coreFiles);

        // Copy files relative to the selected templating engine
        switch($this->tplEngine) {
            case self::TPL_ENGINE_PHP:
            case self::TPL_ENGINE_TWIG:
                $tplFiles = $this->tplEngineFilesMap[$this->tplEngine];
                $this->copyFiles($this->skeletonModuleDir, $moduleDir, $tplFiles);
                foreach([$this->coreFiles, $tplFiles] as $tokenizedFiles) {
                    $this->replaceTokensInFiles($moduleDir, $tokenizedFiles, [
                        '[MODULE_NAME]'    => $moduleName,
                        '[TPL_ENGINE_EXT]' => $this->tplEngine
                    ]);
                }
                break;
        }
        // Routing
        switch($this->routingEngine) {
            case self::ROUTING_ENGINE_SYMFONY:
                $routingFiles = $this->routingEngineFilesMap[$this->routingEngine];
                $this->copyFiles($this->skeletonModuleDir, $moduleDir, $routingFiles);
                $routingTokensMap = $this->routingEngineTokenMap[$this->routingEngine];
                $this->replaceTokensInFiles($moduleDir, $routingFiles, $routingTokensMap);
                break;
        }

        $output->writeln("<info>Created module: {$moduleName}</info>");
        $output->writeln("<comment>To activate it, add <info>'{$moduleName}'</info> to your <info>'active_modules'</info> setting in <info>your app config file.</info></comment>");
    }

    /**
     * @param string $moduleDir
     * @param array $files
     * @param array $tokens
     *
     * @return void
     */
    protected function replaceTokensInFiles($moduleDir, $files, $tokens)
    {
        foreach($files as $file) {
            $file = $moduleDir . DIRECTORY_SEPARATOR . $file;
            if(!is_writeable($file)) {
                throw new \InvalidArgumentException(sprintf('File %s is not writeable', $file));
            }
            file_put_contents(
                $file,
                str_replace(
                    array_keys($tokens),
                    array_values($tokens),
                    file_get_contents($file)
                )
            );
        }
    }

    /**
     * @param string $skeletonDir
     * @param string $moduleDir
     * @param array $files
     *
     * @throws \InvalidArgumentException When a file path being created already exists
     */
    protected function copyFiles($skeletonDir, $moduleDir, $files)
    {
        foreach($files as $file) {
            $srcFile = $skeletonDir . DIRECTORY_SEPARATOR . $file;
            $dstFile = $moduleDir . DIRECTORY_SEPARATOR . $file;
            if(!file_exists($srcFile)) {
                throw new \InvalidArgumentException(sprintf('File does not exist: %s', $srcFile));
            }
            if(file_exists($dstFile)) {
                throw new \InvalidArgumentException(sprintf('File already exists: %s', $dstFile));
            }
            copy($srcFile, $dstFile);
        }
    }

    /**
     * @param string $moduleDir
     * @param string $moduleName
     *
     * @throws \InvalidArgumentException When a dir path being created already exists
     */
    protected function createModuleStructure($moduleDir, $moduleName)
    {
        if(is_dir($moduleDir)) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to create module: %s it already exists at %s%s',
                $moduleName, $moduleDir, $moduleName
            ));
        }

        @mkdir($moduleDir);

        // Create base structure
        foreach($this->coreDirs as $coreDir) {
            $tmpDir = $moduleDir . DIRECTORY_SEPARATOR . $coreDir;
            @mkdir($tmpDir);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function askQuestions(InputInterface $input, OutputInterface $output)
    {
        // Module DIR
        if ($input->getOption('dir') == false) {
            $dialog = $this->getHelper('dialog');
            $this->modulesDir = $dialog->ask($output, "Where's the modules dir? [" . $this->modulesDir . "]: ", $this->modulesDir);
        }
        // Templating
        if ($input->getOption('tpl') == null) {
            $questionHelper = $this->getHelper('question');
            $tplQuestion = new ChoiceQuestion('Choose your templating engine', [
                1 => 'php',
                2 => 'twig'
            ], 'php');
            $tplQuestion->setErrorMessage('Templating engine %s is invalid.');
            $this->tplEngine = $questionHelper->ask($input, $output, $tplQuestion);
        }
        // Routing
        if ($input->getOption('routing') == null) {
            $questionHelper = $this->getHelper('question');
            $routingQuestion = new ChoiceQuestion('Choose your routing engine', [
                1 => 'symfony',
            ], 'symfony');
            $tplQuestion->setErrorMessage('Routing engine %s is invalid.');
            $this->routingEngine = $questionHelper->ask($input, $output, $routingQuestion);
        }
    }
}
