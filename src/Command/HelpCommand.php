<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\CommandCollection;
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\Core\Configure;
use SimpleXMLElement;

/**
 * Print out command list
 */
class HelpCommand extends Command implements CommandCollectionAwareInterface
{
    /**
     * The command collection to get help on.
     *
     * @var \Cake\Console\CommandCollection
     */
    protected $commands;

    /**
     * {@inheritDoc}
     */
    public function setCommandCollection(CommandCollection $commands): void
    {
        $this->commands = $commands;
    }

    /**
     * Main function Prints out the list of shells.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        if (!$args->getOption('xml')) {
            $io->out('<info>Current Paths:</info>', 2);
            $io->out('* app:  ' . Configure::read('App.dir'));
            $io->out('* root: ' . rtrim(ROOT, DIRECTORY_SEPARATOR));
            $io->out('* core: ' . rtrim(CORE_PATH, DIRECTORY_SEPARATOR));
            $io->out('');

            $io->out('<info>Available Commands:</info>', 2);
        }

        $commands = $this->commands->getIterator();
        $commands->ksort();

        if ($args->getOption('xml')) {
            $this->asXml($io, $commands);

            return static::CODE_SUCCESS;
        }
        $this->asText($io, $commands);

        return static::CODE_SUCCESS;
    }

    /**
     * Output text.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param \ArrayIterator $commands The command collection to output.
     * @return void
     */
    protected function asText($io, $commands): void
    {
        $invert = [];
        foreach ($commands as $name => $class) {
            if (is_object($class)) {
                $class = get_class($class);
            }
            if (!isset($invert[$class])) {
                $invert[$class] = [];
            }
            $invert[$class][] = $name;
        }

        foreach ($commands as $name => $class) {
            if (is_object($class)) {
                $class = get_class($class);
            }
            if (count($invert[$class]) === 1) {
                $io->out('- ' . $name);
            }

            if (count($invert[$class]) > 1) {
                // Sort by length so we can get the shortest name.
                usort($invert[$class], function ($a, $b) {
                    return strlen($a) - strlen($b);
                });
                $io->out('- ' . array_shift($invert[$class]));

                // Empty the list to prevent duplicates
                $invert[$class] = [];
            }
        }
        $io->out('');

        $io->out('To run a command, type <info>`cake shell_name [args|options]`</info>');
        $io->out('To get help on a specific command, type <info>`cake shell_name --help`</info>', 2);
    }

    /**
     * Output as XML
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param \ArrayIterator $commands The command collection to output
     * @return void
     */
    protected function asXml($io, $commands): void
    {
        $shells = new SimpleXMLElement('<shells></shells>');
        foreach ($commands as $name => $class) {
            if (is_object($class)) {
                $class = get_class($class);
            }
            $shell = $shells->addChild('shell');
            $shell->addAttribute('name', $name);
            $shell->addAttribute('call_as', $name);
            $shell->addAttribute('provider', $class);
            $shell->addAttribute('help', $name . ' -h');
        }
        $io->setOutputAs(ConsoleOutput::RAW);
        $io->out($shells->saveXML());
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Get the list of available shells for this application.'
        )->addOption('xml', [
            'help' => 'Get the listing as XML.',
            'boolean' => true,
        ]);

        return $parser;
    }
}