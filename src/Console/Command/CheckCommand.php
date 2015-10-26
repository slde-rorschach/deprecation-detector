<?php

namespace SensioLabs\DeprecationDetector\Console\Command;

use SensioLabs\DeprecationDetector\DeprecationDetector\Configuration\Configuration;
use SensioLabs\DeprecationDetector\DeprecationDetector\Factory\DefaultFactory;
use SensioLabs\DeprecationDetector\RuleSet\RuleSet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDefinition(
                array(
                    new InputArgument('source', InputArgument::OPTIONAL, 'The path to the source files', 'src/'),
                    new InputArgument(
                        'ruleset',
                        InputArgument::OPTIONAL,
                        'The path to the composer.lock file, a rule set or source directory',
                        'composer.lock'
                    ),
                    new InputOption(
                        'container-path',
                        null,
                        InputOption::VALUE_REQUIRED,
                        'The path to symfony container cache',
                        'app/cache/dev/appDevDebugProjectContainer.xml'
                    ),
                    new InputOption('no-cache', null, InputOption::VALUE_NONE, 'Disable rule set cache'),
                    new InputOption('cache-dir', null, InputOption::VALUE_REQUIRED, 'Cache directory', '.rules/'),
                    new InputOption('filter-method-calls', null, InputOption::VALUE_OPTIONAL, 'Filter method calls', ''),
                    new InputOption('fail', null, InputOption::VALUE_NONE, 'Fails, if any deprecation is detected'),
                )
            )
            ->setDescription('Check for deprecated usage')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command tries to find all usages of deprecated features and functions in your application.
The command will generate a rule set for deprecated classes, methods and interfaces based on your libraries, lock file or by loading an existing rule set.
After that the application is checked against this rule set or list of rule sets.

Execute the script with default behavior:

    <info>php %command.full_name%</info>

By default the command checks the folder src/ against the composer.lock, but you can easily specify other folders and files:

    <info>php %command.full_name% </info><comment>path/to/script/ path/to/library/</comment>

    - the first path argument defines the application source to check
    - the second path argument defines the rule set path:

The rule set path can have the following values:

    - path to composer.lock file
    - path to library sources (directory)
    - path to rule set file

After generating a rule set it is cached within the directory .rules, but you can use the caching options to change this behavior:

    - to disable caching use the option <comment>no-cache</comment>:

      <info>php %command.full_name% </info><comment>--no-cache</comment>

    - to change the cache directory use the option <comment>cache-dir</comment>:

      <info>php %command.full_name% </info><comment>--cache-dir=path/to/cache</comment>

To get additional runtime output for information about process progress use the option <comment>verbose</comment>:

    <info>php %command.full_name% </info><comment>--verbose</comment>
EOF
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceArg = realpath($input->getArgument('source'));
        $ruleSetArg = realpath($input->getArgument('ruleset'));

        if (false === $sourceArg || false === $ruleSetArg) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s argument is invalid: "%s" is not a path.',
                    $sourceArg ? 'Rule set' : 'Source directory',
                    $sourceArg ? $input->getArgument('ruleset') : $input->getArgument('source')
                )
            );
        }

        /* @TODO Implement detector.yml and override specific values from input */
        $config = new Configuration(
            $input->getArgument('ruleset'),
            $input->getOption('container-path'),
            $input->getOption('no-cache'),
            $input->getOption('cache-dir'),
            $input->getOption('filter-method-calls'),
            $input->getOption('fail'),
            $input->getOption('verbose')
        );

        $factory = new DefaultFactory($this->getApplication()->getDispatcher());
        $detector = $factory->buildDetector($config, $output);

        /* @TODO Checking your %s for deprecations - this could take a while ... */
        $output->writeln('Checking your application for deprecations - this could take a while ...');

        try {
            $violations = $detector->checkForDeprecations($sourceArg, $ruleSetArg);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return 1;
        }

        /* @TODO move to Detector */
        //$output->writeln(sprintf('<comment>There are %s deprecations:</comment>', count($violations)));

        return $config->failOnDeprecation() ? 1 : 0;
    }
}
